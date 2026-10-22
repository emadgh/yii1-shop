<?php
class BlogController extends AppController
{
    public function actionIndex($tag=null,$q=null) {
      $criteria=new CDbCriteria;
      $criteria->condition="t.status='published'";
      if($q) { $search=new CDbCriteria;foreach(array('t.title','t.excerpt','t.body') as $column)$search->addSearchCondition($column,mb_substr(trim((string)$q),0,100),true,'OR');$criteria->mergeWith($search); }
      if($tag) {
        $found=Tag::model()->findByAttributes(array('slug'=>$tag));
        if(!$found) $this->notFound();
        $criteria->addCondition('EXISTS (SELECT 1 FROM post_tag pt WHERE pt.post_id=t.id AND pt.tag_id=:tagId)');
        $criteria->params[':tagId']=$found->id;
      }
      $criteria->order='t.created_at DESC';
      $provider=new CActiveDataProvider('Post',array('criteria'=>$criteria,'pagination'=>array('pageSize'=>9)));
      $this->pageTitle='مجله و بلاگ';
      $this->render('index',array('provider'=>$provider,'q'=>$q,'tag'=>$tag));
    }
    public function actionView($slug) {
      $post=Post::model()->findByAttributes(array('slug'=>$slug,'status'=>'published'));
      if(!$post) $this->notFound();
      $this->pageTitle=$post->seo_title ? $post->seo_title : $post->title;
      $this->metaDescription=mb_substr($post->seo_description ? $post->seo_description : Content::plain($post->excerpt),0,160);
      $this->render('view',array('post'=>$post));
    }
}
