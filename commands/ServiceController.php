<?php
namespace validvalue\geo\commands;

use validvalue\geo\models\Geo;
use validvalue\geo\models\Ip;
use Yii;
use yii\helpers\Console;
use yii\helpers\FileHelper;

/**
 * Команды для работы с geodata
 * Выполняются из командной строки
 * @author Pavel Aleksandrov <inblank@yandex.ru>
 * @copyright Copyright (c) 2014, Pavel Aleksandrov
 * @package application
 * @subpackage command
 */
class ServiceController extends \yii\console\Controller
{

    public $urls = [
        'geo' => 'http://ipgeobase.ru/files/db/Main/geo_files.zip',
        'countries' => 'http://www.artlebedev.ru/tools/country-list/tab/',
    ];

    /**
     * Загрузка и установка базы IP
     */
    public function actionUpdate()
    {
        $db = Yii::$app->db;
        // check module install
        if (0 == count($db->createCommand("SHOW TABLES LIKE '" . preg_replace('/{{%([^}]+)}}/', $db->tablePrefix . '\1', Geo::tableName()) . "'")->queryAll())) {
            $this->stderr("Error: Migrate module Geo first...\n", Console::FG_RED);
            Yii::$app->end(1);
        }

        $runtimePath = Yii::getAlias('@runtime/geo_module');
        FileHelper::createDirectory($runtimePath);

        echo "    > Load actual Geo data ...";

        $time = microtime(true);
        $zipFile = $runtimePath . '/geo_files.zip';
        $countriesFile = $runtimePath . '/country.txt';

        if (
            file_put_contents($zipFile, fopen($this->urls['geo'], 'r')) === false
            || file_put_contents($countriesFile, fopen($this->urls['countries'], 'r')) === false
        ) {
            $this->stderr("Can not load Geo data\n", Console::FG_RED);
        } else {
            echo ' done (time: ' . sprintf('%.3f', microtime(true) - $time) . "s)\n";

            // unpack
            echo "    > Unpack Geo data ...";
            $time = microtime(true);

            $zip = new \ZipArchive;
            if ($zip->open($zipFile) !== true) {
                $this->stderr("Geo data archive open error\n", Console::FG_RED);
            } else {
                if ($zip->extractTo($runtimePath) !== true) {
                    $this->stderr("Can not unpack Geo data archive\n", Console::FG_RED);
                } else {
                    if (!file_exists($runtimePath . '/cities.txt') || !file_exists($runtimePath . '/cidr_optim.txt')) {
                        $this->stderr("Not found necessary files in Geo data archive\n", Console::FG_RED);
                    } else {
                        echo ' done (time: ' . sprintf('%.3f', microtime(true) - $time) . "s)\n";

                        echo "    > Build countries list ...";
                        $time = microtime(true);
                        $db->createCommand('SET FOREIGN_KEY_CHECKS=0;truncate table ' . Geo::tableName() . ';truncate table ' . Ip::tableName() . ';SET FOREIGN_KEY_CHECKS=1')->execute();
                        $countries = array();
                        $countryIdShift = 60000;
                        $unknownCountryId = 3000 + $countryIdShift;
                        foreach (file($countriesFile) as $row) {
                            $row = array_map('trim', explode("\t", $row));
                            if (strlen($row[3]) > 2) {
                                continue;
                            }
                            $countries[strtoupper($row[3])] = [
                                'id' => (int)$row[5] + $countryIdShift, // country id from `iso` field.
                                'name' => $row[0],
                                'full_name' => empty($row[1]) ? $row[0] : $row[1],
                            ];
                        }
                        echo ' done (time: ' . sprintf('%.3f', microtime(true) - $time) . "s)\n";

                        echo "    > Build ip address blocks ...";
                        $time = microtime(true);
                        $ipAddresses = [];
                        foreach (file($runtimePath . '/cidr_optim.txt') as $row) {
                            $row = array_map('trim', explode("\t", mb_convert_encoding($row, 'UTF-8', 'cp-1251')));
                            $countryCode = strtoupper($row[3]);
                            if (empty($countries[$countryCode])) {
                                $countries[$countryCode] = [
                                    'id' => $unknownCountryId++,
                                    'name' => '::' . $countryCode,
                                    'full_name' => '::' . $countryCode,
                                ];
                            }
                            $cityID = $row[4] == '-' ? 0 : $row[4];
                            if (empty($ipAddresses[$cityID])) {
                                $ipAddresses[$cityID] = [
                                    'countryCode' => $countryCode,
                                    'ips' => [],
                                ];
                            }
                            $ipAddresses[$cityID]['ips'][] = [$row[0], $row[1]];
                        }
                        echo ' done (time: ' . sprintf('%.3f', microtime(true) - $time) . "s)\n";

                        echo "    > Build cities, regions, districts ...";
                        $time = microtime(true);
                        $districtList = array();
                        $errorCities = array();
                        foreach (file($runtimePath . '/cities.txt') as $row) {
                            $row = array_map('trim', explode("\t", mb_convert_encoding($row, 'UTF-8', 'cp-1251')));
                            // find city ips
                            if (empty($ipAddresses[$row[0]])) {
                                $errorCities[] = [
                                    'id' => $row[0],
                                    'name' => $row[1],
                                    'district' => $row[3],
                                    'region' => $row[2],
                                    'lat' => $row[4],
                                    'lng' => $row[5],
                                ];
                                continue;
                            }
                            $countryCode = $ipAddresses[$row[0]]['countryCode'];
                            if (empty($districtList[$countryCode])) {
                                $districtList[$countryCode] = [];
                            }
                            if (!in_array($row[3], array_column($districtList[$countryCode], 0))) {
                                // new district
                                $districtList[$countryCode][] = [$row[3], 'regions' => []];
                            }
                            $districtID = array_search($row[3], array_column($districtList[$countryCode], 0));
                            if (!in_array($row[2], array_column($districtList[$countryCode][$districtID]['regions'], 0))) {
                                // new region
                                $districtList[$countryCode][$districtID]['regions'][] = [$row[2], $row[1] == Geo::TYPE_REGION, 'cities' => []];
                            }
                            $regionID = array_search($row[2], array_column($districtList[$countryCode][$districtID]['regions'], 0));
                            $districtList[$countryCode][$districtID]['regions'][$regionID]['cities'][$row[0]] = [
                                'id' => $row[0],
                                'name' => $row[1],
                                'lat' => $row[4],
                                'lng' => $row[5],
                            ];
                        }
                        echo ' done (time: ' . sprintf('%.3f', microtime(true) - $time) . "s)\n";

                        echo "    > Fixed cities missed in ip list ...";
                        $time = microtime(true);
                        foreach ($errorCities as $city) {
                            $found = false;
                            foreach ($districtList as $countryCode => $data) {
                                $districtID = array_search($city['district'], array_column($data, 0));
                                if ($districtID !== false) {
                                    $regionID = array_search($city['region'], array_column($data[$districtID]['regions'], 0));
                                    if ($regionID !== false) {
                                        if (empty($districtList[$countryCode][$districtID]['regions'][$regionID]['cities'])) {
                                            $districtList[$countryCode][$districtID]['regions'][$regionID]['cities'] = [];
                                        }
                                        $districtList[$countryCode][$districtID]['regions'][$regionID]['cities'][$city['id']] = [
                                            'id' => $city['id'],
                                            'name' => $city['name'],
                                            'lat' => $city['lat'],
                                            'lng' => $city['lng'],
                                        ];
                                        $found = true;
                                        break;
                                    }
                                }
                            }
                            if (!$found) {
                                $this->stderr("\nNot found country for city {$city['name']}...", Console::FG_RED);
                            }
                        }
                        echo ' done (time: ' . sprintf('%.3f', microtime(true) - $time) . "s)\n";

                        //file_put_contents(__DIR__.'/district.txt', print_r($districtList, true));
                        //exit;

                        echo "    > Store geo to database ...";
                        $time = microtime(true);
                        $districtID = 1;
                        $regionID = 1;
                        foreach ($countries as $countryCode => $countryData) {
                            $country = new Geo();
                            $country->id = $countryData['id'];
                            $country->type = Geo::TYPE_COUNTRY;
                            $country->code = $countryCode;
                            $country->name = $countryData['name'];
                            $country->full_name = $countryData['full_name'];
                            $country->makeRoot();
                            if (!empty($districtList[$countryCode])) {
                                foreach ($districtList[$countryCode] as $districtData) {
                                    if (count($districtData['regions']) == 0) {
                                        // no regions in district
                                        continue;
                                    }
                                    $district = new Geo();
                                    $district->id = $districtID + $countryIdShift + 5000;
                                    $district->type = Geo::TYPE_DISTRICT;
                                    $district->name = $districtData[0];
                                    $district->full_name = $districtData[0];
                                    $district->appendTo($country);
                                    $districtID++;
                                    foreach ($districtData['regions'] as $regionData) {
                                        if (count($regionData['cities']) == 0) {
                                            // no cities in region
                                            continue;
                                        }
                                        if (count($regionData['cities']) == 1) {
                                            $cityData = current($regionData['cities']);
                                            if ($cityData['name'] == $regionData[0]) {
                                                // region by city name. add city to district, skip region
                                                $city = new Geo();
                                                $city->id = $cityData['id'];
                                                $city->type = Geo::TYPE_CITY;
                                                $city->name = $cityData['name'];
                                                $city->full_name = $cityData['name'];
                                                $city->appendTo($district);
                                                continue;
                                            }
                                        }
                                        $region = new Geo();
                                        $region->id = $regionID + $countryIdShift + 10000;
                                        $region->type = Geo::TYPE_REGION;
                                        $region->name = $regionData[0];
                                        $region->full_name = $regionData[0];
                                        $region->appendTo($district);
                                        $regionID++;
                                        foreach ($regionData['cities'] as $cityID => $cityData) {
                                            $city = new Geo();
                                            $city->id = $cityID;
                                            $city->type = Geo::TYPE_CITY;
                                            $city->name = $cityData['name'];
                                            $city->full_name = $cityData['name'];
                                            $city->appendTo($cityData['name'] == $regionData[0] ? $district : $region);
                                        }
                                        unset($region);
                                    }
                                    unset($district);
                                }
                            }

                        }
                        echo ' done (time: ' . sprintf('%.3f', microtime(true) - $time) . "s)\n";

                        echo "    > Store ip to database ...";
                        $time = microtime(true);
                        $values = [
                            ':city_id' => null,
                            ':country_id' => null,
                            ':begin_ip' => null,
                            ':end_ip' => null,
                        ];
                        $db->createCommand("SET FOREIGN_KEY_CHECKS=0")->execute();
                        $insert = $db->createCommand("INSERT INTO " . Ip::tableName()
                            . " SET [[city_id]]=:city_id, [[country_id]]=:country_id, [[begin_ip]]=:begin_ip, [[end_ip]]=:end_ip");
                        foreach ($values as $name => $val) {
                            $insert->bindParam($name, $values[$name]);
                        }
                        foreach ($ipAddresses as $cityID => $ipData) {
                            foreach ($ipData['ips'] as $ipBlock) {
                                $values[':city_id'] = $cityID === 0 ? null : $cityID;
                                $values[':country_id'] = $countries[$ipData['countryCode']]['id'];
                                $values[':begin_ip'] = $ipBlock[0];
                                $values[':end_ip'] = $ipBlock[1];
                                $insert->execute();
                            }
                        }
                        $db->createCommand("SET FOREIGN_KEY_CHECKS=1")->execute();
                        echo ' done (time: ' . sprintf('%.3f', microtime(true) - $time) . "s)\n";

                    }
                }
            }
            // закрываем архив
            @$zip->close();
        }
        // remove temp
        FileHelper::removeDirectory($runtimePath);
    }
}
