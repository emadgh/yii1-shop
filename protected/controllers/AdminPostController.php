<?php
class AdminPostController extends AdminController {
 public function actionIndex() { $this->pageTitle='مدیریت بلاگ';$this->render('//admin/posts/index'); }
 public function actionCreate(){ $this->edit(new Post); }
 public function actionUpdate($id){ $this->edit($this->load($id)); }
 private function edit($model){
   $error='';$tagsText=$model->isNewRecord?'':implode(', ',array_map(function($t){return $t->name;},$model->tags));$selected=$model->cover_image?Yii::app()->db->createCommand('SELECT id FROM media WHERE filename=:p')->queryScalar(array(':p'=>$model->cover_image)):'';
   if(isset($_POST['Post']) && is_array($_POST['Post'])){
     $model->attributes=$_POST['Post'];$tagsText=(string)Yii::app()->request->getPost('tags','');$selected=(string)Yii::app()->request->getPost('cover_media','');
     try { PostWriteService::save($model,$_POST['Post'],$tagsText,$selected,Yii::app()->request->getPost('media_context',''));$this->flash('نوشته ذخیره شد.');$this->redirect(array('adminPost/index'));
     }catch(Exception $e){$error=$e->getMessage();}
   }
   $this->pageTitle=$model->isNewRecord?'نوشته جدید':'ویرایش نوشته';$this->render('//admin/posts/form',compact('model','tagsText','selected','error'));
 }
 public function actionDelete($id){$this->requirePost();$this->load($id)->delete();$this->flash('نوشته حذف شد؛ تصویر در کتابخانه باقی ماند.');$this->redirect(array('adminPost/index'));}
 private function load($id){$m=Post::model()->findByPk($id);if(!$m)$this->notFound();return $m;}
}
