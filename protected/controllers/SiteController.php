<?php
class SiteController extends AppController
{
    protected function beforeAction($action) {
      // Error pages must remain available when database services fail.
      return $action->id==='error' ? true : parent::beforeAction($action);
    }
    public function actionIndex() {
       $this->pageTitle=ShopSettings::get('site_name').' | خانه';$this->metaDescription=ShopSettings::get('site_description');
       $this->render('index');
    }
    public function actionLogin() {
      if(!Yii::app()->user->isGuest) $this->redirect(array('adminDashboard/index'));
      $error='';
      if(Yii::app()->request->isPostRequest) {
        $email=trim((string)Yii::app()->request->getPost('email',''));
        $password=(string)Yii::app()->request->getPost('password','');
        LoginGuard::attempt($email);
        $identity=new StoreIdentity($email,$password);
        $identity->otp=Yii::app()->request->getPost('otp','');if($identity->authenticate()) {
          Yii::app()->user->login($identity,0);
          $this->redirect(array('adminDashboard/index'));
        }
        $error='ایمیل یا رمز عبور نادرست است.';
      }
      $this->pageTitle='ورود مدیریت'; $this->render('login',array('error'=>$error));
    }
    public function actionLogout() {
      if(!Yii::app()->request->isPostRequest) throw new CHttpException(405);
      Yii::app()->user->logout();
      $this->redirect(array('site/index'));
    }
    public function actionError() {
      $error=Yii::app()->errorHandler->error;
      if(!$error) $error=array('code'=>404);
      if(!headers_sent()) {
        http_response_code((int)$error['code']);
        header('Cache-Control: no-store');
      }
      $this->renderFile(dirname(__DIR__).'/views/site/error.php',array('error'=>$error));
    }
}
