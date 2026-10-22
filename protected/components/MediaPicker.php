<?php
/** Shared widget: name, value (media IDs), multiple, accept (MIME list), label. */
class MediaPicker extends CWidget {
 public $name='media_ids',$value=array(),$multiple=true,$accept='image/*',$label='انتخاب رسانه';
 public function run() {
   $ids=is_array($this->value)?$this->value:array_filter(explode(',',(string)$this->value));$rows=array_map(array('MediaService','describe'),MediaRepository::forPicker($ids));
   echo '<div class="media-picker" data-multiple="'.($this->multiple?'1':'0').'" data-accept="'.CHtml::encode($this->accept).'">';
   echo CHtml::hiddenField($this->name,implode(',',$ids),array('class'=>'media-value'));
   echo '<div class="media-selection">';foreach($rows as $r)echo '<div class="selected-media" data-id="'.(int)$r['id'].'">'.($r['thumb']?CHtml::image($r['thumb'],$r['original_name']):'<span>PDF / TXT</span>').'<small>'.CHtml::encode($r['original_name']).'</small></div>';echo '</div>';
   echo '<button type="button" class="btn btn-outline" data-open-media>'.CHtml::encode($this->label).'</button> <button type="button" class="btn btn-outline btn-sm" data-clear-media>پاک‌کردن انتخاب</button><small class="muted picker-help">'.($this->multiple?'انتخاب چند رسانه؛ ترتیب انتخاب، ترتیب نمایش است.':'انتخاب یک رسانه').'</small></div>';
 }
}
