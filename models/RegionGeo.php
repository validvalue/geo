<?php

namespace validvalue\geo\models;

use Yii;

/**
 * This is the model class for table "{{%geo_region_geo}}".
 *
 * @property integer $region_id
 * @property integer $geo_id
 *
 * @property Geo $geo
 * @property Region $region
 */
class RegionGeo extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%geo_region_geo}}';
    }

    /**
     * @inheritdoc
     * @return RegionGeoQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new RegionGeoQuery(get_called_class());
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['region_id', 'geo_id'], 'required'],
            [['region_id', 'geo_id'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'region_id' => Yii::t('general', 'Region ID'),
            'geo_id' => Yii::t('general', 'Geo ID'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGeo()
    {
        return $this->hasOne(Geo::className(), ['id' => 'geo_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRegion()
    {
        return $this->hasOne(Region::className(), ['id' => 'region_id']);
    }
}
