<div class="page-heading"><div><span class="eyebrow">اخبار و دانستنی‌ها</span><h1>مجله بازارچه</h1><p>تازه‌ترین یادداشت‌ها و مطالب راهنمای خرید.</p></div></div>
<?= CHtml::beginForm(array('blog/index'),'get',array('class'=>'searchbar','data-pjax-form'=>'1')) ?><?php if($tag)echo CHtml::hiddenField('tag',$tag); ?><?= CHtml::textField('q',$q,array('placeholder'=>'جستجو در عنوان و متن مقاله‌ها…','aria-label'=>'جستجوی مجله')) ?><?= CHtml::submitButton('جستجو',array('class'=>'btn btn-primary')) ?><?= CHtml::endForm() ?>
<?php $posts=$provider->getData(); if(!$posts): ?><div class="empty">هنوز مطلبی منتشر نشده است.</div><?php else: ?><div class="post-grid">
<?php foreach($posts as $post) $this->renderPartial('_card',array('post'=>$post)); ?>
</div><?php endif; ?>
<div class="pager"><?php $this->widget('CLinkPager',array('pages'=>$provider->pagination,'header'=>'','htmlOptions'=>array('class'=>'pagination'))); ?></div>
