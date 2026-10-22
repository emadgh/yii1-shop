<div class="admin-page-head"><div><span class="eyebrow">آمار و مدیریت</span><h1>داشبورد فروشگاه</h1><p>نمای کلی فعالیت فروشگاه شما</p></div><?= CHtml::link('+ افزودن محصول',array('adminProduct/create'),array('class'=>'btn btn-primary','data-pjax'=>'1')) ?></div>
<div class="metric-grid">
<div class="metric"><div class="metric-icon">▣</div><small>کل محصولات</small><strong><?= number_format($stats['products']) ?></strong><span>محصول ثبت‌شده</span></div>
<div class="metric"><div class="metric-icon">◉</div><small>سفارش‌ها</small><strong><?= number_format($stats['orders']) ?></strong><span>مجموع سفارش‌ها</span></div>
<div class="metric"><div class="metric-icon">▤</div><small>نوشته‌های بلاگ</small><strong><?= number_format($stats['posts']) ?></strong><span>محتوای منتشرشده یا پیش‌نویس</span></div>
<div class="metric"><div class="metric-icon">◇</div><small>خالص دریافتی ثبت‌شده</small><strong><?= Money::format($stats['revenue']) ?></strong><span>پس از کسر بازپرداخت‌ها</span></div>
</div>
<?php $this->widget("SiteStats",array("days"=>7)); ?><div class="admin-columns"><section class="panel"><div class="panel-heading"><h2>سفارش‌های اخیر</h2><?= CHtml::link('مشاهده همه',array('adminOrder/index'),array('data-pjax'=>'1','class'=>'text-link')) ?></div>
<?php $this->widget("AdminTable",array("type"=>"orders","scope"=>"recent-orders","pageSize"=>7,"only"=>array("order_number","customer_name","total","status"))); ?></section>
<section class="panel shortcuts"><h2>دسترسی سریع</h2>
<?= CHtml::link('<span>▣</span><div><strong>مدیریت محصولات</strong><small>تصاویر، قیمت، موجودی و متادیتا</small></div><b>←</b>',array('adminProduct/index'),array('data-pjax'=>'1')) ?>
<?= CHtml::link('<span>▤</span><div><strong>مدیریت بلاگ</strong><small>نوشته‌های جدید و تگ‌ها</small></div><b>←</b>',array('adminPost/index'),array('data-pjax'=>'1')) ?>
<?= CHtml::link('<span>◉</span><div><strong>بررسی سفارش‌ها</strong><small>ثبت، تأیید و تغییر وضعیت</small></div><b>←</b>',array('adminOrder/index'),array('data-pjax'=>'1')) ?>
</section></div>
