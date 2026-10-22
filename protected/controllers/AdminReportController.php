<?php
class AdminReportController extends AdminController {
 public function actionIndex() {
   $db=Yii::app()->db;$this->pageTitle='مالی و انبار';
   $totals=$db->createCommand("SELECT COALESCE(SUM(CASE WHEN kind='receipt' THEN amount ELSE 0 END),0) receipts,COALESCE(SUM(CASE WHEN kind='refund' THEN -amount ELSE 0 END),0) refunds,COALESCE(SUM(amount),0) net FROM financial_entries")->queryRow();
   $this->render('//admin/reports/index',array('totals'=>$totals));
 }
}
