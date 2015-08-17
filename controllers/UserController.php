<?php
/**
 *  ____              _   ____             _
 *|  _ \  ___  _ __ | |_|  _ \ __ _ _ __ (_) ___
 *| | | |/ _ \| '_ \| __| |_) / _` | '_ \| |/ __|
 *| |_| | (_) | | | | |_|  __/ (_| | | | | | (__
 *|____/ \___/|_| |_|\__|_|   \__,_|_| |_|_|\___|
 *
 * Author: diaosj
 * Date: 15/8/16
 * Time: 下午8:35
 */

namespace app\controllers;

use app\components\CJson;
use app\components\Util;
use yii\db\Query;
use yii\rest\ActiveController;
use yii\web\User;
use app\models\LoginForm;

class UserController extends ActiveController {
    public $modelClass = 'app\models\User';
    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];

    public function actionLogin() {
        $ok = 0;
        //$post = $_POST;
        $post = $_REQUEST;
        if (empty($post) or empty($post['username']) or empty($post['password'])) {
            //TODO Unable to locate message source for category 'Global'?
            //$msg = \Yii::t('Global', 'Wrong Params');
            $msg = 'Wrong Params';
            //TODO class 'CJSON' not found?
            //echo CJson::encode(compact('ok', 'msg'));
            echo json_encode(compact('ok', 'msg'));
            exit;
        }

        //var_dump($post);exit;
        /*$model = new LoginForm();
        if ($model->load($post) && $model->login()) {
        //if ($model->load(Yii::$app->request->post()) && $model->login()) {
            $ok = 1;
            $msg = 'Login Success';
        }*/
        $query = new Query();
        $query->from('user')
            ->where('name = :username', [':username' => $post['username']])
            ->select('*');
        $command = $query->createCommand();
        $record = $command->queryOne();
        //var_dump($record['password'], $post['password']);exit;
        if ($record['password'] == Util::hashPassword($post['password'])) {
            $ok = 1;
            $msg = 'Login Success';
        }

        echo json_encode(compact('ok', 'msg'));
        exit;
    }

    public function actionRegister() {
        $ok = 0;
        //$post = $_POST;
        $post = $_REQUEST;
        if (empty($post)) {
            echo json_encode(compact('ok', 'msg'));
            exit;
        }

        $generateString = Util::generateString(6);

        if ($post['username'] && $post['email'] && $post['password']) {
            $email = trim($post['email']);
            if (strlen($post['password']) < 6 || strlen($post['password']) > 20) {
                //$msg = Util::t('passwordLength');
                $msg = 'password length';
                echo json_encode(compact('ok', 'msg'));
                exit();
            }
            $password = Util::hashPassword(trim($post['password']));
            //用户名是否注册
            $model = new $this->modelClass;
            $member_res = $model
                ->find()
                ->where('name = :name', array(':name' => $post['username']))
                ->one();

            if ($member_res) {
                $msg = 'usernameIsHave';
                echo json_encode(compact('ok', 'msg'));
                exit();
            }
            //邮箱是否注册
            $member_res = $model
                ->find()
                ->where('email = :email', array(':email' => $post['email']))
                ->one();
            if ($member_res) {
                $msg = 'emailIsHave';
                echo json_encode(compact('ok', 'msg'));
                exit();
            }
            $userName = trim($post['username']);

            $memberRegister = new $model();
            $memberRegister->name = $userName;
            $memberRegister->email = $email;
            $memberRegister->password = $password;
            $res = $memberRegister->insert();
            $ok = 1;
            $msg = 'registerDone';
        }

        echo json_encode(compact('ok', 'msg'));
        exit;
    }

}