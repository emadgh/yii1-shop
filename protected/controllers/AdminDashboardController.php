<?php
class AdminDashboardController extends AdminController
{
    public function actionIndex() {
       $db=Yii::app()->db;
       $stats=array(
         'products'=>(int)$db->createCommand('SELECT COUNT(*) FROM products')->queryScalar(),
         'posts'=>(int)$db->createCommand('SELECT COUNT(*) FROM posts')->queryScalar(),
         'orders'=>(int)$db->createCommand('SELECT COUNT(*) FROM orders')->queryScalar(),
         'revenue'=>(int)$db->createCommand("SELECT COALESCE(SUM(amount),0) FROM financial_entries")->queryScalar(),
       );
       $orders=Order::model()->findAll(array('order'=>'created_at DESC','limit'=>7));
       $this->pageTitle='داشبورد مدیریت';
       $this->render('//admin/dashboard/index',array('stats'=>$stats,'orders'=>$orders));
    }
}
