<article class="article-page"><div class="breadcrumbs"><?= CHtml::link('مجله',array('blog/index'),array('data-pjax'=>'1')) ?> / <?= CHtml::encode($post->title) ?></div>
<span class="eyebrow">مجله بازارچه</span><h1><?= CHtml::encode($post->title) ?></h1><div class="article-subtitle"><?= Content::markdown($post->excerpt) ?></div>
<?php if($post->cover_image): ?><div class="article-cover"><?= MediaService::responsive($post->cover_image,$post->title) ?></div><?php endif; ?>
<div class="article-text"><?= Content::markdown($post->body) ?></div>
<div class="product-tags"><?php foreach($post->tags as $tag) echo CHtml::link('#'.CHtml::encode($tag->name),array('blog/index','tag'=>$tag->slug),array('class'=>'tag','data-pjax'=>'1')).' '; ?></div>
</article>
