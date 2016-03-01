<?php

namespace validvalue\geo\migrations;

use yii;

class Migration extends yii\db\Migration
{
    /**
     * @var string
     */
    protected $tableOptions;
    protected $tableGroup = 'geo_';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        switch (Yii::$app->db->driverName) {
            case 'mysql':
            case 'pgsql':
                $this->tableOptions = null;
                break;
            default:
                throw new \RuntimeException('Your database is not supported!');
        }
    }
}
