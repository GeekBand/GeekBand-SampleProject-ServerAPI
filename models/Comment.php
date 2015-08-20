<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "comment".
 *
 * @property string $id
 * @property integer $shop_id
 * @property integer $user_id
 * @property integer $project_id
 * @property string $comment
 * @property string $pic_link
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
            [['shop_id', 'user_id', 'project_id'], 'integer'],
            [['comment'], 'string'],
            [['created', 'modified'], 'safe'],
            [['pic_link'], 'string', 'max' => 64]
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
            'project_id' => 'Project ID',
            'comment' => 'Comment',
            'pic_link' => 'Pic Link',
            'created' => 'Created',
            'modified' => 'Modified',
        ];
    }
}
