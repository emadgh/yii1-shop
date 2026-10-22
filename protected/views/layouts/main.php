<?php
$cs=Yii::app()->clientScript;
foreach(array('/assets/css/app.css','/assets/css/modern.css') as $file)$cs->registerCssFile(Yii::app()->baseUrl.$file);
$themeKey=ThemeService::key();$theme=ThemeService::info();
if($themeKey!=='classic'){ $cs->registerCssFile(Yii::app()->baseUrl.'/assets/themes/storefront.css');$cs->registerCssFile(Yii::app()->baseUrl.'/assets/themes/'.$themeKey.'.css'); }
$cs->registerCssFile(Yii::app()->baseUrl.'/assets/css/cms.css');
$cs->registerCssFile(Yii::app()->baseUrl.'/assets/css/async.css');
foreach(array('app','pjax','cms') as $script)$cs->registerScriptFile(Yii::app()->baseUrl.'/assets/js/'.$script.'.js',CClientScript::POS_END);
?>
<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="description" content="<?= CHtml::encode($this->metaDescription) ?>"><title><?= CHtml::encode($this->pageTitle) ?></title><meta name="csrf-name" content="<?= CHtml::encode(Yii::app()->request->csrfTokenName) ?>"><meta name="csrf-token" content="<?= CHtml::encode(Yii::app()->request->csrfToken) ?>"><link rel="icon" href="/assets/favicon.svg" type="image/svg+xml"><link rel="preload" href="/assets/fonts/Vazir-Regular.woff2" as="font" type="font/woff2" crossorigin></head>
<body class="storefront theme-<?= CHtml::encode($themeKey) ?> theme-kind-<?= CHtml::encode($theme['kind']) ?>" data-layout="storefront" data-store-theme="<?= CHtml::encode($themeKey) ?>" data-theme-preview="<?= ThemeService::preview()?'1':'0' ?>" data-base="<?= CHtml::encode(Yii::app()->baseUrl) ?>">
<?php $this->renderPartial('//shared/_dialogs'); ?><a class="skip-link" href="#pjax-main">رفتن به محتوا</a>
<div class="announcement"><?= CHtml::encode(ShopSettings::get('announcement','')) ?></div>
<?php if(ThemeService::preview()): ?><div class="theme-preview-banner">پیش‌نمایش <?= CHtml::encode($theme['title']) ?> · <?= CHtml::link('مدیریت قالب‌ها',array('adminTheme/index'),array('data-no-pjax'=>'1')) ?></div><?php endif; ?>
<header class="topbar"><div class="container bar-inner">
<?= CHtml::link('<span class="brandmark">ب</span><span>'.CHtml::encode(ShopSettings::get('site_name')).'<small>اشیایی برای زندگی روزمره</small></span>',array('site/index'),array('class'=>'brand','data-pjax'=>'1')) ?>
<?php if($theme['kind']==='market'): ?><?= CHtml::beginForm(array('shop/index'),'get',array('class'=>'theme-header-search','data-pjax-form'=>'1')) ?><?= CHtml::searchField('q',Yii::app()->request->getQuery('q',''),array('placeholder'=>'جستجوی محصول، مشخصات و برند…','aria-label'=>'جستجوی محصولات')) ?><button type="submit" aria-label="جستجو">⌕</button><?= CHtml::endForm() ?><?php endif; ?>
<nav class="main-nav" aria-label="فهرست اصلی"><?php $this->widget('NavigationWidget'); ?></nav><div class="nav-actions"><?php if($theme['kind']!=='market'): ?><?= CHtml::link('جستجو',array('shop/index'),array('data-pjax'=>'1')) ?><?php endif; ?>
<?php if(Commerce::enabled()): ?><?= CHtml::link('سبد خرید <span class="cart-number">'.(int)Yii::app()->cart->count().'</span>',array('cart/index'),array('class'=>'btn btn-outline','data-pjax'=>'1')) ?><?php else: ?><span class="catalog-mode-label">کاتالوگ محصولات</span><?php endif; ?>
<?php if(AccessService::isAdmin()): ?><?= CHtml::link('مدیریت',array('adminDashboard/index'),array('class'=>'small-link','data-no-pjax'=>'1')) ?><?php endif; ?></div></div></header>
<main id="pjax-main" class="container main-content" tabindex="-1"><?php foreach(array('success','error') as $type)if(Yii::app()->user->hasFlash($type)): ?><div class="flash <?= $type==='error'?'flash-error':'flash-success' ?>"><?= CHtml::encode(Yii::app()->user->getFlash($type)) ?></div><?php endif; ?><?= $content ?></main>
<footer class="footer"><div class="container footer-inner"><div><strong><?= CHtml::encode(ShopSettings::get('site_name')) ?></strong><p><?= CHtml::encode(ShopSettings::get('footer_text',ShopSettings::get('site_description'))) ?></p><?php foreach(array('contact_phone','contact_email') as $field)if(ShopSettings::get($field,'')): ?><p><?= CHtml::encode(ShopSettings::get($field)) ?></p><?php endif; ?></div><nav aria-label="فهرست پایین سایت"><?php $this->widget('NavigationWidget',array('location'=>'footer')); ?></nav></div></footer>
<?php $this->widget('VisitTracker'); ?></body></html>
