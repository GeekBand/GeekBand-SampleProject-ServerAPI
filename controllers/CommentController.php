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
use app\models\Comment;
use yii\web\Controller;
use yii\filters\VerbFilter;

class CommentController extends Controller
{

    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'index' => ['get'],
                    'view' => ['get'],
                    'create' => ['post'],
                    'update' => ['post'],
                    'delete' => ['delete'],
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

    public function actionIndex()
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

        $sql = "SELECT * FROM `comment` WHERE 1";
        $picId = $request->get('pic_id');
        if ($picId) {
            $sql .= " AND pic_id = $picId ";
        }

        $countSql = "SELECT COUNT(*) FROM `comment` WHERE 1";
        if ($picId) {
            $countSql .= " AND pic_id = $picId";
        }

        $db = Yii::$app->db;
        $totalItems = $db->createCommand($countSql)->queryScalar();

        $sort = $request->get('sort');
        if ($sort) {
            $order = $request->get('order');
            if (in_array(strtoupper($order), array('DESC', 'ASC'))){
                $sort .= " $order";
            }
            $sql .= $sort;
        }

        $page = $request->get('page');
        if (empty($page)) {
            $page = 1;
        }

        $limit = $request->get('limit');
        if (empty($limit)) {
            $limit = 10;
        }

        $offset = $limit * ($page - 1);

        $sql .= " LIMIT $offset, $limit";
        $models = Yii::$app->db->createCommand($sql)->queryAll();

        $this->setHeader(200);
        echo json_encode(array('status' => 1, 'msg' => 'Query Success', 'data' => $models, 'count' => $totalItems), JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function actionView()
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

        $model = $this->findModel($id);

        $this->setHeader(200);
        echo json_encode(array('status' => 1, 'data' => array_filter($model->attributes)), JSON_PRETTY_PRINT);
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

        $params = $_POST;

        $model = new Comment();
        $model->attributes = $params;

        if ($model->save()) {
            $this->setHeader(200);
            echo json_encode(array('status' => 1, 'data' => array_filter($model->attributes)), JSON_PRETTY_PRINT);
            exit;
        } else {
            $this->setHeader(400);
            echo json_encode(array('status' => 0, 'error_code' => 400, 'errors' => $model->errors), JSON_PRETTY_PRINT);
            exit;
        }
    }

    public function actionUpdate()
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

        $params = $_POST;
        $commentId = $request->post('comment_id');
        if (empty($commentId)) {
            $this->setHeader(400);
            echo json_encode(array('status' => 0, 'error_code' => 400, 'message' => 'No such comment'),
                JSON_PRETTY_PRINT);
            exit;
        }

        $model = $this->findModel($commentId);
        $model->attributes = $params;

        if ($model->save()) {
            $this->setHeader(200);
            echo json_encode(array('status' => 1, 'data' => array_filter($model->attributes)), JSON_PRETTY_PRINT);
            exit;
        } else {
            $this->setHeader(400);
            echo json_encode(array('status' => 0, 'error_code' => 400, 'errors' => $model->errors), JSON_PRETTY_PRINT);
            exit;
        }
    }

    public function actionDelete()
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

        $commentId = $request->post('comment_id');
        if (empty($commentId)) {
            $this->setHeader(400);
            echo json_encode(array('status' => 0, 'error_code' => 400, 'message' => 'No such comment'),
                JSON_PRETTY_PRINT);
            exit;
        }

        $model = $this->findModel($commentId);
        if ($model->user_id != $userId) {
            $this->setHeader(400);
            echo json_encode(array('status' => 0, 'error_code' => 400, 'message' => 'Cannot delete this comment'),
                JSON_PRETTY_PRINT);
            exit;
        }

        if ($model->delete()) {
            $this->setHeader(200);
            echo json_encode(array('status' => 1, 'data' => array_filter($model->attributes)), JSON_PRETTY_PRINT);
            exit;
        } else {
            $this->setHeader(400);
            echo json_encode(array('status' => 0, 'error_code' => 400, 'errors' => $model->errors), JSON_PRETTY_PRINT);
            exit;
        }
    }

    protected function findModel($id)
    {
        if (($model = Comment::findOne($id)) !== null) {
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