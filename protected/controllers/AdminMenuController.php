<?php
class AdminMenuController extends AdminController {
 private function location($location){if(!is_string($location)||!isset(MenuItem::locations()[$location]))throw new CHttpException(400,'محل نمایش معتبر نیست.');return $location;}
 private function load($id){if(!is_scalar($id)||!ctype_digit((string)$id))throw new CHttpException(400);$model=MenuItem::model()->findByPk($id);if(!$model)$this->notFound();return $model;}
 public function actionIndex($location='header',$id=null){
  $location=$this->location($location);$model=$id!==null?$this->load($id):new MenuItem;
  if(!$model->isNewRecord&&$model->location!==$location)$this->notFound();
  if($model->isNewRecord){$model->location=$location;$model->is_active=1;$model->sort_order=0;}
  if(isset($_POST['MenuItem'])){
   if(!is_array($_POST['MenuItem']))throw new CHttpException(400);
   $attributes=$_POST['MenuItem'];if(isset($attributes['location'])&&$attributes['location']!==$location)throw new CHttpException(422,'محل نمایش منو قابل تغییر نیست.');
   $model->attributes=$attributes;$model->location=$location;$model->parent_id=$model->parent_id?:null;
   try{if(Hierarchy::save($model,Yii::app()->request->getPost('tree_revision',''))){$this->flash('منو ذخیره شد.');$this->redirect(array('adminMenu/index','location'=>$location,'id'=>$model->id),false,303);return;}}
   catch(Exception $e){$model->addError('label',$e->getMessage());}
  }
  $this->pageTitle='مدیریت منوها';$this->render('//admin/menus/index',array('model'=>$model,'location'=>$location,'treeData'=>HierarchyTreeQuery::data('menu_items',$location,$model->id)));
 }
 public function actionCreate($location='header'){$this->actionIndex($location);}
 public function actionUpdate($id){$model=$this->load($id);$this->actionIndex($model->location,$id);}
 public function actionMove($location='header'){
  $this->requirePost();$service=new HierarchyTreeService('menu_items',$this->location($location));$request=Yii::app()->request;
  try{$id=$request->getPost('id');$target=$request->getPost('target',0);$position=$request->getPost('position');$selected=$request->getPost('selected',0);
   if(!is_scalar($id)||!ctype_digit((string)$id)||!is_scalar($target)||!ctype_digit((string)$target)||!is_scalar($selected)||!ctype_digit((string)$selected)||!is_string($position))throw new CHttpException(422,'داده جابه‌جایی معتبر نیست.');
   $revision=$service->move((int)$id,(int)$target,$position,$request->getPost('revision'));
   $model=$selected?MenuItem::model()->findByAttributes(array('id'=>$selected,'location'=>$location)):null;
   $this->json(array('ok'=>true,'revision'=>$revision,'options'=>$service->options((int)$selected),'selected'=>$model?array('id'=>(int)$model->id,'parent'=>(int)$model->parent_id,'sort'=>(int)$model->sort_order):null));
  }catch(Exception $e){$this->json(array('ok'=>false,'error'=>$e->getMessage()),$e instanceof CHttpException?$e->statusCode:422);}
 }
 public function actionDelete($id){
  $this->requirePost();$model=$this->load($id);$location=$model->location;$db=Yii::app()->db;$tx=$db->beginTransaction();
  try{if($db->driverName==='mysql')$db->createCommand('SELECT id FROM menu_items ORDER BY id FOR UPDATE')->queryAll();else $db->createCommand('UPDATE menu_items SET sort_order=sort_order')->execute();
   $service=new HierarchyTreeService('menu_items',$location);$revision=Yii::app()->request->getPost('tree_revision');
   if(!is_string($revision)||!hash_equals($service->revision(),$revision))throw new CHttpException(409,'ساختار منو تغییر کرده است؛ صفحه را تازه کنید.');
   if(MenuItem::model()->exists('parent_id=:id',array(':id'=>$id))){$this->flash('ابتدا زیرمنوها را جابه‌جا کنید.','error');$tx->commit();$this->redirect(array('adminMenu/index','location'=>$location,'id'=>$id),false,303);return;}
   $model->delete();Audit::record('menu_items',$id,'delete');$tx->commit();$this->flash('منو حذف شد.');$this->redirect(array('adminMenu/index','location'=>$location),false,303);
  }catch(Exception $e){if($tx->active)$tx->rollback();throw $e;}
 }
 private function json($value,$status=200){http_response_code($status);header('Content-Type: application/json; charset=utf-8');echo CJSON::encode($value);Yii::app()->end();}
}
