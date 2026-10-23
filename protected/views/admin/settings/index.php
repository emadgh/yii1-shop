<div class="website-settings">
<div class="admin-page-head"><h1>تنظیمات وبسایت</h1></div>
<?php if($error): ?><div class="flash flash-error"><?= CHtml::encode($error) ?></div><?php endif; ?>
<?= CHtml::beginForm('','post',array('id'=>'website-settings-form','class'=>'settings-form')) ?>
<div class="settings-grid">
<div class="settings-column">
<section class="panel">
  <h2>هویت و ارتباط</h2>
  <div class="settings-fields">
  <?php foreach(array('site_name'=>'نام سایت','site_description'=>'معرفی سایت','contact_phone'=>'تلفن تماس','contact_email'=>'ایمیل تماس','announcement'=>'نوار اطلاع‌رسانی','footer_text'=>'متن پایین سایت') as $key=>$label): ?>
    <div class="field <?= in_array($key,array('announcement','footer_text'),true)?'settings-field-wide':'' ?>">
      <label for="<?= $key ?>"><?= $label ?></label>
      <?= CHtml::textField($key,ShopSettings::get($key,''),array('id'=>$key,'dir'=>in_array($key,array('contact_phone','contact_email'),true)?'ltr':'rtl')) ?>
    </div>
  <?php endforeach; ?>
  </div>
</section>
<section class="panel">
  <h2>فروشگاه یا کاتالوگ</h2>
  <div class="check-field"><?= CHtml::checkBox('commerce_enabled',Commerce::enabled(),array('value'=>'1')) ?><label for="commerce_enabled">امکان خرید و ثبت سفارش فعال باشد</label></div>
  <div class="check-field"><?= CHtml::checkBox('catalog_show_prices',ShopSettings::get('catalog_show_prices','1')==='1',array('value'=>'1')) ?><label for="catalog_show_prices">قیمت محصولات در حالت کاتالوگ نمایش داده شود</label></div>
  <p class="muted">با خاموش‌کردن خرید، سایت به کاتالوگ تبدیل می‌شود؛ مدیریت سفارش‌های قبلی همچنان در دسترس است.</p>
  <div class="settings-fields">
    <div class="field"><label for="currency">واحد نمایش و ورود قیمت</label><?= CHtml::dropDownList('currency',ShopSettings::get('currency'),array('toman'=>'تومان','rial'=>'ریال')) ?></div>
    <div class="field"><label for="reservation_minutes">مهلت رزرو (دقیقه)</label><?= CHtml::numberField('reservation_minutes',ShopSettings::get('reservation_minutes'),array('min'=>1,'max'=>10080,'required'=>true)) ?></div>
  </div>
  <p class="muted">تغییر واحد، ارزش مبالغ را حفظ می‌کند. مدت رزرو جدید برای سفارش‌های بعدی اعمال می‌شود.</p>
</section>
<section class="panel">
  <h2>ارسال سفارش</h2>
  <div class="settings-fields">
    <div class="field"><label for="shipping_fee">هزینه ارسال (<?= Money::unit() ?>)</label><?= CHtml::textField('shipping_fee',Money::input(ShopSettings::get('shipping_fee',0)),array('class'=>'money-input')) ?></div>
    <div class="field"><label for="shipping_free_threshold">حداقل ارسال رایگان (<?= Money::unit() ?>)</label><?= CHtml::textField('shipping_free_threshold',Money::input(ShopSettings::get('shipping_free_threshold',0)),array('class'=>'money-input')) ?></div>
  </div>
  <p class="muted">برای غیرفعال‌کردن ارسال رایگان، حداقل خرید را صفر بگذارید.</p>
</section>
</div>
<div class="settings-column">
<?php if(AccessService::isSuperAdmin()): ?>
<section class="panel">
  <h2>درگاه پرداخت زرین‌پال</h2>
  <div class="check-field"><?= CHtml::checkBox('zarinpal_enabled',ShopSettings::get('zarinpal_enabled','0')==='1',array('value'=>'1')) ?><label for="zarinpal_enabled">پرداخت آنلاین فعال باشد</label></div>
  <div class="settings-fields">
    <div class="field settings-field-wide"><label for="zarinpal_merchant">Merchant ID</label><?= CHtml::textField('zarinpal_merchant',ShopSettings::get('zarinpal_merchant',''),array('dir'=>'ltr','maxlength'=>36,'autocomplete'=>'off')) ?></div>
    <div class="field"><label for="zarinpal_mode">حالت درگاه</label><?= CHtml::dropDownList('zarinpal_mode',ShopSettings::get('zarinpal_mode','sandbox'),array('sandbox'=>'آزمایشی','live'=>'واقعی')) ?></div>
    <div class="field"><label for="public_url">آدرس عمومی سایت</label><?= CHtml::urlField('public_url',ShopSettings::get('public_url',''),array('dir'=>'ltr','placeholder'=>'https://example.com')) ?></div>
  </div>
  <p class="muted">تا تکمیل اطلاعات، درگاه را غیرفعال نگه دارید. مبالغ به ریال برای درگاه ارسال می‌شوند؛ حالت واقعی به HTTPS نیاز دارد.</p>
</section>
<?php endif; ?>
<section class="panel">
  <h2>آمار بازدید</h2>
  <div class="check-field"><?= CHtml::checkBox('analytics_enabled',VisitService::enabled(),array('value'=>'1')) ?><label for="analytics_enabled">ثبت آمار ناشناس بازدید فعال باشد</label></div>
  <div class="field"><label for="analytics_retention_days">مدت نگهداری آمار (روز)</label><?= CHtml::numberField('analytics_retention_days',ShopSettings::get('analytics_retention_days',90),array('min'=>7,'max'=>365,'required'=>true)) ?></div>
  <details class="settings-details" <?= ShopSettings::get('matomo_url','')||ShopSettings::get('matomo_site_id','')?'open':'' ?>>
    <summary>اتصال اختیاری Matomo</summary>
    <div class="settings-fields">
      <div class="field"><label for="matomo_url">آدرس Matomo</label><?= CHtml::urlField('matomo_url',ShopSettings::get('matomo_url',''),array('placeholder'=>'https://analytics.example.com/','dir'=>'ltr')) ?></div>
      <div class="field"><label for="matomo_site_id">شناسه سایت Matomo</label><?= CHtml::textField('matomo_site_id',ShopSettings::get('matomo_site_id',''),array('inputmode'=>'numeric')) ?></div>
    </div>
    <p class="muted">آدرس HTTPS و شناسه سایت را با هم وارد کنید. Matomo بدون کوکی اجرا می‌شود و درخواست عدم ردیابی مرورگر رعایت می‌شود.</p>
  </details>
  <p class="muted">آمار داخلی بدون سرویس بیرونی کار می‌کند؛ نگهداری آمار از ۷ تا ۳۶۵ روز قابل تنظیم است.</p>
</section>
<?php if(AccessService::isSuperAdmin()): ?>
<section class="panel">
  <h2>کش و سرعت سایت</h2>
  <div class="settings-fields">
    <div class="field"><label for="cache_backend">محل نگهداری کش</label><?= CHtml::dropDownList('cache_backend',ShopSettings::get('cache_backend','db'),array('db'=>'دیتابیس (پیش‌فرض)','file'=>'فایل روی دیسک','off'=>'غیرفعال')) ?></div>
    <div class="field"><label for="cache_ttl">مدت نگهداری (ثانیه)</label><?= CHtml::numberField('cache_ttl',ShopSettings::get('cache_ttl','600'),array('min'=>30,'max'=>86400,'required'=>true)) ?></div>
  </div>
  <p class="muted">کش با تغییر داده‌ها خودکار باطل می‌شود. موجودی، سبد خرید و پرداخت همیشه از داده تازه استفاده می‌کنند.</p>
  <button type="submit" form="settings-cache-clear" class="btn btn-outline btn-sm">پاک‌سازی کش سایت</button>
</section>
<?php endif; ?>
</div>
</div>
<div class="settings-actions"><?= CHtml::submitButton('ذخیره تنظیمات',array('class'=>'btn btn-primary')) ?></div>
<?= CHtml::endForm() ?>
<?php if(AccessService::isSuperAdmin()): ?><?= CHtml::beginForm(array('adminSettings/clearCache'),'post',array('id'=>'settings-cache-clear','data-confirm'=>'کش همه بخش‌ها دوباره ساخته شود؟')) ?><?= CHtml::endForm() ?><?php endif; ?>
</div>
