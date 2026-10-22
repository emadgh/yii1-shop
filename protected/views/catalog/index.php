<div class="section-head"><div><span class="eyebrow">کتابخانه شرکت</span><h1>کاتالوگ‌های شرکت</h1><p class="muted">معرفی محصولات و خدمات؛ نسخه PDF را دانلود کنید.</p></div></div>
<div class="company-catalogs">
<?php foreach($provider->getData() as $catalog): ?>
<article class="company-catalog"><div class="company-catalog-cover"><?php if($catalog['image_filename']): ?><?= MediaService::responsive($catalog['image_filename'],$catalog['title'],array('loading'=>'lazy')) ?><?php else: ?><span aria-hidden="true">PDF</span><?php endif; ?></div><div class="company-catalog-body"><h2><?= CHtml::encode($catalog['title']) ?></h2><div class="prose"><?= Content::markdown($catalog['description']) ?></div><p class="muted">PDF · <?= number_format((int)ceil($catalog['pdf_size']/1024)) ?> KB · <?= JalaliDate::format($catalog['updated_at']) ?></p><?= CHtml::link('دانلود کاتالوگ',array('catalog/download','id'=>$catalog['id']),array('class'=>'btn btn-primary','data-no-pjax'=>'1','download'=>'catalog-'.$catalog['id'].'.pdf')) ?></div></article>
<?php endforeach; ?>
</div>
<?php if(!$provider->totalItemCount): ?><section class="panel"><p>هنوز کاتالوگی منتشر نشده است.</p></section><?php endif; ?>
<?php $this->widget('CLinkPager',array('pages'=>$provider->pagination,'header'=>'','htmlOptions'=>array('class'=>'pagination'))); ?>
