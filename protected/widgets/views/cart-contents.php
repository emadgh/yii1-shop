<?php if(!$summary['items']): ?><div class="empty"><p>سبد خرید شما خالی است</p><?= CHtml::link('مشاهده فروشگاه',array('shop/index'),array('class'=>'btn btn-outline','data-pjax'=>'1')) ?></div>
<?php else: ?><div class="<?= $compact?'mini-cart-layout':'cart-layout' ?>"><div class="<?= $compact?'mini-cart-lines':'panel cart-lines' ?>">
<?php foreach($summary['items'] as $item): $p=$item['product']; ?><div class="cart-line">
<a class="mini-photo" href="<?= CHtml::encode($p->link()) ?>" data-pjax="1"><?php if($p->cover()) echo CHtml::image($p->cover(),$p->title,array('loading'=>'lazy'));else echo '✧'; ?></a>
<div class="cart-line-title"><strong><?= CHtml::link(CHtml::encode($p->title),$p->link(),array('data-pjax'=>'1')) ?></strong><span><?= Money::format($p->salePrice()) ?></span></div>
<?= CHtml::beginForm(array('cart/update'),'post',array('class'=>'cart-qty','data-cart-form'=>'update')) ?>
<?= CHtml::hiddenField('product_id',$p->id,array('id'=>false)) ?><?= CHtml::numberField('quantity',$item['quantity'],array('id'=>false,'min'=>0,'max'=>min(99,(int)$p->stock),'aria-label'=>'تعداد '.$p->title)) ?><?= CHtml::submitButton('بروزرسانی',array('class'=>'btn btn-outline btn-sm')) ?><button type="submit" name="remove" value="1" formnovalidate class="cart-remove" aria-label="حذف <?= CHtml::encode($p->title) ?>">حذف</button><?= CHtml::endForm() ?>
<strong class="line-total"><?= Money::format($item['lineTotal']) ?></strong></div><?php endforeach; ?></div>
<div class="<?= $compact?'mini-cart-summary':'panel order-summary' ?>"><?php if(!$compact): ?><h2>خلاصه سفارش</h2><?php endif; ?><div class="summary-row total"><span>مبلغ کل</span><strong><?= Money::format($summary['total']) ?></strong></div>
<?php if($compact): ?><?= CHtml::link('مشاهده سبد خرید',array('cart/index'),array('class'=>'btn btn-outline btn-block','data-pjax'=>'1')) ?><?php endif; ?>
<?= CHtml::link('ادامه و ثبت سفارش ←',array('cart/checkout'),array('class'=>'btn btn-primary btn-block','data-pjax'=>'1')) ?><p class="muted note">رزرو کالا پس از ثبت نهایی سفارش انجام می‌شود.</p></div></div><?php endif; ?>
