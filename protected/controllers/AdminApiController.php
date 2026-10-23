<?php
class AdminApiController extends AdminController
{
    public function actionIndex()
    {
        if(!AccessService::isSuperAdmin())throw new CHttpException(403);
        header('Cache-Control: no-store');header('Referrer-Policy: no-referrer');$secret=null;$issued=null;$error='';
        if(Yii::app()->request->isPostRequest){
            try{
                if(Yii::app()->request->getPost('action')==='toggle'){
                    ShopSettings::set('ai_api_enabled',Yii::app()->request->getPost('enabled')==='1'?'1':'0');Audit::record('ai_api','settings','toggle');$this->flash('تنظیمات API ذخیره شد.');$this->redirect(array('adminApi/index'));
                }
                $scopes=Yii::app()->request->getPost('scopes',array());$days=Yii::app()->request->getPost('days','90');
                if(!is_string($days)||!ctype_digit($days))throw new CHttpException(422,'مدت اعتبار معتبر نیست.');
                $issued=ApiTokenService::issue(Yii::app()->user->id,Yii::app()->request->getPost('name',''),$scopes,(int)$days);$secret=$issued['token'];Audit::record('api_tokens',$issued['id'],'issue',array('scopes'=>$scopes));
            }catch(Exception $e){$error=$e->getMessage();}
        }
        $apiUrl=rtrim(ShopSettings::get('public_url','')?:Yii::app()->request->hostInfo.Yii::app()->baseUrl,'/').'/api/v1/';
        $this->pageTitle='دسترسی AI و API';$this->render('//admin/api/index',array('secret'=>$secret,'issued'=>$issued,'apiUrl'=>$apiUrl,'error'=>$error,'scopes'=>ApiTokenService::scopes()));
    }
    public function actionRevoke($id)
    {
        $this->requirePost();if(!AccessService::isSuperAdmin())throw new CHttpException(403);
        Yii::app()->db->createCommand()->update('api_tokens',array('revoked_at'=>date('Y-m-d H:i:s')),'id=:id',array(':id'=>$id));Audit::record('api_tokens',$id,'revoke');$this->flash('توکن لغو شد.');$this->redirect(array('adminApi/index'));
    }
}
