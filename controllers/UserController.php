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
use app\components\Util;
use yii\rest\ActiveController;

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
        $request = Yii::$app->request;
        $username = $request->post('username');
        $password = $request->post('password');

        if (empty($username) or empty($password)) {
            $this->setHeader(400);
            echo json_encode(array('status' => 0, 'error_code' => 400, 'message' => 'Missing params'),
                JSON_PRETTY_PRINT);
            exit;
        }

        $db = Yii::$app->db;
        $projectName = $request->post('gbid');
        if (empty($projectName)) {
            $sql = "SELECT * FROM user WHERE name = :username";
            $params = [
                ':username' => $username,
            ];
            $record = $db->createCommand($sql, $params)->queryOne();
        } else {
            $sql = "SELECT * FROM user u
                JOIN project p ON p.id = u.project_id
                WHERE u.name = :username AND p.name = :projectName";
            $params = [
                ':username' => $username,
                ':projectName' => $projectName,
            ];
            $record = $db->createCommand($sql, $params)->queryOne();
        }

        if (empty($record)) {
            $this->setHeader(400);
            echo json_encode(array('status' => 0, 'error_code' => 400, 'message' => 'No such user'),
                JSON_PRETTY_PRINT);
            exit;
        }

        if ($record['password'] != Util::hashPassword($password)) {
            $this->setHeader(400);
            echo json_encode(array('status' => 0, 'error_code' => 400, 'message' => 'Wrong password'),
                JSON_PRETTY_PRINT);
            exit;
        }

        $token = Util::generateToken($username);
        $expires = time() + 86400;
        $lastLogin = date('Y-m-d H:i:s');
        $loginTimes = $record['login_times'] + 1;
        $sql = "UPDATE user SET token = :token, expires = :expires, last_login = :lastLogin,
                login_times = :loginTimes
                WHERE id = :userId";
        $params = [
            ':token' => $token,
            ':expires' => $expires,
            ':lastLogin' => $lastLogin,
            ':loginTimes' => $loginTimes,
            ':userId' => $record['id'],
        ];
        $db->createCommand($sql, $params)->execute();

        $data = [
            'user_id' => $record['id'],
            'username' => $username,
            'token' => $token,
            'avatar' => $record['avatar'],
            'project_id' => $record['project_id'],
            'last_login' => $lastLogin,
            'login_times' => $loginTimes,
        ];

        $this->setHeader(200);
        echo json_encode(array('status' => 1, 'data' => $data, 'message' => 'Login success'), JSON_PRETTY_PRINT);
        exit;
    }

    public function actionRegister()
    {
        $request = Yii::$app->request;
        $username = $request->post('username');
        $email = $request->post('email');
        $password = $request->post('password');
        if (empty($username) or empty($email) or empty($password)) {
            $this->setHeader(400);
            echo json_encode(array('status' => 0, 'error_code' => 400, 'message' => 'Missing params'),
                JSON_PRETTY_PRINT);
            exit;
        }

        $db = Yii::$app->db;
        $projectName = $request->post('gbid');
        if (!empty($projectName)) {
            $sql = "SELECT id FROM project WHERE name = :projectName";
            $params = [
                ':projectName' => $projectName,
            ];
            $projectId = $db->createCommand($sql, $params)->queryScalar();
        }
        if (empty($projectId)) {
            $projectId = 0;
        }

        if (strlen($password) < 6 or strlen($password) > 20) {
            $this->setHeader(400);
            echo json_encode(array('status' => 0, 'error_code' => 400, 'message' => 'Password length must in [6, 20]'),
                JSON_PRETTY_PRINT);
            exit;
        }

        $username = trim($username);
        $sql = "SELECT 1 FROM user WHERE name = :name";
        $params = [
            ':name' => $username,
        ];
        $usernameExist = $db->createCommand($sql, $params)->queryScalar();
        if ($usernameExist) {
            $this->setHeader(400);
            echo json_encode(array('status' => 0, 'error_code' => 400, 'message' => 'Username exists'),
                JSON_PRETTY_PRINT);
            exit;
        }

        $email = trim($email);
        $sql = "SELECT 1 FROM user WHERE email = :email";
        $params = [
            ':email' => $email,
        ];
        $emailExist = $db->createCommand($sql, $params)->queryScalar();
        if ($emailExist) {
            $this->setHeader(400);
            echo json_encode(array('status' => 0, 'error_code' => 400, 'message' => 'Email exists'),
                JSON_PRETTY_PRINT);
            exit;
        }

        $password = Util::hashPassword(trim($password));
        $sql = "INSERT user (name, password, email, project_id) VALUES (:name, :password, :email, :projectId)";
        $params = [
            ':name' => $username,
            ':password' => $password,
            ':email' => $email,
            ':projectId' => $projectId,
        ];
        $db->createCommand($sql, $params)->execute();

        $sql = "SELECT id user_id, name user_name, project_id FROM user WHERE name = :name";
        $params = [
            ':name' => $username,
        ];
        $data = $db->createCommand($sql, $params)->queryOne();

        $this->setHeader(200);
        echo json_encode(array('status' => 1, 'data' => $data, 'message' => 'Register success'), JSON_PRETTY_PRINT);
        exit;
    }

    public function actionAvatar()
    {
        $request = Yii::$app->request;
        $userId = $request->post('user_id');
        $token = $request->post('token');
        $picData = $request->post('data');

        if (!$this->checkToken($userId, $token)) {
            $this->setHeader(400);
            echo json_encode(array('status' => 0, 'error_code' => 400, 'message' => 'Invalid token'),
                JSON_PRETTY_PRINT);
            exit;
        }

        $saveDir = __DIR__ . '/avatar/';
        if (!file_exists($saveDir)) {
            mkdir($saveDir);
        }
        $fileName = $saveDir . $userId . '_' . time() . '.jpg';
        $fp2 = fopen($fileName, 'w');
        fwrite($fp2, $picData);
        fclose($fp2);

        $db = Yii::$app->db;
        $sql = "UPDATE user SET avatar = :path WHERE id = :id";
        $params = [
            ':path' => $fileName,
            ':id' => $userId,
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
        $userId = $request->post('user_id');
        $token = $request->post('token');
        $newName = $request->post('new_name');

        if (!$this->checkToken($userId, $token)) {
            $this->setHeader(400);
            echo json_encode(array('status' => 0, 'error_code' => 400, 'message' => 'Invalid token'),
                JSON_PRETTY_PRINT);
            exit;
        }

        $db = Yii::$app->db;
        $sql = "UPDATE user SET name = :name WHERE id = :userId";
        $params = [
            ':name' => $newName,
            ':userId' => $userId,
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

    public function actionShow()
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
        ob_start();
        echo file_get_contents($url);
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

    private function checkToken($userId, $token) {
        if (empty($token)) {
            return false;
        }

        if (! preg_match('/^[0-9A-F]{40}$/i', $token)) {
            return false;
        }

        $sql = "SELECT token, expires FROM user WHERE id = :userId";
        $params = [
            ':userId' => $userId,
        ];
        $record = Yii::$app->db->createCommand($sql, $params)->queryOne();

        if (empty($record)) {
            return false;
        }

        if (time() > $record['expires']) {
            return false;
        }

        return true;
    }

}