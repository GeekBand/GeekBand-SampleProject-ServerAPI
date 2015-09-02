<?php
/**
 *  ____              _   ____             _
 *|  _ \  ___  _ __ | |_|  _ \ __ _ _ __ (_) ___
 *| | | |/ _ \| '_ \| __| |_) / _` | '_ \| |/ __|
 *| |_| | (_) | | | | |_|  __/ (_| | | | | | (__
 *|____/ \___/|_| |_|\__|_|   \__,_|_| |_|_|\___|
 *
 * Author: diaosj
 * Date: 15/8/15
 * Time: 上午9:19
 */

namespace app\controllers;

use Yii;
use app\models\Picture;
use yii\web\Controller;
use yii\filters\VerbFilter;

class PictureController extends Controller
{

    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'view' => ['get'],
                    'create' => ['post'],
                    'update' => ['post'],
                    'delete' => ['delete'],
                    'read' => ['get'],
                ],
            ]
        ];
    }

    public function beforeAction($event)
    {
        $action = $event->id;
        if (isset($this->actions[$action])) {
            $verbs = $this->actions[$action];
        } elseif (isset($this->actions['*'])) {
            $verbs = $this->actions['*'];
        } else {
            return $event->isValid;
        }
        $verb = Yii::$app->getRequest()->getMethod();

        $allowed = array_map('strtoupper', $verbs);

        if (!in_array($verb, $allowed)) {
            $this->setHeader(400);
            echo json_encode(array('status' => 0, 'error_code' => 400, 'message' => 'Method not allowed'),
                JSON_PRETTY_PRINT);
            exit;
        }

        return true;
    }

    public function actionView()
    {
        $nodeId = Yii::$app->request->get('node');
        if (empty($nodeId)) {
            $this->setHeader(400);
            echo json_encode(array('status' => 0, 'error_code' => 400, 'message' => 'Bad request'), JSON_PRETTY_PRINT);
            exit;
        }

        $sql = "SELECT * FROM picture WHERE node_id = $nodeId";
        $pictures = Yii::$app->db->createCommand($sql)->queryAll();

        $this->setHeader(200);
        echo json_encode(array('status' => 1, 'data' => $pictures), JSON_PRETTY_PRINT);
        exit;
    }

    public function actionCreate()
    {
        $request = Yii::$app->request;
        $userId = $request->post('user_id');
        $token = $request->post('token');

        if (!$this->checkToken($userId, $token)) {
            $this->setHeader(400);
            echo json_encode(array('status' => 0, 'error_code' => 400, 'message' => 'Invalid token'),
                JSON_PRETTY_PRINT);
            exit;
        }

        $longitude = $request->post('longitude');
        $latitude = $request->post('latitude');
        $data = $request->post('data');

        $sql = "SELECT id FROM node WHERE geom = GeomFromText('Point($longitude $latitude)')";
        $nodeId = Yii::$app->db->createCommand($sql)->queryScalar();

        if (empty($nodeId)) {
            $createSql = "INSERT node (geom) VALUES (GeomFromText('Point($longitude $latitude)'))";
            Yii::$app->db->createCommand($createSql)->execute();
            $nodeId = Yii::$app->db->createCommand($sql)->queryScalar();
        }

        $img = $data;
        $save_dir = __DIR__ . '/' . $nodeId . '/';
        if (!file_exists($save_dir)) {
            mkdir($save_dir);
        }
        $fileName = $save_dir . $userId . '_' . time() . '.jpg';
        $fp2 = fopen($fileName, 'w');
        fwrite($fp2, $img);
        fclose($fp2);

        $title = $request->post('title');
        $model = new Picture();
        $model->node_id = $nodeId;
        $model->user_id = $userId;
        $model->title = $title;
        $model->pic_link = $fileName;

        if ($model->save()) {
            $this->setHeader(200);
            echo json_encode(array('status' => 1, 'data' => [
                'node_id' => $model->node_id,
                'pic_id' => $model->id,
                'title' => $model->title,
            ]),
                JSON_PRETTY_PRINT);
            exit;
        } else {
            $this->setHeader(400);
            echo json_encode(array('status' => 0, 'error_code' => 400, 'errors' => $model->errors), JSON_PRETTY_PRINT);
            exit;
        }
    }

    public function actionRead()
    {
        $request = Yii::$app->request;
        $userId = $request->get('user_id');
        $token = $request->get('token');

        if (!$this->checkToken($userId, $token)) {
            $this->setHeader(400);
            echo json_encode(array('status' => 0, 'error_code' => 400, 'message' => 'Invalid token'),
                JSON_PRETTY_PRINT);
            exit;
        }

        $id = $request->get('pic_id');
        if (empty($id)) {
            $this->setHeader(400);
            echo json_encode(array('status' => 0, 'error_code' => 400, 'errors' => 'no picture'), JSON_PRETTY_PRINT);
            exit;
        }

        $sql = "SELECT pic_link FROM picture WHERE id = $id";
        $url = Yii::$app->db->createCommand($sql)->queryScalar();

        if (empty($url)) {
            $this->setHeader(400);
            echo json_encode(array('status' => 0, 'error_code' => 400, 'errors' => 'no picture'), JSON_PRETTY_PRINT);
            exit;
        }

        //todo 区分？
        $basename = basename($url);
        $urlPath = str_replace($basename, '', $url);
        $url = $urlPath . urlencode($basename);

        header('Content-Type: image/jpeg');
        ob_start();
        echo file_get_contents($url);
        exit;
    }

    protected function findModel($condition)
    {
        if (($model = Picture::findOne($condition)) !== null) {
            return $model;
        } else {
            $this->setHeader(400);
            echo json_encode(array('status' => 0, 'error_code' => 400, 'message' => 'Bad request'), JSON_PRETTY_PRINT);
            exit;
        }
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