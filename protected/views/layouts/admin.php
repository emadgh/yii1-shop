<?php
$cs=Yii::app()->clientScript;
$adminLink=function($label,$route,$options=array()){return AccessService::canRoute($route[0])?CHtml::link($label,$route,$options):'';};
JalaliDate::assets();
$cs->registerCssFile(Yii::app()->baseUrl.'/assets/css/app.css');
$cs->registerCssFile(Yii::app()->baseUrl.'/assets/css/modern.css');
$cs->registerCssFile(Yii::app()->baseUrl.'/assets/css/cms.css');
$cs->registerCssFile(Yii::app()->baseUrl.'/assets/css/async.css');
$cs->registerCssFile(Yii::app()->baseUrl.'/assets/css/admin-tables.css');
$cs->registerCssFile(Yii::app()->baseUrl.'/assets/css/hierarchy-editor.css');
$cs->registerCssFile(Yii::app()->baseUrl.'/assets/css/albums.css');
$cs->registerCssFile(Yii::app()->baseUrl.'/assets/css/sortable-list.css');
foreach(array('sortable-list','album-editor') as $script)$cs->registerScriptFile(Yii::app()->baseUrl.'/assets/js/'.$script.'.js',CClientScript::POS_END);
$cs->registerScriptFile(Yii::app()->baseUrl.'/assets/js/hierarchy-editor.js',CClientScript::POS_END);
$cs->registerScriptFile(Yii::app()->baseUrl.'/assets/js/admin-tables.js',CClientScript::POS_END);
$cs->registerScriptFile(Yii::app()->baseUrl.'/assets/js/cms.js',CClientScript::POS_END);
$cs->registerCssFile(Yii::app()->baseUrl.'/assets/css/themes-admin.css');
$cs->registerScriptFile(Yii::app()->baseUrl.'/assets/js/app.js',CClientScript::POS_END);
$cs->registerScriptFile(Yii::app()->baseUrl.'/assets/js/pjax.js',CClientScript::POS_END);
?>
<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="description" content="<?= CHtml::encode($this->metaDescription) ?>">
<meta name="theme-color" content="#0e2631"><title><?= CHtml::encode($this->pageTitle) ?></title><meta name="csrf-name" content="<?= CHtml::encode(Yii::app()->request->csrfTokenName) ?>"><meta name="csrf-token" content="<?= CHtml::encode(Yii::app()->request->csrfToken) ?>"><link rel="icon" href="/assets/favicon.svg" type="image/svg+xml"></head>
<body class="admin-body" data-layout="admin" data-store-theme="admin" data-base="<?= CHtml::encode(Yii::app()->baseUrl) ?>"><?php $this->renderPartial('//shared/_dialogs'); ?><a class="skip-link" href="#pjax-main">رفتن به محتوا</a><div class="admin-shell">
<aside class="sidebar">
  <div class="sidebar-head"><span class="brandmark">ب</span><div>پنل بازارچه<small>مدیریت فروشگاه</small></div></div>
  <div class="side-section">داشبورد</div>
  <?= $adminLink('◈ نمای کلی',array('adminDashboard/index'),array('data-pjax'=>'1')) ?>
  <div class="side-section">کاتالوگ</div>
  <?= $adminLink('▣ محصولات',array('adminProduct/index'),array('data-pjax'=>'1')) ?>
  <?= $adminLink('◫ دسته‌بندی‌ها',array('adminCategory/index'),array('data-pjax'=>'1')) ?>
  <?= $adminLink('⌗ تگ‌ها',array('adminTag/index'),array('data-pjax'=>'1')) ?>
  <div class="side-section">محتوا و فروش</div>
  <?= $adminLink('▥ کاتالوگ‌های شرکت',array('adminCatalog/index'),array('data-pjax'=>'1')) ?>
  <?= $adminLink('▤ بلاگ',array('adminPost/index'),array('data-pjax'=>'1')) ?>
  <?= $adminLink('◉ سفارش‌ها',array('adminOrder/index'),array('data-pjax'=>'1')) ?>
  <?= $adminLink('◇ کدهای تخفیف',array('adminCoupon/index'),array('data-pjax'=>'1')) ?>
  <?= $adminLink('↗ مالی و انبار',array('adminReport/index'),array('data-pjax'=>'1')) ?>
  <div class="side-section">مدیریت وبسایت</div><?= $adminLink("◧ کالکشن‌ها",array("adminCollection/index"),array("data-pjax"=>"1")) ?><?= $adminLink("☷ مدیریت منو",array("adminMenu/index"),array("data-pjax"=>"1")) ?><?= $adminLink("▥ آمار بازدید",array("adminAnalytics/index"),array("data-pjax"=>"1")) ?>
  <?= $adminLink('▧ کتابخانه رسانه',array('adminMedia/index'),array('data-pjax'=>'1')) ?>
  <?= $adminLink('⚙ تنظیمات',array('adminSettings/index'),array('data-pjax'=>'1')) ?>
  <?php if(AccessService::isSuperAdmin()): ?><?= $adminLink('◐ مدیریت قالب‌ها',array('adminTheme/index'),array('data-pjax'=>'1')) ?><?php endif; ?>
  <?php if(AccessService::isSuperAdmin()): ?><?= $adminLink('تغییر آدرس صفحات',array('adminSeo/index'),array('data-pjax'=>'1')) ?><?= $adminLink('کاربران مدیریت',array('adminUser/index'),array('data-pjax'=>'1')) ?><?= $adminLink('عملیات و تاریخچه',array('adminOperations/index'),array('data-pjax'=>'1')) ?><?php endif; ?><?= $adminLink('امنیت حساب',array('adminSecurity/index'),array('data-pjax'=>'1')) ?><div class="side-section">میانبرها</div>
  <?= $adminLink('↗ مشاهده فروشگاه',array('site/index'),array('data-no-pjax'=>'1')) ?>
  <?= CHtml::beginForm(array('site/logout'),'post',array('class'=>'logout-form')) ?><?= CHtml::submitButton('خروج از حساب',array('class'=>'logout-btn')) ?><?= CHtml::endForm() ?>
</aside>
<div class="admin-area"><div class="admin-top"><div class="admin-breadcrumb">مدیریت / <?= CHtml::encode($this->pageTitle) ?></div><span class="admin-avatar">مدیر سیستم</span></div>
<main id="pjax-main" class="admin-content" tabindex="-1">
<?php foreach(array('success','error') as $type) if(Yii::app()->user->hasFlash($type)): ?>
<div class="flash <?= $type==='error'?'flash-error':'flash-success' ?>"><?= CHtml::encode(Yii::app()->user->getFlash($type)) ?></div>
<?php endif; ?>
<?= $content ?>
</main></div></div></body></html>
