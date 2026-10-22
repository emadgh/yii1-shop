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
      header('Cache-Control: no-store');
      if(!Yii::app()->user->isGuest) $this->redirect(array('adminDashboard/index'));
      $error='';
      if(Yii::app()->request->isPostRequest) {
        $email=Yii::app()->request->getPost('email','');$password=Yii::app()->request->getPost('password','');
        if(!is_string($email)||!is_string($password))throw new CHttpException(400);$email=trim($email);
        LoginGuard::attempt($email);
        $identity=new StoreIdentity($email,$password);
        $result=$identity->beginLogin();
        if($result==='challenge')$this->redirect(array('site/verify'));
        if($result==='authenticated') {
          Yii::app()->user->login($identity,0);
          $this->redirect(array('adminDashboard/index'));
        }
        $error='ایمیل یا رمز عبور نادرست است.';
      }
      $this->pageTitle='ورود مدیریت'; $this->render('login',array('error'=>$error));
    }
    public function actionVerify(){
      header('Cache-Control: no-store');header('Referrer-Policy: no-referrer');
      if(!Yii::app()->user->isGuest)$this->redirect(array('adminDashboard/index'));
      if(!StoreIdentity::challengeUser())$this->redirect(array('site/login'));
      $error='';
      if(Yii::app()->request->isPostRequest){
        $identity=StoreIdentity::authenticateChallenge(Yii::app()->request->getPost('otp',''));
        if($identity){Yii::app()->user->login($identity,0);$this->redirect(array('adminDashboard/index'));}
        if(!StoreIdentity::challengeUser())$this->redirect(array('site/login'));
        $error='کد معتبر نیست یا قبلاً استفاده شده است.';
      }
      $this->pageTitle='تأیید ورود دومرحله‌ای';$this->render('verify',array('error'=>$error));
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
