<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "picture".
 *
 * @property string $id
 * @property integer $node_id
 * @property integer $user_id
 * @property string $pic_link
 * @property string $created
 * @property string $modified
 */
class Picture extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'picture';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['node_id', 'user_id'], 'integer'],
            [['created', 'modified'], 'safe'],
            [['pic_link'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'node_id' => 'Node ID',
            'user_id' => 'User ID',
            'pic_link' => 'Pic Link',
            'created' => 'Created',
            'modified' => 'Modified',
        ];
    }
}
