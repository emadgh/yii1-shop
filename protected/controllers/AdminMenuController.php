<?php
class AdminMenuController extends AdminController {
 public function actionIndex() { $this->pageTitle='مدیریت منو';$this->render('//admin/menus/index'); }
 public function actionCreate(){$this->edit(new MenuItem);}
 public function actionUpdate($id){$this->edit($this->load($id));}
 private function load($id){$m=MenuItem::model()->findByPk($id);if(!$m)$this->notFound();return $m;}
 private function edit($model){if(isset($_POST['MenuItem'])){$model->attributes=$_POST['MenuItem'];$model->parent_id=$model->parent_id?:null;if(Hierarchy::save($model)){$this->flash('منو ذخیره شد.');$this->redirect(array('adminMenu/index'));}}$this->pageTitle='ویرایش منو';$parents=array();foreach(Hierarchy::flatten(Hierarchy::rows('menu_items')) as $row)if((int)$row['id']!==(int)$model->id)$parents[$row['id']]=str_repeat('— ',$row['_depth']).$row['label'].' ('.$row['location'].')';$this->render('//admin/menus/form',array('model'=>$model,'parents'=>$parents));}
 public function actionDelete($id){$this->requirePost();$m=$this->load($id);$db=Yii::app()->db;$tx=$db->beginTransaction();try{$db->createCommand()->update('menu_items',array('parent_id'=>$m->parent_id),'parent_id=:id',array(':id'=>$id));$m->delete();$tx->commit();}catch(Exception $e){$tx->rollback();throw $e;}$this->redirect(array('adminMenu/index'));}
}
