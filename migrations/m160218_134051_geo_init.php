<?php

use validvalue\geo\migrations\Migration;
use yii\db\Schema;

class m160218_134051_geo_init extends Migration
{
    public function up()
    {
        // дерево стран, городов и т.д.
        $this->createTable('{{%' . $this->tableGroup . 'geo}}', [
            'id' => Schema::TYPE_PK,

            'tree' => Schema::TYPE_INTEGER . ' NOT NULL',
            'lft' => Schema::TYPE_INTEGER . ' NOT NULL',
            'rgt' => Schema::TYPE_INTEGER . ' NOT NULL',
            'depth' => Schema::TYPE_INTEGER . ' NOT NULL',

            'lat' => Schema::TYPE_DOUBLE . ' NOT NULL DEFAULT 0',
            'lng' => Schema::TYPE_DOUBLE . ' NOT NULL DEFAULT 0',
            'type' => Schema::TYPE_INTEGER . ' NOT NULL DEFAULT 0',
            'code' => Schema::TYPE_STRING . "(2) DEFAULT NULL",

            'slug' => Schema::TYPE_STRING . "(255) NOT NULL DEFAULT ''",
            'name' => Schema::TYPE_STRING . "(255) NOT NULL DEFAULT ''",
            'full_name' => Schema::TYPE_STRING . "(255) NOT NULL DEFAULT ''",
        ], $this->tableOptions);
        $this->createIndex('unique_slug', '{{%' . $this->tableGroup . 'geo}}', 'slug', true);
        $this->createIndex('unique_code', '{{%' . $this->tableGroup . 'geo}}', 'code', true);

        // связяь ip и адресов
        $this->createTable('{{%' . $this->tableGroup . 'ip}}', [
            'begin_ip' => Schema::TYPE_BIGINT . ' NOT NULL',
            'end_ip' => Schema::TYPE_BIGINT . ' NOT NULL',
            'country_id' => Schema::TYPE_INTEGER . ' DEFAULT NULL',
            'city_id' => Schema::TYPE_INTEGER . ' DEFAULT NULL',
        ], $this->tableOptions);
        $this->addPrimaryKey('main', '{{%' . $this->tableGroup . 'ip}}', ['begin_ip', 'end_ip']);
        $this->addForeignKey('fk__geo_ip__country', '{{%' . $this->tableGroup . 'ip}}', 'country_id', '{{%' . $this->tableGroup . 'geo}}', 'id', 'CASCADE', 'RESTRICT');
        $this->addForeignKey('fk__geo_ip__city', '{{%' . $this->tableGroup . 'ip}}', 'city_id', '{{%' . $this->tableGroup . 'geo}}', 'id', 'CASCADE', 'RESTRICT');

        // используемые страны
        $this->createTable('{{%' . $this->tableGroup . 'country}}', [
            'geo_id' => Schema::TYPE_INTEGER . ' NOT NULL DEFAULT 0',
            'sort' => Schema::TYPE_INTEGER . ' DEFAULT NULL',
        ], $this->tableOptions);
        $this->createIndex('sorting', '{{%' . $this->tableGroup . 'country}}', ['geo_id', 'sort'], true);
        $this->addForeignKey('fk__geo_country__geo', '{{%' . $this->tableGroup . 'country}}', 'geo_id', '{{%' . $this->tableGroup . 'geo}}', 'id', 'CASCADE', 'RESTRICT');

        // города быстрого выбора по гео объектам
        $this->createTable('{{%' . $this->tableGroup . 'city}}', [
            'geo_id' => Schema::TYPE_INTEGER . ' NOT NULL DEFAULT 0',
            'sort' => Schema::TYPE_INTEGER . ' DEFAULT NULL',
            'city_id' => Schema::TYPE_INTEGER . ' NOT NULL DEFAULT 0',
        ], $this->tableOptions);
        $this->createIndex('sorting', '{{%' . $this->tableGroup . 'city}}', ['geo_id', 'sort'], true);
        $this->addForeignKey('fk__geo_city__geo', '{{%' . $this->tableGroup . 'city}}', 'geo_id', '{{%' . $this->tableGroup . 'geo}}', 'id', 'CASCADE', 'RESTRICT');
        $this->addForeignKey('fk__geo_city__city', '{{%' . $this->tableGroup . 'city}}', 'city_id', '{{%' . $this->tableGroup . 'geo}}', 'id', 'CASCADE', 'RESTRICT');

        // 'виртуальны' регионы.
        // Например может использоваться как регион продаж куда входят несколько географических объектов
        $this->createTable('{{%' . $this->tableGroup . 'region}}', [
            'id' => Schema::TYPE_PK,
            'slug' => Schema::TYPE_STRING . "(255) NOT NULL DEFAULT ''",
            'name' => Schema::TYPE_STRING . "(255) NOT NULL DEFAULT ''",
        ], $this->tableOptions);
        $this->createIndex('unique_slug', '{{%' . $this->tableGroup . 'region}}', 'slug', true);

        // связь регионов и географичеких объектов
        $this->createTable('{{%' . $this->tableGroup . 'region_geo}}', [
            'region_id' => Schema::TYPE_INTEGER . ' NOT NULL DEFAULT 0',
            'geo_id' => Schema::TYPE_INTEGER . ' NOT NULL DEFAULT 0',
        ], $this->tableOptions);
        $this->addPrimaryKey('main', '{{%' . $this->tableGroup . 'region_geo}}', ['region_id', 'geo_id']);
        $this->addForeignKey('fk__goods_region_geo__region', '{{%' . $this->tableGroup . 'region_geo}}', 'region_id', '{{%' . $this->tableGroup . 'region}}', 'id', 'CASCADE', 'RESTRICT');
        $this->addForeignKey('fk__goods_region_geo__geo', '{{%' . $this->tableGroup . 'region_geo}}', 'geo_id', '{{%' . $this->tableGroup . 'geo}}', 'id', 'CASCADE', 'RESTRICT');
    }

    public function down()
    {
        $tables = [
            'region_geo',
            'region',
            'country',
            'city',
            'ip',
            'geo',
        ];
        foreach ($tables as $table) {
            $this->dropTable('{{%' . $this->tableGroup . $table . '}}');
        }
        return true;
    }

    /*
    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
    }

    public function safeDown()
    {
    }
    */
}
