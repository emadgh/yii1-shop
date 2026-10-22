<div class="page-heading"><div><span class="eyebrow">گام نهایی</span><h1>اطلاعات سفارش</h1><p>برای ثبت درخواست خرید، اطلاعات تماس و نشانی را وارد کنید.</p></div></div>
<div class="cart-layout"><div class="panel checkout-form"><h2>مشخصات خریدار</h2>
<?php $form=$this->beginWidget('CActiveForm',array('id'=>'checkout-form','enableClientValidation'=>false)); ?>
<?= $form->errorSummary($formModel) ?>
<?php foreach(array('customer_name','customer_email','customer_phone','shipping_address','notes') as $field): ?><div class="field">
<?= $form->labelEx($formModel,$field) ?>
<?php if(in_array($field,array('shipping_address','notes'),true)) echo $form->textArea($formModel,$field,array('rows'=>3)); else echo $form->textField($formModel,$field,array('dir'=>$field==='customer_email'?'ltr':'auto')); ?>
<?= $form->error($formModel,$field) ?></div><?php endforeach; ?>
<div class="field"><label for="coupon">کد تخفیف</label><div class="coupon-entry"><?= CHtml::textField('coupon',$coupon,array('id'=>'coupon','dir'=>'ltr','placeholder'=>'WELCOME10')) ?><button type="button" class="btn btn-outline" data-apply-coupon>بررسی کد</button></div><small id="coupon-feedback" aria-live="polite"><?= $discount?'تخفیف: '.Money::format($discount):'' ?></small></div><p class="muted note">کالاها به مدت <?= (int)ShopSettings::get('reservation_minutes',20) ?> دقیقه برای شما رزرو می‌شوند. پس از این زمان، رزرو پرداخت‌نشده آزاد می‌شود.</p>
<?= CHtml::submitButton('ثبت سفارش و رزرو کالا',array('class'=>'btn btn-primary')) ?>
<?php $this->endWidget(); ?></div><aside class="panel order-summary"><h2>خلاصه خرید</h2>
<?php foreach($summary['items'] as $item): ?><div class="summary-row"><span><?= CHtml::encode($item['product']->title) ?> × <?= (int)$item['quantity'] ?></span><strong><?= Money::format($item['lineTotal']) ?></strong></div><?php endforeach; ?>
<div class="summary-row total"><span>مجموع</span><strong><?= Money::format($summary['total']-$discount) ?></strong></div><p class="muted note">پس از ثبت سفارش، مدیر فروشگاه وضعیت آن را بررسی می‌کند. این مرحله تراکنش مالی انجام نمی‌دهد.</p></aside></div>
