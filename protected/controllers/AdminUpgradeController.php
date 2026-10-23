<?php
class AdminUpgradeController extends AdminController
{
    public function actionIndex()
    {
        if(!AccessService::isSuperAdmin())throw new CHttpException(403);
        header('Cache-Control: no-store');$this->pageTitle='ارتقای دیتابیس';
        $this->render('//admin/upgrade/index',array('result'=>Yii::app()->user->getFlash('databaseUpgradeResult',null),'lastUpgrade'=>ShopSettings::get('database_upgraded_at','')));
    }
    public function actionRun()
    {
        $this->requirePost();if(!AccessService::isSuperAdmin())throw new CHttpException(403);
        try{$log=DatabaseUpgradeService::run();$result=array('ok'=>true,'message'=>'ارتقای دیتابیس با موفقیت انجام شد.','log'=>$log);}
        catch(RuntimeException $exception){$result=array('ok'=>false,'message'=>$exception->getMessage(),'log'=>'');}
        Yii::app()->user->setFlash('databaseUpgradeResult',$result);
        $this->redirect(Yii::app()->request->getQuery('r')!==null?Yii::app()->request->scriptUrl.'?r=adminUpgrade/index':array('adminUpgrade/index'));
    }
}
