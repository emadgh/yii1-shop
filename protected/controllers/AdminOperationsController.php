<?php
class AdminOperationsController extends AdminController {
 public function actionIndex(){if(!AccessService::isSuperAdmin())throw new CHttpException(403);$this->pageTitle='عملیات فروشگاه';$this->render('//admin/operations/index');}
}
