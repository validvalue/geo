<?php

namespace validvalue\geo\models;

use creocoder\nestedsets\NestedSetsBehavior;
use Yii;
use yii\behaviors\SluggableBehavior;

/**
 * This is the model class for table "{{%geo_geo}}".
 *
 * @property integer $id
 * @property integer $tree
 * @property integer $lft
 * @property integer $rgt
 * @property integer $depth
 * @property integer $lat
 * @property integer $lng
 * @property integer $type
 * @property string $code
 * @property string $slug
 * @property string $name
 * @property string $full_name
 */
class Geo extends \yii\db\ActiveRecord
{
    const TYPE_COUNTRY = 1;
    const TYPE_DISTRICT = 2;
    const TYPE_REGION = 3;
    const TYPE_CITY = 4;

    static $ipCache = [];
    /**
     * Default geo object if location not found
     * @var Geo
     */
    static $defaultGeo;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%geo_geo}}';
    }

    /**
     * @inheritdoc
     * @return GeoQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new GeoQuery(get_called_class());
    }

    /**
     * @param null $ip
     * @return Geo|null
     */
    static public function location($ip = null)
    {
        if ($ip === null) {
            $ip = Yii::$app->request->userIP;
        }
        $ipInt = explode(".", $ip);
        $ipInt = count($ipInt) != 4 ? null : $ipInt = $ipInt[3] + 256 * ($ipInt[2] + 256 * ($ipInt[1] + 256 * $ipInt[0]));
        if (empty(self::$ipCache[$ip])) {
            if ($ipInt) {
                $data = Ip::find()->where("[[begin_ip]]<=:ip and [[end_ip]]>=:ip", [':ip' => $ipInt])->one();
            }
            if (empty($data) && is_numeric(self::$defaultGeo)) {
                Geo::$defaultGeo = Geo::findOne(['id' => self::$defaultGeo, 'type' => self::TYPE_CITY]);
            }
            self::$ipCache[$ip] = empty($data) ? self::$defaultGeo : $data->city;
        }
        return self::$ipCache[$ip];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['name', 'required'],
            [['tree', 'lft', 'rgt', 'depth', 'type'], 'integer'],
            [['lat', 'lng'], 'double'],
            [['code'], 'string', 'max' => 2],
            [['name', 'full_name', 'slug'], 'string', 'max' => 255],
            ['slug', 'unique', 'targetAttribute' => 'slug', 'message' => 'The Slug has already been taken.']
            // TODO unique code only for TYPE_COUNTRY
            //[['type', 'code'], 'unique', 'targetAttribute' => ['type', 'code'], 'message' => 'The combination of Type and Code has already been taken.']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('geo/general', 'ID'),
            'tree' => Yii::t('geo/general', 'Tree'),
            'lft' => Yii::t('geo/general', 'Lft'),
            'rgt' => Yii::t('geo/general', 'Rgt'),
            'depth' => Yii::t('geo/general', 'Depth'),
            'lat' => Yii::t('geo/general', 'Latitude'),
            'lng' => Yii::t('geo/general', 'Longitude'),
            'type' => Yii::t('geo/general', 'Type'),
            'code' => Yii::t('geo/general', 'Code'),
            'slug' => Yii::t('geo/general', 'Slug'),
            'name' => Yii::t('geo/general', 'Name'),
            'full_name' => Yii::t('geo/general', 'Full Name'),
        ];
    }

    public function behaviors()
    {
        return [
            'tree' => [
                'class' => NestedSetsBehavior::className(),
                'treeAttribute' => 'tree',
            ],
            [
                'class' => SluggableBehavior::className(),
                'attribute' => 'name',
                'ensureUnique' => true,
            ],
        ];
    }

    public function transactions()
    {
        return [
            self::SCENARIO_DEFAULT => self::OP_ALL,
        ];
    }
}
