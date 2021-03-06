<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "user".
 *
 * @property integer $id
 * @property string $name
 * @property string $password
 * @property string $email
 * @property integer $project_id
 * @property string $avatar
 * @property string $created
 * @property string $modified
 */
class User extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['project_id'], 'integer'],
            [['created', 'modified'], 'safe'],
            [['name', 'email'], 'string', 'max' => 64],
            [['password', 'avatar'], 'string', 'max' => 255],
            [['name'], 'unique'],
            [['email'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'password' => 'Password',
            'email' => 'Email',
            'project_id' => 'Project ID',
            'avatar' => 'Avatar',
            'created' => 'Created',
            'modified' => 'Modified',
        ];
    }
}
