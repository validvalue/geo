<?php

namespace validvalue\geo\models;

/**
 * This is the ActiveQuery class for [[RegionGeo]].
 *
 * @see RegionGeo
 */
class RegionGeoQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }*/

    /**
     * @inheritdoc
     * @return RegionGeo[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return RegionGeo|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}