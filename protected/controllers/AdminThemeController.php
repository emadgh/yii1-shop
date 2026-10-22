<?php
class AdminThemeController extends AdminController {
 public function accessRules() { return array(array('allow','expression'=>'AccessService::isSuperAdmin()'),array('deny','users'=>array('*'))); }
 public function actionIndex() { $this->pageTitle='مدیریت قالب‌ها';$this->render('//admin/themes/index',array('themes'=>ThemeService::catalog(),'active'=>ThemeService::active())); }
 public function actionCustomize($key){
  if(!ThemeService::valid($key))$this->notFound();$error='';
  if(Yii::app()->request->isPostRequest){try{ThemeOptions::save($key,Yii::app()->request->getPost('options',array()),Yii::app()->request->getPost('blocks',array()));$this->flash('چیدمان قالب ذخیره شد.');$this->refresh();}catch(Exception $e){$error=$e->getMessage();}}
  $this->pageTitle='شخصی‌سازی قالب';$this->render('//admin/themes/customize',array('key'=>$key,'options'=>ThemeOptions::get($key),'blocks'=>ThemeOptions::blocks($key),'error'=>$error,'collections'=>CollectionQuery::options()));
 }
 public function actionActivate() {
   $this->requirePost();$key=Yii::app()->request->getPost('theme');ThemeService::activate($key);
   $this->flash('قالب «'.ThemeService::catalog()[$key]['title'].'» فعال شد.');$this->redirect(array('adminTheme/index'));
 }
}
