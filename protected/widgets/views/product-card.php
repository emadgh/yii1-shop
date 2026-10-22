<article class="product-card card-<?= CHtml::encode($style) ?>">
<?= CHtml::link($product->cover() ? MediaService::responsive($product->images[0]->filename,$product->title,array('loading'=>'lazy')) : '<span class="image-fallback">✧</span>',$product->link(),array('class'=>'product-media','data-pjax'=>'1')) ?>
<?php if(Commerce::showPrices() && $product->discountPercent()): ?><span class="discount-badge card-discount"><?= $product->discountPercent() ?>٪</span><?php endif; ?>
<div class="product-body">
  <div class="product-category"><?= $product->category?CHtml::encode($product->category->name):'محصولات' ?></div>
  <h3><?= CHtml::link(CHtml::encode($product->title),$product->link(),array('data-pjax'=>'1')) ?></h3>
  <div class="product-footer"><div><?php if(Commerce::showPrices()): ?><?php if($product->salePrice()<$product->price): ?><small class="old-price"><?= Money::format($product->price) ?></small><?php endif; ?><span class="price"><?= Money::format($product->salePrice()) ?></span><?php endif; ?></div>
  <?= CHtml::link('مشاهده ←',$product->link(),array('class'=>'detail-link','data-pjax'=>'1')) ?></div>
</div></article>
