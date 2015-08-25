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

//namespace app\modules\api\controllers;
namespace app\controllers;

use Yii;
use app\models\Comment;
//use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\db\Query;

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

        $sql = "SELECT * FROM `comment` WHERE 1";

        $node = $request->get('node');
        if ($node) {
            $sql .= " AND node_id = $node ";
        }

        $countSql = "SELECT COUNT(*) FROM `comment` WHERE 1";
        if ($node) {
            $countSql .= " AND node_id = $node";
        }
        $totalItems = Yii::$app->db->createCommand($countSql)->queryScalar();

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

    }

    public function actionView($id)
    {

        $model = $this->findModel($id);

        $this->setHeader(200);
        echo json_encode(array('status' => 1, 'data' => array_filter($model->attributes)), JSON_PRETTY_PRINT);

    }

    public function actionCreate()
    {
        $params = $_POST;

        $model = new Comment();
        $model->attributes = $params;


        if ($model->save()) {

            $this->setHeader(200);
            echo json_encode(array('status' => 1, 'data' => array_filter($model->attributes)), JSON_PRETTY_PRINT);

        } else {
            $this->setHeader(400);
            echo json_encode(array('status' => 0, 'error_code' => 400, 'errors' => $model->errors), JSON_PRETTY_PRINT);
        }

    }

    public function actionUpdate($id)
    {
        $params = $_REQUEST;

        $model = $this->findModel($id);

        $model->attributes = $params;
        if ($model->save()) {

            $this->setHeader(200);
            echo json_encode(array('status' => 1, 'data' => array_filter($model->attributes)), JSON_PRETTY_PRINT);

        } else {
            $this->setHeader(400);
            echo json_encode(array('status' => 0, 'error_code' => 400, 'errors' => $model->errors), JSON_PRETTY_PRINT);
        }

    }

    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        if ($model->delete()) {
            $this->setHeader(200);
            echo json_encode(array('status' => 1, 'data' => array_filter($model->attributes)), JSON_PRETTY_PRINT);

        } else {

            $this->setHeader(400);
            echo json_encode(array('status' => 0, 'error_code' => 400, 'errors' => $model->errors), JSON_PRETTY_PRINT);
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
}