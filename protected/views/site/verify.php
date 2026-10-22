<div class="auth-wrap"><div class="auth-card"><span class="eyebrow">تأیید هویت</span><h1>کد ورود دومرحله‌ای</h1><p class="muted">کد شش‌رقمی برنامه Authenticator را وارد کنید. این مرحله تا پنج دقیقه معتبر است.</p>
<?php if($error): ?><div class="flash flash-error" role="alert"><?= CHtml::encode($error) ?></div><?php endif; ?>
<?= CHtml::beginForm(array('site/verify'),'post') ?>
<div class="field"><label for="otp">کد برنامه</label><?= CHtml::textField('otp','',array('id'=>'otp','required'=>true,'autocomplete'=>'one-time-code','inputmode'=>'numeric','maxlength'=>6,'pattern'=>'[0-9۰-۹٠-٩]{6}','dir'=>'ltr','autofocus'=>true)) ?></div>
<?= CHtml::submitButton('تأیید و ورود',array('class'=>'btn btn-primary btn-block')) ?><?= CHtml::endForm() ?>
<?= CHtml::link('ورود با حساب دیگر',array('site/login')) ?></div></div>
