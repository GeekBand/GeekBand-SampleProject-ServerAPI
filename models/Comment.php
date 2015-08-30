<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "comment".
 *
 * @property string $id
 * @property integer $shop_id
 * @property integer $user_id
 * @property string $comment
 * @property integer $pic_id
 * @property string $created
 * @property string $modified
 */
class Comment extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'comment';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['shop_id', 'user_id', 'pic_id'], 'integer'],
            [['comment'], 'string'],
            [['created', 'modified'], 'safe']
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
            'user_id' => 'User ID',
            'comment' => 'Comment',
            'pic_id' => 'Pic ID',
            'created' => 'Created',
            'modified' => 'Modified',
        ];
    }
}
