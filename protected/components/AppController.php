<?php
class AppController extends CController
{
    public $layout='//layouts/main';
    public $pageTitle='بازارچه';
    public $metaDescription='فروشگاه اینترنتی بازارچه؛ خرید آسان و محصولات منتخب.';
    protected function beforeAction($action) { OrderService::expire(); Yii::app()->params['currency']=Money::unit(); return parent::beforeAction($action); }

    public function render($view,$data=null,$return=false)
    {
        $req=Yii::app()->request;
        // Only regular GET page navigation is eligible for a layout-free response.
        if($req->getRequestType()==='GET' && $req->getIsAjaxRequest() && isset($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX']==='true') {
            Yii::app()->getClientScript()->reset();
            $html=$this->renderPartial($view,$data,true,false);
            $title=CHtml::encode($this->pageTitle);
            $description=CHtml::encode($this->metaDescription);
            $storeTheme=$this->layout==='//layouts/admin'?'admin':ThemeService::key();
            $html='<div data-pjax-title="'.$title.'" data-pjax-description="'.$description.'" data-store-theme="'.CHtml::encode($storeTheme).'">'.$html.'</div>';
            if($return) return $html;
            echo $html;
            return;
        }
        return parent::render($view,$data,$return);
    }

    protected function notFound() { throw new CHttpException(404,'مورد درخواستی پیدا نشد.'); }
    protected function redirectAfterPost($url) { $this->redirect($url); }
}
