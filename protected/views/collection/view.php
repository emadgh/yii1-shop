<div class="section-heading"><h1><?= CHtml::encode($collection->title) ?></h1></div>
<?php if($collection->image): ?><div class="collection-cover"><?= MediaService::responsive($collection->image,$collection->title) ?></div><?php endif; ?>
<div class="rich-content"><?= Content::markdown($collection->description) ?></div>
<?php $this->widget('ProductRail',array('products'=>$products,'presentation'=>'grid')); ?>
