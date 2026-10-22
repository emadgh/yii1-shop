<section class="panel album-editor" data-album-editor>
<div class="panel-heading"><h2>آلبوم‌های محصول</h2><button type="button" class="btn btn-outline btn-sm" data-add-album>+ افزودن آلبوم</button></div>
<p class="muted note">هر آلبوم، تصاویر و سبک نمایش مستقل دارد. برای جابه‌جایی از دستگیره یا دکمه‌های بالا و پایین استفاده کنید. تغییرات با ذخیره محصول ثبت می‌شوند؛ اولین تصویرِ اولین آلبومِ دارای تصویر، تصویر اصلی محصول است.</p>
<?= CHtml::hiddenField('albums_present','1') ?><?= CHtml::hiddenField('album_revision',is_string($this->revision)?$this->revision:'') ?>
<?php $this->beginWidget('SortableList',array('htmlOptions'=>array('data-album-list'=>'1','class'=>'album-editor-list')));foreach($rows as $index=>$row)$this->render('_album-editor-row',array('index'=>$index,'row'=>$row));$this->endWidget(); ?>
<p class="album-empty muted" data-album-empty <?= $rows?'hidden':'' ?>>آلبومی اضافه نشده است. برای شروع «افزودن آلبوم» را بزنید.</p><p role="status" aria-live="polite" class="muted note" data-album-feedback></p>
<template data-album-template><?php $this->render('_album-editor-row',array('index'=>'__INDEX__','row'=>array('id'=>0,'title'=>'آلبوم جدید','layout'=>'grid','media_ids'=>''))); ?></template>
</section>
