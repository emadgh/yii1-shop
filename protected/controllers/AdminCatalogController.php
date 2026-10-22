<?php
class AdminCatalogController extends AdminController
{
    public function actionIndex()
    {
        $this->pageTitle='کاتالوگ‌های شرکت';
        $this->render('//admin/catalogs/index');
    }
    public function actionCreate() { $this->edit(new CompanyCatalog); }
    public function actionUpdate($id) { $this->edit($this->load($id)); }
    private function load($id)
    {
        $model=CompanyCatalog::model()->findByPk($id);
        if (!$model) $this->notFound();
        return $model;
    }
    private function edit($model)
    {
        $error='';
        if (isset($_POST['CompanyCatalog'])) {
            try {
                CompanyCatalogService::save($model, $_POST['CompanyCatalog'], Yii::app()->request->getPost('image_id',''), Yii::app()->request->getPost('pdf_id',''));
                $this->flash('کاتالوگ ذخیره شد.');
                $this->redirect(array('adminCatalog/index'));
            } catch (Exception $exception) { $error=$exception->getMessage(); }
        }
        $this->pageTitle='ویرایش کاتالوگ';
        $this->render('//admin/catalogs/form',array('model'=>$model,'error'=>$error));
    }
    public function actionDelete($id)
    {
        $this->requirePost();$this->load($id)->delete();
        $this->flash('کاتالوگ حذف شد.');$this->redirect(array('adminCatalog/index'));
    }
}
