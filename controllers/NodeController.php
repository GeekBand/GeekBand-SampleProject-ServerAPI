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
use yii\web\Controller;
use yii\filters\VerbFilter;

class NodeController extends Controller
{

    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'index' => ['get'],
                    'list' => ['get'],
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

    public function actionList()
    {
        $request = Yii::$app->request;
        $longitude = $request->get('longitude');
        $latitude = $request->get('latitude');
        $distance = $request->get('distance');
        if (empty($longitude) OR empty($latitude)) {
            $this->setHeader(400);
            echo json_encode(array('status' => 0, 'error_code' => 400, 'message' => 'Wrong params'),
                JSON_PRETTY_PRINT);
            exit;
        }

        if (empty($distance)) {
            $distance = 1;
        }

        $db = Yii::$app->db;
        $sql = "SELECT id, ST_Distance_Sphere(ST_GeomFromText('Point($longitude $latitude)'), geom) distance_in_meters,
            tags, ST_AsText(geom)
            FROM node
            WHERE ST_Distance_Sphere(ST_GeomFromText('Point($longitude $latitude)'), geom) <= $distance
            ORDER BY distance_in_meters LIMIT 10";
        /*
         * TODO
         * ST_Contains( ST_MakeEnvelope(
                    Point(($longitude+(20/111)) ($latitude+(20/111))),
                    Point(($longitude-(20/111)) ($latitude-(20/111)))
                 ), geom )
            AND ST_Distance_Sphere(Point($longitude $latitude), geom) <= $distance
            ORDER BY distance_in_meters LIMIT 10
         */
        //            AND match(tags) against ("+thai +restaurant" IN BOOLEAN MODE)
        $nodes = $db->createCommand($sql)->queryAll();
        if (empty($nodes)) {
            $this->setHeader(400);
            echo json_encode(array('status' => 0, 'error_code' => 400, 'message' => 'No nodes'),
                JSON_PRETTY_PRINT);
            exit;
        }

        $nodeIds = [];
        $results = [];
        foreach ($nodes as $node) {
            array_push($nodeIds, $node['id']);
            $results[$node['id']] = [];
            $results[$node['id']]['node'] = $node;
            $results[$node['id']]['comments'] = [];
        }

        $nodeStr = implode(',', $nodeIds);
        $sql = "SELECT node_id, user_id, comment FROM comment WHERE node_id IN ($nodeStr)";
        $comments = $db->createCommand($sql)->queryAll();

        foreach ($comments as $comment) {
            array_push($results[$comment['node_id']], $comment);
        }

        $this->setHeader(200);
        echo json_encode(array('status' => 1, 'data' => $results, JSON_UNESCAPED_UNICODE));
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