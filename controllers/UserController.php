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

use Yii;
use app\components\CJson;
use app\components\Util;
use yii\db\Query;
use yii\filters\auth\HttpBasicAuth;
use yii\helpers\ArrayHelper;
use yii\rest\ActiveController;
use yii\web\User;

class UserController extends ActiveController
{
    public $modelClass = 'app\models\User';
    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator']['formats'] = [
            'application/json' => 'json',
            //todo response not found?
            //'application/json' => Response::FORMAT_JSON,
        ];

        return $behaviors;
    }

    public function init()
    {
        parent::init();
        Yii::$app->user->enableSession = false;
    }

    public function actions()
    {
        $actions = parent::actions();

        unset($actions['delete'], $actions['create']);

        return $actions;
    }

    public function actionLogin()
    {
        $ok = 0;
        //TODO POST
        //$post = $_POST;
        $post = $_REQUEST;
        if (empty($post) or empty($post['username']) or empty($post['password'])) {
            //TODO Unable to locate message source for category 'Global'?
            //$msg = Yii::t('Global', 'Wrong Params');
            $msg = 'Wrong Params';
            //TODO class 'CJSON' not found?
            //echo CJson::encode(compact('ok', 'msg'));
            header("Content-Type: application/json");
            echo json_encode(compact('ok', 'msg'));
            exit;
        }

        if (empty($post['gbid'])) {
            $sql = "SELECT * FROM user WHERE name = :username";
            $params = [
                ':username' => $post['username'],
            ];
            $record = Yii::$app->db->createCommand($sql, $params)->queryOne();
        } else {
            $sql = "SELECT * FROM user u
                JOIN project p ON p.id = u.project_id
                WHERE u.name = :username AND p.name = :projectName";
            $params = [
                ':username' => $post['username'],
                ':projectName' => $post['gbid'],
            ];
            $record = Yii::$app->db->createCommand($sql, $params)->queryOne();
        }

        if ($record['password'] == Util::hashPassword($post['password'])) {
            $ok = 1;
            $msg = 'Login Success';
        }

        header("Content-Type: application/json");
        echo json_encode(compact('ok', 'msg'));
        exit;
    }

    public function actionRegister()
    {
        $ok = 0;
        //$post = $_POST;
        $post = $_REQUEST;
        if (empty($post)) {
            header("Content-Type: application/json");
            echo json_encode(compact('ok', 'msg'));
            exit;
        }

        if (!empty($post['gbid'])) {
            $sql = "SELECT id FROM project WHERE name = :projectName";
            $params = [
                ':projectName' => $post['gbid'],
            ];
            $projectId = Yii::$app->db->createCommand($sql, $params)->queryScalar();
        }
        if (empty($projectId)) {
            $projectId = 0;
        }

        $generateString = Util::generateString(6);

        if ($post['username'] && $post['email'] && $post['password']) {
            $email = trim($post['email']);
            if (strlen($post['password']) < 6 || strlen($post['password']) > 20) {
                //$msg = Util::t('passwordLength');
                $msg = 'password length';
                header("Content-Type: application/json");
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
                $msg = 'usernameExists';
                header("Content-Type: application/json");
                echo json_encode(compact('ok', 'msg'));
                exit();
            }
            //邮箱是否注册
            $member_res = $model
                ->find()
                ->where('email = :email', array(':email' => $post['email']))
                ->one();
            if ($member_res) {
                $msg = 'emailExists';
                header("Content-Type: application/json");
                echo json_encode(compact('ok', 'msg'));
                exit();
            }
            $userName = trim($post['username']);

            $memberRegister = new $model();
            $memberRegister->name = $userName;
            $memberRegister->email = $email;
            $memberRegister->password = $password;
            $memberRegister->project_id = $projectId;
            $memberRegister->created = date('Y-m-d H:i:s');
            $res = $memberRegister->insert();
            $ok = 1;
            $msg = 'registerSuccess';
        }

        header("Content-Type: application/json");
        echo json_encode(compact('ok', 'msg'));
        exit;
    }

    public function actionAvatar()
    {
        $request = Yii::$app->request;
        $userName = $request->post('username');
        $password = $request->post('password');
        $projectName = $request->post('gbid');
        $picData = $request->post('data');

        $db = Yii::$app->db;
        if (empty($projectName)) {
            $sql = "SELECT id, password FROM user WHERE name = :username";
            $params = [
                ':username' => $userName,
            ];
        } else {
            $sql = "SELECT u.id, u.password
                FROM user u
                JOIN project p ON p.id = u.project_id
                WHERE u.name = :username AND p.name = :projectName";
            $params = [
                ':username' => $userName,
                ':projectName' => $projectName,
            ];
        }
        $record = $db->createCommand($sql, $params)->queryOne();

        if (empty($record)) {
            $this->setHeader(400);
            echo json_encode(array('status' => 0, 'error_code' => 400, 'message' => 'No such user'),
                JSON_PRETTY_PRINT);
            exit;
        }

        if ($record['password'] != Util::hashPassword($password)) {
            $this->setHeader(400);
            echo json_encode(array('status' => 0, 'error_code' => 400, 'message' => 'Invalid password'),
                JSON_PRETTY_PRINT);
            exit;
        }

        $saveDir = __DIR__ . '/../images/avatar/';
        if (!file_exists($saveDir)) {
            mkdir($saveDir);
        }
        $fileName = $saveDir . $record['id'] . '_' . time() . '.jpg';
        $fp2 = fopen($fileName, 'w');
        fwrite($fp2, $picData);
        fclose($fp2);

        $sql = "UPDATE user SET avatar = :path WHERE id = :id";
        $params = [
            ':path' => $fileName,
            ':id' => $record['id'],
        ];
        $db->createCommand($sql, $params)->execute();

        $sql = "SELECT id user_id, name user_name, email, avatar FROM user WHERE id = :id AND avatar = :path";
        $record = $db->createCommand($sql, $params)->queryOne();

        if (empty($record)) {
            $this->setHeader(400);
            echo json_encode(array('status' => 1, 'error_code' => 400, 'message' => 'Database error'),
                JSON_PRETTY_PRINT);
            exit;
        }

        $this->setHeader(200);
        echo json_encode(array('status' => 1, 'data' => $record, 'message' => 'Update success'), JSON_PRETTY_PRINT);
        exit;
    }

    public function actionRename()
    {
        $request = Yii::$app->request;
        $userName = $request->post('username');
        $password = $request->post('password');
        $projectName = $request->post('gbid');
        $newName = $request->post('new_name');

        $db = Yii::$app->db;
        if (empty($projectName)) {
            $sql = "SELECT id, password FROM user WHERE name = :username";
            $params = [
                ':username' => $userName,
            ];
        } else {
            $sql = "SELECT u.id, u.password
                FROM user u
                JOIN project p ON p.id = u.project_id
                WHERE u.name = :username AND p.name = :projectName";
            $params = [
                ':username' => $userName,
                ':projectName' => $projectName,
            ];
        }
        $record = $db->createCommand($sql, $params)->queryOne();

        if (empty($record)) {
            $this->setHeader(400);
            echo json_encode(array('status' => 0, 'error_code' => 400, 'message' => 'No such user'),
                JSON_PRETTY_PRINT);
            exit;
        }

        if ($record['password'] != Util::hashPassword($password)) {
            $this->setHeader(400);
            echo json_encode(array('status' => 0, 'error_code' => 400, 'message' => 'Invalid password'),
                JSON_PRETTY_PRINT);
            exit;
        }

        $sql = "UPDATE user SET name = :name WHERE id = :userId";
        $params = [
            ':name' => $newName,
            ':userId' => $record['id'],
        ];
        $db->createCommand($sql, $params)->execute();

        $sql = "SELECT id user_id, name user_name, email, avatar FROM user WHERE id = :userId AND name = :name";
        $record = $db->createCommand($sql, $params)->queryOne();

        if (empty($record)) {
            $this->setHeader(400);
            echo json_encode(array('status' => 1, 'error_code' => 400, 'message' => 'Database error'),
                JSON_PRETTY_PRINT);
            exit;
        }

        $this->setHeader(200);
        echo json_encode(array('status' => 1, 'data' => $record, 'message' => 'Update success'), JSON_PRETTY_PRINT);
        exit;
    }

    public function actionShowAvatar()
    {
        $id = Yii::$app->request->get('user_id');

        if (empty($id)) {
            $this->setHeader(400);
            echo json_encode(array('status' => 0, 'error_code' => 400, 'errors' => 'Wrong params'), JSON_PRETTY_PRINT);
            exit;
        }

        $sql = "SELECT avatar FROM user WHERE id = :id";
        $params = [
            ':id' => $id,
        ];
        $url = Yii::$app->db->createCommand($sql, $params)->queryScalar();

        if (empty($url)) {
            $this->setHeader(400);
            echo json_encode(array('status' => 0, 'error_code' => 400, 'errors' => 'No picture'), JSON_PRETTY_PRINT);
            exit;
        }

        header('Content-Type: image/jpeg');
        ob_start();//打开输出缓冲区，也就是暂时不允许输出
        echo file_get_contents($url);//读一个文件写入到输出缓冲
        exit;
    }

    private function setHeader($status)
    {
        $status_header = 'HTTP/1.1 ' . $status . ' ' . $this->_getStatusCodeMessage($status);
        $content_type = "application/json; charset=utf-8";

        header($status_header);
        header('Content-type: ' . $content_type);
    }

    private function _getStatusCodeMessage($status)
    {
        $codes = Array(
            200 => 'OK',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
        );
        return (isset($codes[$status])) ? $codes[$status] : '';
    }

}