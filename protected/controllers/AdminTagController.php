<?php
class AdminTagController extends AdminController
{
    public function actionIndex() { $this->pageTitle='مدیریت تگ‌ها';$this->render('//admin/tags/index'); }
 public function actionCreate() { $this->edit(new Tag); }
    public function actionUpdate($id) { $this->edit($this->load($id)); }
    private function edit($model) {
      if(isset($_POST['Tag'])) {
         $model->attributes=$_POST['Tag'];
         if($model->save()) { $this->flash('تگ ذخیره شد.'); $this->redirect(array('adminTag/index')); }
      }
      $this->pageTitle=$model->isNewRecord?'تگ جدید':'ویرایش تگ';
      $this->render('//admin/tags/form',array('model'=>$model));
    }
    public function actionDelete($id) { $this->requirePost(); $this->load($id)->delete(); $this->redirect(array('adminTag/index')); }
    private function load($id) { $model=Tag::model()->findByPk($id); if(!$model) $this->notFound(); return $model; }
}
