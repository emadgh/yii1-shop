<?php
class VisitController extends CController {
 public function actionTrack(){if(!Yii::app()->request->isPostRequest)throw new CHttpException(405);VisitService::record(Yii::app()->request->getPost('event'),Yii::app()->request->getPost('path'),Yii::app()->request->getPost('referrer',''),Yii::app()->request->userAgent);http_response_code(204);Yii::app()->end();}
}
