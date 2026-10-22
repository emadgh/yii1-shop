<?php
class AppController extends CController
{
    public $layout='//layouts/main';
    public $socialImage='';
    public $pageTitle='بازارچه';
    public $metaDescription='فروشگاه اینترنتی بازارچه؛ خرید آسان و محصولات منتخب.';
    protected function beforeAction($action) { header('X-Content-Type-Options: nosniff');header('X-Frame-Options: SAMEORIGIN');header('Referrer-Policy: strict-origin-when-cross-origin'); OrderService::expire(); Yii::app()->params['currency']=Money::unit(); return parent::beforeAction($action); }

    public function render($view,$data=null,$return=false)
    {
        $req=Yii::app()->request;
        // POST fragments are explicit opt-in; CSRF and controller access checks still run.
        $fragmentMethod=$req->getRequestType()==='GET'||($req->getRequestType()==='POST'&&($_SERVER['HTTP_X_PJAX_FORM']??'')==='true');
        if($fragmentMethod && $req->getIsAjaxRequest() && isset($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX']==='true') {
            Yii::app()->getClientScript()->reset();
            $html=$this->renderPartial($view,$data,true,false);
            $title=CHtml::encode($this->pageTitle);
            $description=CHtml::encode($this->metaDescription);
            $storeTheme=$this->layout==='//layouts/admin'?'admin':ThemeService::key();
            $messages='';foreach(array('success','error') as $kind)if(Yii::app()->user->hasFlash($kind))$messages.='<div class="flash flash-'.$kind.'">'.CHtml::encode(Yii::app()->user->getFlash($kind)).'</div>';
            $html=$messages.$html;
            $html='<div data-pjax-title="'.$title.'" data-pjax-description="'.$description.'" data-pjax-image="'.CHtml::encode($this->socialImage).'" data-store-theme="'.CHtml::encode($storeTheme).'">'.$html.'</div>';
            if($return) return $html;
            echo $html;
            return;
        }
        return parent::render($view,$data,$return);
    }

    protected function notFound() { throw new CHttpException(404,'مورد درخواستی پیدا نشد.'); }
    protected function redirectAfterPost($url) { $this->redirect($url); }
}
