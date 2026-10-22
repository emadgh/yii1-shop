<?php
class AdminCategoryController extends AdminController {
 public function actionIndex($id=null){
  if($id!==null&&(!is_scalar($id)||!ctype_digit((string)$id)||(int)$id<1))throw new CHttpException(400);
  $model=$id?$this->load($id):new Category;
  if(isset($_POST['Category'])){
   if(!is_array($_POST['Category']))throw new CHttpException(400);
   $model->attributes=$_POST['Category'];$model->parent_id=$model->parent_id?:null;
   try{CmsMedia::bind($model,Yii::app()->request->getPost('image_id',''));if(Hierarchy::save($model,Yii::app()->request->getPost('tree_revision',''))){$this->flash('دسته‌بندی ذخیره شد.');$this->redirect(array('adminCategory/index','id'=>$model->id),false,303);return;}}
   catch(Exception $e){$model->addError('name',$e->getMessage());}
  }
  $this->pageTitle='دسته‌بندی محصولات';$this->render('//admin/categories/index',array('model'=>$model));
 }
 public function actionCreate(){if(Yii::app()->request->isPostRequest){$this->actionIndex();return;}$this->redirect(array('adminCategory/index'));}
 public function actionUpdate($id){if(Yii::app()->request->isPostRequest){$this->actionIndex($id);return;}$this->redirect(array('adminCategory/index','id'=>$id));}
 public function actionMove(){
  $this->requirePost();$request=Yii::app()->request;
  try{$id=$request->getPost('id');$target=$request->getPost('target',0);$position=$request->getPost('position');if(!is_scalar($id)||!ctype_digit((string)$id)||!is_scalar($target)||!ctype_digit((string)$target)||!is_string($position))throw new CHttpException(422,'داده جابه‌جایی معتبر نیست.');CategoryTreeService::move((int)$id,(int)$target,$position,$request->getPost('revision'));$selected=(int)$request->getPost('selected',0);$model=$selected?Category::model()->findByPk($selected):null;$this->json(array('ok'=>true,'revision'=>CategoryTreeService::revision(),'options'=>Category::options($selected),'selected'=>$model?array('id'=>(int)$model->id,'parent'=>(int)$model->parent_id,'sort'=>(int)$model->sort_order):null));}
  catch(Exception $e){$this->json(array('ok'=>false,'error'=>$e->getMessage()),$e instanceof CHttpException?$e->statusCode:422);}
 }
 public function actionDelete($id){
  $this->requirePost();$model=$this->load($id);$db=Yii::app()->db;$tx=$db->beginTransaction();
  try{if($db->driverName==='mysql')$db->createCommand('SELECT id FROM categories ORDER BY id FOR UPDATE')->queryAll();else $db->createCommand('UPDATE categories SET sort_order=sort_order')->execute();
   if(Category::model()->exists('parent_id=:id',array(':id'=>$id))||Product::model()->exists('category_id=:id',array(':id'=>$id))){$this->flash('ابتدا محصولات و زیرمجموعه‌های این دسته را جابه‌جا کنید.','error');$tx->commit();$this->redirect(array('adminCategory/index','id'=>$id),false,303);return;}
   $model->delete();$tx->commit();$this->flash('دسته‌بندی حذف شد.');$this->redirect(array('adminCategory/index'),false,303);
  }catch(Exception $e){if($tx->active)$tx->rollback();throw $e;}
 }
 private function json($value,$status=200){http_response_code($status);header('Content-Type: application/json; charset=utf-8');echo CJSON::encode($value);Yii::app()->end();}
 private function load($id){$model=Category::model()->findByPk($id);if(!$model)$this->notFound();return $model;}
}
