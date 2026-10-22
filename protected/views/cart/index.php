<div class="page-heading"><div><span class="eyebrow">پیش از ثبت سفارش</span><h1>سبد خرید</h1><p>اقلام انتخاب‌شده و تعداد آن‌ها را بررسی کنید.</p></div></div>
<?php if(!$summary['items']): ?><div class="empty"><h2>سبد خرید شما خالی است</h2><?= CHtml::link('مشاهده فروشگاه',array('shop/index'),array('class'=>'btn btn-primary','data-pjax'=>'1')) ?></div>
<?php else: ?><div class="cart-layout"><div class="panel cart-lines">
<?php foreach($summary['items'] as $item): $p=$item['product']; ?><div class="cart-line">
<div class="mini-photo"><?php if($p->cover()) echo CHtml::image($p->cover(),$p->title); else echo '✧'; ?></div>
<div class="cart-line-title"><strong><?= CHtml::link(CHtml::encode($p->title),$p->link(),array('data-pjax'=>'1')) ?></strong><span><?= Money::format($p->salePrice()) ?></span></div>
<?= CHtml::beginForm(array('cart/update'),'post',array('class'=>'cart-qty')) ?>
<?= CHtml::hiddenField('product_id',$p->id) ?><?= CHtml::numberField('quantity',$item['quantity'],array('min'=>0,'max'=>min(99,(int)$p->stock),'aria-label'=>'تعداد')) ?><?= CHtml::submitButton('بروزرسانی',array('class'=>'btn btn-outline btn-sm')) ?><?= CHtml::endForm() ?>
<strong class="line-total"><?= Money::format($item['lineTotal']) ?></strong></div><?php endforeach; ?></div>
<div class="panel order-summary"><h2>خلاصه سفارش</h2><div class="summary-row"><span>جمع کالاها</span><strong><?= Money::format($summary['total']) ?></strong></div><div class="summary-row total"><span>مبلغ کل</span><strong><?= Money::format($summary['total']) ?></strong></div>
<?= CHtml::link('ادامه و ثبت سفارش ←',array('cart/checkout'),array('class'=>'btn btn-primary btn-block','data-pjax'=>'1')) ?><p class="muted note">هزینه ارسال و پرداخت آنلاین در این نسخه تعبیه نشده است.</p></div></div><?php endif; ?>
