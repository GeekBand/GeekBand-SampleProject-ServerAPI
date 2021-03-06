<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "node".
 *
 * @property string $id
 * @property integer $shop_id
 * @property string $geom
 * @property string $tags
 * @property string $addr
 * @property string $created
 * @property string $modified
 */
class Node extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'node';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['shop_id'], 'integer'],
            [['geom'], 'required'],
            [['geom', 'tags'], 'string'],
            [['created', 'modified'], 'safe'],
            [['addr'], 'string', 'max' => 63]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'shop_id' => 'Shop ID',
            'geom' => 'Geom',
            'tags' => 'Tags',
            'addr' => 'Addr',
            'created' => 'Created',
            'modified' => 'Modified',
        ];
    }
}
