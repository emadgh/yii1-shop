<article class="post-card">
<?= CHtml::link($post->cover_image?MediaService::responsive($post->cover_image,$post->title,array('loading'=>'lazy')):'<span class="image-fallback">✦</span>',$post->link(),array('class'=>'post-media','data-pjax'=>'1')) ?>
<div class="post-body"><div class="post-date"><?= CHtml::encode(substr($post->created_at,0,10)) ?></div><h3><?= CHtml::link(CHtml::encode($post->title),$post->link(),array('data-pjax'=>'1')) ?></h3><p><?= CHtml::encode(Content::plain($post->excerpt)) ?></p><?= CHtml::link('ادامه مطلب ←',$post->link(),array('class'=>'text-link','data-pjax'=>'1')) ?></div>
</article>
