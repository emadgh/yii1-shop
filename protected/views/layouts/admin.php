<?php
$cs=Yii::app()->clientScript;
JalaliDate::assets();
$cs->registerCssFile(Yii::app()->baseUrl.'/assets/css/app.css');
$cs->registerCssFile(Yii::app()->baseUrl.'/assets/css/modern.css');
$cs->registerCssFile(Yii::app()->baseUrl.'/assets/css/cms.css');
$cs->registerCssFile(Yii::app()->baseUrl.'/assets/css/async.css');
$cs->registerCssFile(Yii::app()->baseUrl.'/assets/css/admin-tables.css');
$cs->registerCssFile(Yii::app()->baseUrl.'/assets/css/category-tree.css');
$cs->registerScriptFile(Yii::app()->baseUrl.'/assets/js/category-tree.js',CClientScript::POS_END);
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
  <?= CHtml::link('◈ نمای کلی',array('adminDashboard/index'),array('data-pjax'=>'1')) ?>
  <div class="side-section">کاتالوگ</div>
  <?= CHtml::link('▣ محصولات',array('adminProduct/index'),array('data-pjax'=>'1')) ?>
  <?= CHtml::link('◫ دسته‌بندی‌ها',array('adminCategory/index'),array('data-pjax'=>'1')) ?>
  <?= CHtml::link('⌗ تگ‌ها',array('adminTag/index'),array('data-pjax'=>'1')) ?>
  <div class="side-section">محتوا و فروش</div>
  <?= CHtml::link('▤ بلاگ',array('adminPost/index'),array('data-pjax'=>'1')) ?>
  <?= CHtml::link('◉ سفارش‌ها',array('adminOrder/index'),array('data-pjax'=>'1')) ?>
  <?= CHtml::link('◇ کدهای تخفیف',array('adminCoupon/index'),array('data-pjax'=>'1')) ?>
  <?= CHtml::link('↗ مالی و انبار',array('adminReport/index'),array('data-pjax'=>'1')) ?>
  <div class="side-section">مدیریت وبسایت</div><?= CHtml::link("◧ کالکشن‌ها",array("adminCollection/index"),array("data-pjax"=>"1")) ?><?= CHtml::link("☷ مدیریت منو",array("adminMenu/index"),array("data-pjax"=>"1")) ?><?= CHtml::link("▥ آمار بازدید",array("adminAnalytics/index"),array("data-pjax"=>"1")) ?>
  <?= CHtml::link('▧ کتابخانه رسانه',array('adminMedia/index'),array('data-pjax'=>'1')) ?>
  <?= CHtml::link('⚙ تنظیمات',array('adminSettings/index'),array('data-pjax'=>'1')) ?>
  <?php if(AccessService::isSuperAdmin()): ?><?= CHtml::link('◐ مدیریت قالب‌ها',array('adminTheme/index'),array('data-pjax'=>'1')) ?><?php endif; ?>
  <div class="side-section">میانبرها</div>
  <?= CHtml::link('↗ مشاهده فروشگاه',array('site/index'),array('data-no-pjax'=>'1')) ?>
  <?= CHtml::beginForm(array('site/logout'),'post',array('class'=>'logout-form')) ?><?= CHtml::submitButton('خروج از حساب',array('class'=>'logout-btn')) ?><?= CHtml::endForm() ?>
</aside>
<div class="admin-area"><div class="admin-top"><div class="admin-breadcrumb">مدیریت / <?= CHtml::encode($this->pageTitle) ?></div><span class="admin-avatar">مدیر سیستم</span></div>
<main id="pjax-main" class="admin-content" tabindex="-1">
<?php foreach(array('success','error') as $type) if(Yii::app()->user->hasFlash($type)): ?>
<div class="flash <?= $type==='error'?'flash-error':'flash-success' ?>"><?= CHtml::encode(Yii::app()->user->getFlash($type)) ?></div>
<?php endif; ?>
<?= $content ?>
</main></div></div></body></html>
