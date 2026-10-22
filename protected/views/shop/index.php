<div class="page-heading"><div><span class="eyebrow">کاتالوگ بازارچه</span><h1><?= Commerce::enabled()?"فروشگاه":"کاتالوگ محصولات" ?></h1><p>محصولات را جستجو و بر اساس نیاز خود مرتب کنید.</p></div><span class="count-badge"><?= (int)$provider->totalItemCount ?> محصول</span></div>
<div class="shop-layout"><aside class="filter-panel"><h3>دسته‌بندی‌ها</h3>
<?= CHtml::link('همه محصولات',array('shop/index'),array('data-pjax'=>'1','class'=>!$category?'filter-active':'')) ?>
<?php foreach($categories as $cat): ?><?= CHtml::link(CHtml::encode($cat->name),array('shop/category','slug'=>$cat->slug),array('data-pjax'=>'1','class'=>$cat->slug===$category?'filter-active':'')) ?><?php endforeach; ?></aside>
<div class="shop-results" id="shop-results-region" data-pjax-region="1"><?php if($category): $current=Category::model()->findByAttributes(array("slug"=>$category)); ?><section class="category-description"><h2><?= CHtml::encode($current->name) ?></h2><div class="rich-content"><?= Content::markdown($current->description) ?></div><?php $this->widget("CategoryTiles",array("parent"=>$current->id)); ?></section><?php endif; ?>
<?= CHtml::beginForm(array('shop/index'),'get',array('class'=>'searchbar','data-pjax-form'=>'1')) ?>
<?php if($category)echo CHtml::hiddenField('category',$category);if($tag)echo CHtml::hiddenField('tag',$tag); ?><?= CHtml::textField('q',$q,array('placeholder'=>'نام، مشخصات فنی، برند، تگ یا متادیتا…','aria-label'=>'جستجوی محصول')) ?>
<?= CHtml::dropDownList('sort',$sort,array('new'=>'جدیدترین','price-asc'=>'ارزان‌ترین','price-desc'=>'گران‌ترین'),array('aria-label'=>'مرتب‌سازی')) ?>
<?= CHtml::submitButton('جستجو',array('class'=>'btn btn-primary')) ?><?= CHtml::endForm() ?>
<?php $products=$provider->getData(); if(!$products): ?><div class="empty">محصولی مطابق جستجو پیدا نشد.</div><?php else: ?><div class="product-grid">
<?php foreach($products as $p): $this->renderPartial('_card',array('product'=>$p)); endforeach; ?>
</div><?php endif; ?>
<div class="pager"><?php $this->widget('CLinkPager',array('pages'=>$provider->pagination,'header'=>'','nextPageLabel'=>'بعدی','prevPageLabel'=>'قبلی','htmlOptions'=>array('class'=>'pagination'))); ?></div>
</div></div>
