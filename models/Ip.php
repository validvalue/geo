<?php

namespace validvalue\geo\models;

use Yii;

/**
 * This is the model class for table "{{%geo_ip}}".
 *
 * @property integer $begin
 * @property integer $end
 * @property integer $country_id
 * @property integer $city_id
 *
 * @property Geo $city
 * @property Geo $country
 */
class Ip extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%geo_ip}}';
    }

    /**
     * @inheritdoc
     * @return IpQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new IpQuery(get_called_class());
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['begin', 'end'], 'required'],
            [['begin', 'end', 'country_id', 'city_id'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'begin' => Yii::t('general', 'Begin'),
            'end' => Yii::t('general', 'End'),
            'country_id' => Yii::t('general', 'Country ID'),
            'city_id' => Yii::t('general', 'City ID'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCity()
    {
        return $this->hasOne(Geo::className(), ['id' => 'city_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCountry()
    {
        return $this->hasOne(Geo::className(), ['id' => 'country_id']);
    }
}
