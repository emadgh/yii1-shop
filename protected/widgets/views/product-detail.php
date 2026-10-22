<?php $p=$product; ?>
<div class="breadcrumbs"><?= CHtml::link('فروشگاه',array('shop/index'),array('data-pjax'=>'1')) ?> / <?= CHtml::encode($p->title) ?></div>
<div class="product-detail product-layout-<?= CHtml::encode($layout) ?>"><div class="gallery">
<div class="gallery-cover"><?php if($p->cover()): ?><?= MediaService::responsive($p->images[0]->filename,$p->title,array('id'=>'main-product-image')) ?><?php else: ?><div class="image-fallback">✧</div><?php endif; ?></div>
</div><div class="product-info"><span class="eyebrow"><?= $p->category?CHtml::encode($p->category->name):'بازارچه' ?></span><h1><?= CHtml::encode($p->title) ?></h1>
<div class="product-summary"><?= Content::markdown($p->summary) ?></div>
<?php if(Commerce::showPrices()): ?><div class="detail-price"><?= Money::format($p->salePrice()) ?> <?php if($p->discountPercent()): ?><span class="discount-badge"><?= $p->discountPercent() ?>٪ تخفیف</span><?php endif; ?></div>
<?php if(max((int)$p->compare_price,(int)$p->price)>$p->salePrice()): ?><div class="old-price"><?= Money::format(max($p->compare_price,$p->price)) ?></div><?php endif; ?>
<?php endif; ?><p class="stock-label"><?= (int)$p->stock>0?'● موجود در انبار ('.(int)$p->stock.' عدد)':'○ ناموجود' ?></p>
<?php if($variants): ?><div class="product-tags" aria-label="انتخاب رنگ و سایز"><?php foreach($variants as $variant): ?><?= CHtml::link(CHtml::encode($variant->variant_label?:$variant->title),$variant->link(),array('class'=>'tag','data-pjax'=>'1','aria-current'=>$variant->id===$p->id?'page':null)) ?><?php endforeach; ?></div><?php endif; ?><?php if(Commerce::enabled() && (int)$p->stock>0 && !(!$p->parent_product_id && $variants)): ?><?= CHtml::beginForm(array('cart/add'),'post',array('class'=>'buy-row','data-cart-form'=>'add')) ?>
<?= CHtml::hiddenField('product_id',$p->id) ?><?= CHtml::numberField('quantity',1,array('min'=>1,'max'=>min(99,(int)$p->stock),'class'=>'qty-field','aria-label'=>'تعداد')) ?><?= CHtml::submitButton('افزودن به سبد خرید',array('class'=>'btn btn-primary')) ?><?= CHtml::endForm() ?><?php endif; ?>
<div class="product-tags"><?php foreach($p->tags as $tag) echo CHtml::link('#'.CHtml::encode($tag->name),array('shop/index','tag'=>$tag->slug),array('class'=>'tag','data-pjax'=>'1')).' '; ?></div>
</div></div>
<?php $this->widget('ProductAlbums',array('product'=>$p,'albums'=>$albums)); ?>
<div class="detail-sections"><section class="panel"><h2>توضیحات محصول</h2><div class="rich-content"><?= Content::markdown($p->description) ?></div></section>
<?php if($p->specifications): ?><section class="panel"><h2>مشخصات فنی</h2><div class="spec-table">
<?php foreach($p->specifications as $s): ?><div class="spec-row"><strong><?= CHtml::encode($s->name) ?></strong><span><?= CHtml::encode($s->value) ?></span></div><?php endforeach; ?>
</div></section><?php endif; ?></div>
<?php if($p->metadata): ?><details class="metadata-panel"><summary>متادیتای ساختاریافته محصول</summary><dl>
<?php foreach($p->metadata as $m): ?><dt><?= CHtml::encode($m->meta_key) ?></dt><dd><?= CHtml::encode($m->meta_value) ?></dd><?php endforeach; ?>
</dl></details><?php endif; ?>

<?php
// Product JSON-LD provides searchable structured facts without interpreting arbitrary metadata as HTML.
$structured=array(
 '@context'=>'https://schema.org','@type'=>'Product','name'=>$p->title,
 'sku'=>$p->sku,'description'=>$p->summary,
 'offers'=>array('@type'=>'Offer','priceCurrency'=>'IRR','price'=>$p->salePrice()*10,
   'availability'=>((int)$p->stock>0?'https://schema.org/InStock':'https://schema.org/OutOfStock')),
);
if(!Commerce::showPrices() || !Commerce::enabled() || (!$p->parent_product_id && $variants))unset($structured['offers']);
if($p->cover()) $structured['image']=Yii::app()->request->hostInfo.$p->cover();
$props=array();
foreach($p->specifications as $spec) $props[]=array('@type'=>'PropertyValue','name'=>$spec->name,'value'=>$spec->value);
foreach($p->metadata as $meta) $props[]=array('@type'=>'PropertyValue','name'=>$meta->meta_key,'value'=>$meta->meta_value);
if($props) $structured['additionalProperty']=$props;
echo '<script type="application/ld+json">'.json_encode($structured,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_QUOT|JSON_HEX_APOS).'</script>';
?>
