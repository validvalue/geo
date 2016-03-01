<?php

namespace validvalue\geo\models;

use Yii;

/**
 * This is the model class for table "{{%geo_region}}".
 *
 * @property integer $id
 * @property string $slug
 * @property string $name
 *
 * @property RegionGeo[] $regionsGeos
 * @property Geo[] $geos
 */
class Region extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%geo_region}}';
    }

    /**
     * @inheritdoc
     * @return RegionQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new RegionQuery(get_called_class());
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['slug', 'name'], 'string', 'max' => 255],
            [['slug'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('general', 'ID'),
            'slug' => Yii::t('general', 'Alias'),
            'name' => Yii::t('general', 'Name'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRegionGeos()
    {
        return $this->hasMany(RegionGeo::className(), ['region_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGeos()
    {
        return $this->hasMany(Geo::className(), ['id' => 'geo_id'])->viaTable('{{%geo_region_geo}}', ['region_id' => 'id']);
    }
}
