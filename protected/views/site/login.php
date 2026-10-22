<div class="auth-wrap"><div class="auth-card"><span class="eyebrow">ورود امن</span><h1>ورود به پنل مدیریت</h1><p class="muted">با ایمیل و رمز عبور مدیر وارد شوید.</p>
<?php if($error): ?><div class="flash flash-error"><?= CHtml::encode($error) ?></div><?php endif; ?>
<?= CHtml::beginForm(array('site/login'),'post') ?>
<div class="field"><label for="email">ایمیل</label><?= CHtml::emailField('email',isset($_POST['email'])?$_POST['email']:'',array('id'=>'email','required'=>true,'autocomplete'=>'username','dir'=>'ltr')) ?></div>
<div class="field"><label for="password">رمز عبور</label><?= CHtml::passwordField('password','',array('id'=>'password','required'=>true,'autocomplete'=>'current-password','dir'=>'ltr')) ?></div>
<div class="field"><label for="otp">کد دومرحله‌ای؛ اگر فعال کرده‌اید</label><?= CHtml::textField('otp','',array('id'=>'otp','autocomplete'=>'one-time-code','inputmode'=>'numeric','maxlength'=>6)) ?></div><?= CHtml::submitButton('ورود به داشبورد',array('class'=>'btn btn-primary btn-block')) ?>
<?= CHtml::endForm() ?></div></div>
