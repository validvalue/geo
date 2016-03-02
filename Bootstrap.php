<?php

namespace validvalue\geo;

use validvalue\geo\models\Geo;
use yii;
use yii\base\Application;
use yii\console\Application as ConsoleApplication;
use yii\i18n\PhpMessageSource;
use yii\web\GroupUrlRule;

class Bootstrap implements yii\base\BootstrapInterface
{

    /** @var array Model's map */
    private $_modelMap = [
        'Geo' => 'validvalue\geo\models\Geo',
        'Region' => 'validvalue\geo\models\Region',
    ];

    /**
     * Bootstrap method to be called during application bootstrap stage.
     * @param Application $app the application currently running
     */
    public function bootstrap($app)
    {
        /** @var Module $module */
        /** @var \yii\db\ActiveRecord $modelName */
        if ($app->hasModule('geo') && ($module = $app->getModule('geo')) instanceof Module) {
            $this->_modelMap = array_merge($this->_modelMap, $module->modelMap);
            foreach ($this->_modelMap as $name => $definition) {
                $class = "validvalue\\geo\\models\\" . $name;
                Yii::$container->set($class, $definition);
                $modelName = is_array($definition) ? $definition['class'] : $definition;
                $module->modelMap[$name] = $modelName;
            }
            if (!empty($module->defaultGeo)) {
                Geo::$defaultGeo = $module->defaultGeo;
            }
            if ($app instanceof ConsoleApplication) {
                $module->controllerNamespace = 'validvalue\geo\commands';
            } else {
                $configUrlRule = [
                    'prefix' => $module->urlPrefix,
                    'rules' => $module->urlRules,
                ];

                if ($module->urlPrefix != 'geo') {
                    $configUrlRule['routePrefix'] = 'geo';
                }

                $app->urlManager->addRules([new GroupUrlRule($configUrlRule)], false);
            }

            if (!isset($app->get('i18n')->translations['geo*'])) {
                $app->get('i18n')->translations['geo*'] = [
                    'class' => PhpMessageSource::className(),
                    'basePath' => __DIR__ . '/messages',
                ];
            }
        }
    }
}