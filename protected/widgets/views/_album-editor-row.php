<?php $prefix='albums['.$index.']';$titleId='album-title-'.$index; ?>
<section class="album-editor-row" data-sortable-item data-album-row>
<div class="album-row-head"><button type="button" class="tree-drag-handle" data-sort-handle aria-label="جابه‌جایی آلبوم" title="برای جابه‌جایی بکشید">⠿</button><button type="button" class="album-toggle" data-toggle-album aria-expanded="true" aria-label="بستن جزئیات آلبوم" title="بستن جزئیات آلبوم">⌃</button><strong data-sort-title data-album-heading><?= CHtml::encode($row['title']) ?></strong><span class="muted note" data-album-count></span><div class="album-row-actions"><button type="button" data-sort-step="before" aria-label="انتقال آلبوم به بالا">↑</button><button type="button" data-sort-step="after" aria-label="انتقال آلبوم به پایین">↓</button><button type="button" data-remove-album aria-label="حذف آلبوم">×</button></div></div>
<?= CHtml::hiddenField($prefix.'[id]',$row['id']) ?><div class="album-editor-body" data-album-body>
<div class="form-row"><div class="field"><label for="<?= CHtml::encode($titleId) ?>">عنوان آلبوم *</label><?= CHtml::textField($prefix.'[title]',$row['title'],array('id'=>$titleId,'required'=>true,'maxlength'=>150,'data-album-title'=>'1')) ?></div><div class="field"><label for="album-layout-<?= CHtml::encode($index) ?>">سبک نمایش</label><?= CHtml::dropDownList($prefix.'[layout]',$row['layout'],ProductAlbum::layouts(),array('id'=>'album-layout-'.$index,'data-album-layout'=>'1')) ?></div></div>
<p class="muted note" data-album-layout-hint></p>
</div>
<?php $this->widget('MediaPicker',array('name'=>$prefix.'[media_ids]','value'=>$row['media_ids'],'multiple'=>true,'accept'=>'image/*','label'=>'انتخاب یا آپلود تصاویر آلبوم')); ?>
</section>
