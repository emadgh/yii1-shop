<!doctype html>
<html lang="fa" dir="rtl">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>نصب بازارچه</title><link rel="stylesheet" href="assets/installer.css"></head>
<body>
<main class="installer">
<aside class="intro">
  <a class="brand" href="./"><span class="brand-mark">ب</span> بازارچه</a>
  <div><p class="eyebrow">شروع یک فروشگاه تازه</p><h1>خانه تازهٔ<br>کسب‌وکار شما.</h1><p>چند قدم ساده تا راه‌اندازی سایت؛ اطلاعاتتان را وارد کنید، بقیه را به بازارچه بسپارید.</p></div>
  <div class="intro-foot"><span class="dot"></span> نصب روی هاست خودتان، با مالکیت کامل اطلاعات</div>
</aside>
<section class="workspace">
<?php if($locked): ?>
  <div class="result-icon"><?php echo $complete?'✓':'⌑'; ?></div>
  <p class="eyebrow"><?php echo $complete?'آمادهٔ شروع':'نصب محافظت‌شده'; ?></p>
  <h2><?php echo $complete?'سایت شما آماده است.':'این سایت قبلاً نصب شده است.'; ?></h2>
  <p class="muted"><?php echo $complete?'اکنون با ایمیل و رمز مدیری که انتخاب کردید وارد شوید و سایت را شخصی‌سازی کنید.':'نصب‌کننده برای محافظت از اطلاعات قفل شده است. تنظیمات سایت را از پنل مدیریت تغییر دهید.'; ?></p>
  <?php if($complete&&isset($_SESSION['installer_cache_available'])&&!$_SESSION['installer_cache_available']): ?><p class="message">هاست اجازه ساخت trigger نداده است. نصب کامل شد؛ کش برای حفظ صحت اطلاعات غیرفعال مانده است.</p><?php endif; ?>
  <div class="result-actions"><a class="button" href="index.php?r=site/login">ورود به مدیریت ←</a><a class="button secondary" href="./">مشاهده سایت</a></div>
  <p class="hint">نصب مجدد از این صفحه امکان‌پذیر نیست.</p>
<?php else: ?>
  <nav class="steps" aria-label="مراحل نصب"><span data-step-indicator="0">۱ <b>پیش‌نیازها</b></span><span data-step-indicator="1">۲ <b>سایت</b></span><span data-step-indicator="2">۳ <b>دیتابیس</b></span><span data-step-indicator="3">۴ <b>مدیر و نصب</b></span></nav>
  <form id="installer-form" method="post" action="install.php">
  <input type="hidden" name="csrf" value="<?php echo $escape($_SESSION['installer_csrf']); ?>">
  <div id="form-message" class="message <?php echo $error?'error':''; ?>" role="status" aria-live="polite" <?php if(!$error)echo 'hidden'; ?>><?php echo $escape($error); ?></div>
  <section data-step="0">
    <p class="eyebrow">قبل از شروع</p><h2>هاست شما آماده است؟</h2><p class="muted">پیش‌نیازها را بررسی کرده‌ایم. دیتابیس و کاربر آن را در پنل هاست بسازید و اطلاعاتشان را همراه داشته باشید.</p>
    <div class="checks"><?php foreach($checks as $check): ?><div class="check <?php echo $check['ok']?'pass':'fail'; ?>"><span aria-hidden="true"><?php echo $check['ok']?'✓':'!'; ?></span><div><strong><?php echo $escape($check['label']); ?></strong><small><?php echo $escape($check['detail']); ?></small></div></div><?php endforeach; ?></div>
    <?php if(!$ready): ?><p class="message error">موارد قرمز را در پنل هاست اصلاح و صفحه را تازه کنید.</p><?php endif; ?>
  </section>
  <section data-step="1">
    <p class="eyebrow">هویت سایت</p><h2>با چه نامی شروع کنیم؟</h2><p class="muted">این اطلاعات بعداً از تنظیمات مدیریت قابل تغییر است.</p>
    <label>نام سایت<input name="site_name" value="<?php echo $escape($values['site_name']); ?>" maxlength="100" required placeholder="مثلاً خانه روشن"></label>
    <label>آدرس سایت<input name="site_url" type="url" dir="ltr" value="<?php echo $escape($values['site_url']); ?>" maxlength="500" required><small>آدرس نهایی سایت، همراه با https:// و مسیر پوشه در صورت وجود.</small></label>
    <label>توضیح کوتاه<textarea name="site_description" rows="3" maxlength="500" placeholder="درباره کسب‌وکار شما"><?php echo $escape($values['site_description']); ?></textarea></label>
  </section>
  <section data-step="2">
    <p class="eyebrow">ذخیره‌سازی اطلاعات</p><h2>اتصال به دیتابیس</h2><p class="muted">یک دیتابیس خالی MySQL یا MariaDB انتخاب کنید. نام دیتابیس و کاربر در هاست ممکن است پیشوند حساب شما را داشته باشند.</p>
    <div class="fields"><label>آدرس سرور<input name="db_host" dir="ltr" value="<?php echo $escape($values['db_host']); ?>" maxlength="253" required></label><label>پورت<input name="db_port" inputmode="numeric" pattern="[0-9]+" dir="ltr" value="<?php echo $escape($values['db_port']); ?>" required></label></div>
    <label>نام دیتابیس<input name="db_name" dir="ltr" value="<?php echo $escape($values['db_name']); ?>" maxlength="64" required autocomplete="off" placeholder="account_bazaarche"></label>
    <div class="fields"><label>نام کاربر دیتابیس<input name="db_user" dir="ltr" value="<?php echo $escape($values['db_user']); ?>" maxlength="100" required autocomplete="off"></label><label>رمز دیتابیس<input name="db_password" type="password" dir="ltr" autocomplete="off"></label></div>
    <button type="button" class="button secondary" id="check-database">بررسی اتصال</button><span id="database-status" class="hint" role="status" aria-live="polite"></span>
  </section>
  <section data-step="3">
    <p class="eyebrow">آخرین قدم</p><h2>حساب مدیر اصلی</h2><p class="muted">با ایمیل و رمز زیر وارد پنل مدیریت خواهید شد.</p>
    <div class="fields"><label>نام مدیر<input name="admin_name" value="<?php echo $escape($values['admin_name']); ?>" maxlength="100" required autocomplete="name"></label><label>ایمیل مدیر<input name="admin_email" type="email" dir="ltr" value="<?php echo $escape($values['admin_email']); ?>" maxlength="190" required autocomplete="username"></label></div>
    <div class="fields"><label>رمز عبور<input name="admin_password" type="password" dir="ltr" minlength="12" maxlength="72" required autocomplete="new-password"><small>حداقل ۱۲ کاراکتر؛ رمز قوی و یکتا انتخاب کنید.</small></label><label>تکرار رمز عبور<input name="admin_password_confirm" type="password" dir="ltr" minlength="12" maxlength="72" required autocomplete="new-password"></label></div>
    <fieldset class="sample-choices"><legend>شروع سایت</legend><label><input type="radio" name="sample_data" value="0" <?php if($values['sample_data']!=='1')echo 'checked'; ?>><span><strong>شروع خالی</strong><small>فقط ساختار سایت و تنظیمات اولیه؛ آمادهٔ محتوای شما.</small></span></label><label><input type="radio" name="sample_data" value="1" <?php if($values['sample_data']==='1')echo 'checked'; ?>><span><strong>همراه داده نمونه</strong><small>محصول، تصویر، مقاله و سفارش‌های آزمایشی برای آشنایی با سایت.</small></span></label></fieldset>
    <p class="hint">درگاه پرداخت و API تا زمان تنظیم دستی غیرفعال می‌مانند. نصب نمونه ممکن است کمی بیشتر طول بکشد.</p>
  </section>
  <footer class="form-actions"><button type="button" id="previous-step" class="button secondary" hidden>→ مرحله قبل</button><button type="button" id="next-step" class="button" <?php if(!$ready)echo 'disabled'; ?>>ادامه ←</button><button type="submit" id="install-submit" class="button" <?php if(!$ready)echo 'disabled'; ?>>نصب سایت ←</button><span id="install-progress" role="status" hidden><i class="spinner"></i> در حال نصب؛ این صفحه را نبندید…</span></footer>
  </form>
  <noscript><style>#next-step{display:none}</style><p class="hint">همهٔ بخش‌های فرم را کامل کنید و «نصب سایت» را بزنید.</p></noscript>
<?php endif; ?>
<div class="workspace-foot">بازارچه · نصب‌کننده سایت</div>
</section>
</main>
<?php if(!$locked): ?><script src="assets/installer.js" defer></script><?php endif; ?>
</body></html>
