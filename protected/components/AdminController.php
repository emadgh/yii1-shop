<?php
class AdminController extends AppController
{
    public $layout='//layouts/admin';
    protected function beforeAction($action){
      $user=AccessService::current();if($user&&!in_array($user->role,array('admin','superadmin'),true)){
        $allowed=array('content'=>array('adminDashboard','adminPost','adminMedia','adminTag','adminCategory','adminCollection','adminCatalog','adminMenu','adminSecurity'),'sales'=>array('adminDashboard','adminOrder','adminSecurity'),'warehouse'=>array('adminDashboard','adminReport','adminSecurity'));
        if(!in_array($this->id,$allowed[$user->role]??array(),true))throw new CHttpException(403,'برای این بخش دسترسی ندارید.');
        if($this->id==='adminDashboard'){$this->redirect(array($user->role==='content'?'adminPost/index':($user->role==='sales'?'adminOrder/index':'adminReport/index')));return false;}
      }return parent::beforeAction($action);
    }
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
