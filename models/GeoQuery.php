<?php

namespace validvalue\geo\models;

use creocoder\nestedsets\NestedSetsQueryBehavior;

/**
 * This is the ActiveQuery class for [[GeoGeo]].
 *
 * @see GeoGeo
 */
class GeoQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }*/

    /**
     * @inheritdoc
     * @return Geo[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return Geo|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    public function behaviors()
    {
        return [
            NestedSetsQueryBehavior::className(),
        ];
    }
}