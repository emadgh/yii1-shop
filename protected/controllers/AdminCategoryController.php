<?php
class AdminCategoryController extends AdminController
{
    public function actionIndex() { $this->pageTitle='دسته‌بندی محصولات';$this->render('//admin/categories/index'); }
 public function actionCreate() { $this->edit(new Category); }
    public function actionUpdate($id) { $this->edit($this->load($id)); }
    private function edit($model) {
      if(isset($_POST['Category'])) {
         $model->attributes=$_POST['Category'];
         $model->parent_id=$model->parent_id?:null;
         try { CmsMedia::bind($model,Yii::app()->request->getPost('image_id',''));if(Hierarchy::save($model)) { $this->flash('دسته‌بندی ذخیره شد.'); $this->redirect(array('adminCategory/index')); } } catch(Exception $e){$model->addError('image',$e->getMessage());}
      }
      $this->pageTitle=$model->isNewRecord?'دسته‌بندی جدید':'ویرایش دسته‌بندی';
      $this->render('//admin/categories/form',array('model'=>$model));
    }
    public function actionDelete($id) { $this->requirePost();$m=$this->load($id);if(Category::model()->exists('parent_id=:id',array(':id'=>$id)) || Product::model()->exists('category_id=:id',array(':id'=>$id))){$this->flash('ابتدا محصولات و زیرمجموعه‌های این دسته را جابه‌جا کنید.','error');$this->redirect(array('adminCategory/index'));}$m->delete();$this->redirect(array('adminCategory/index')); }
    private function load($id) { $model=Category::model()->findByPk($id); if(!$model) $this->notFound(); return $model; }
}
