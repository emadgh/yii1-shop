<?php
class AdminReportController extends AdminController {
 public function actionIndex() {
   $db=Yii::app()->db;$this->pageTitle='مالی و انبار';
   $totals=$db->createCommand("SELECT COALESCE(SUM(CASE WHEN kind='receipt' THEN amount ELSE 0 END),0) receipts,COALESCE(SUM(CASE WHEN kind='refund' THEN -amount ELSE 0 END),0) refunds,COALESCE(SUM(amount),0) net FROM financial_entries")->queryRow();
   $stock=$db->createCommand('SELECT p.id,p.title,p.stock,COALESCE(SUM(m.quantity),0) ledger FROM products p LEFT JOIN inventory_movements m ON m.product_id=p.id GROUP BY p.id,p.title,p.stock')->queryAll();
   $entries=$db->createCommand('SELECT f.*,o.order_number FROM financial_entries f JOIN orders o ON o.id=f.order_id ORDER BY f.id DESC LIMIT 100')->queryAll();
   $movements=$db->createCommand('SELECT m.*,p.title,o.order_number FROM inventory_movements m JOIN products p ON p.id=m.product_id LEFT JOIN orders o ON o.id=m.order_id ORDER BY m.id DESC LIMIT 100')->queryAll();
   $this->render('//admin/reports/index',compact('totals','stock','entries','movements'));
 }
}
