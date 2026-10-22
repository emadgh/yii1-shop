<?php
class AdminController extends AppController
{
    public $layout='//layouts/admin';
    public function filters() { return array('accessControl'); }
    public function accessRules() {
      return array(
        array('allow','expression'=>'AccessService::isAdmin()'),
        array('deny','users'=>array('*')),
      );
    }
    public function requirePost() {
      if(!Yii::app()->request->isPostRequest) throw new CHttpException(405,'درخواست باید POST باشد.');
    }
    protected function flash($msg,$kind='success') { Yii::app()->user->setFlash($kind,$msg); }
}
