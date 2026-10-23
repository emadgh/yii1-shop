<div class="admin-page-head"><div><span class="eyebrow">سیستم و امنیت</span><h1>ارتقای دیتابیس</h1><p>اجرای ارتقا پس از آپلود نسخهٔ جدید، بدون نیاز به SSH یا ترمینال.</p></div></div>
<?php if($result): ?><div class="flash <?= $result['ok']?'flash-success':'flash-error' ?>" role="status" data-upgrade-result="<?= $result['ok']?'success':'error' ?>"><?= CHtml::encode($result['message']) ?></div><?php endif; ?>
<section class="panel narrow-panel"><h2>به‌روزرسانی ساختار و تنظیمات</h2>
<p>ابتدا فایل‌های نسخهٔ جدید را آپلود و از دیتابیس در پنل هاست نسخهٔ پشتیبان تهیه کنید. سپس دکمهٔ زیر را بزنید و تا نمایش نتیجه منتظر بمانید.</p>
<p class="muted">این عملیات همان ارتقای <code dir="ltr">tools/upgrade.php</code> را اجرا می‌کند. کاربر جدید یا دادهٔ نمونه اضافه نمی‌کند. فایل‌های ارتقا در پوشهٔ <code dir="ltr">tools</code> باید همراه نسخهٔ جدید آپلود شده باشند.</p>
<?php if($lastUpgrade): ?><p>آخرین ارتقای موفق: <strong dir="ltr"><?= CHtml::encode(JalaliDate::format($lastUpgrade)) ?></strong></p><?php endif; ?>
<?= CHtml::beginForm(isset($_GET['r'])?Yii::app()->request->scriptUrl.'?r=adminUpgrade/run':array('adminUpgrade/run'),'post',array('data-confirm'=>'ارتقای دیتابیس اجرا شود؟ پیش از ادامه، نسخهٔ پشتیبان دیتابیس را از پنل هاست تهیه کنید.')) ?>
<?= CHtml::submitButton('اجرای ارتقای دیتابیس',array('class'=>'btn btn-primary')) ?>
<?= CHtml::endForm() ?>
<p class="muted note">اگر اجرا به دلیل محدودیت هاست قطع شد، بخشی از تغییرات ممکن است ثبت شده باشد؛ پس از رفع مشکل دوباره همین عملیات را اجرا کنید. اجرای مجدد، مراحل تکمیل‌شده را تشخیص می‌دهد.</p>
</section>
<?php if($result&&!empty($result['log'])): ?><section class="panel"><details open><summary>گزارش ارتقا</summary><pre dir="ltr" style="white-space:pre-wrap;overflow-wrap:anywhere;margin-top:16px" data-upgrade-log><?= CHtml::encode($result['log']) ?></pre></details></section><?php endif; ?>
