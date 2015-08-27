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
use app\models\Picture;
//use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\db\Query;

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
        $longitude = $request->post('longtitude');
        $latitude = $request->post('latitude');
        $data = $request->post('data');

        $sql = "select id from node where geom = GeomFromText('Point($longitude $latitude)')";
        $nodeId = Yii::$app->db->createCommand($sql)->queryScalar();

        if (empty($nodeId)) {
            $createSql = "INSERT node (geom) VALUES (GeomFromText('Point($longitude $latitude)'))";
            Yii::$app->db->createCommand($createSql)->execute();
            $nodeId = Yii::$app->db->createCommand($sql)->queryScalar();
        }

        //picture
        $img = $data;
        $save_dir = __DIR__ . '/' . $nodeId . '/';
        if ( ! file_exists($save_dir)) {
            mkdir($save_dir);
        }
        $fileName = $save_dir . $userId . '_' . time() . '.jpg';
        $fp2=fopen($fileName,'w');
        fwrite($fp2,$img);
        fclose($fp2);

        $model = new Picture();
        $model->node_id = $nodeId;
        $model->user_id = $userId;
        $model->pic_link = $fileName;


        if ($model->save()) {
            $this->setHeader(200);
            echo json_encode(array('status' => 1, 'data' => ['node_id' => $model->node_id, 'pic_id' => $model->id]), JSON_PRETTY_PRINT);
            exit;

        } else {
            $this->setHeader(400);
            echo json_encode(array('status' => 0, 'error_code' => 400, 'errors' => $model->errors), JSON_PRETTY_PRINT);
            exit;
        }

    }

    public function actionRead()
    {
        $id = Yii::$app->request->get('pic_id');

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

        header('Content-Type: image/jpeg');
        ob_start();//打开输出缓冲区，也就是暂时不允许输出
        echo file_get_contents($url);//读一个文件写入到输出缓冲
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
}