<?php

namespace validvalue\geo\models;

/**
 * This is the ActiveQuery class for [[Ip]].
 *
 * @see Ip
 */
class IpQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }*/

    /**
     * @inheritdoc
     * @return Ip[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return Ip|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}