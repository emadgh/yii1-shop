<?php
class Post extends BaseRecord
{
    public static function model($className=__CLASS__) { return parent::model($className); }
    public function tableName() { return 'posts'; }
    public function rules() { return array(
      array('title, status','required'),array('title, seo_title','length','max'=>255),
      array('slug','length','max'=>190),array('slug','unique'),
      array('slug','match','pattern'=>'/^[a-z0-9]+(?:-[a-z0-9]+)*$/'),
      array('status','in','range'=>array('draft','published')),
      array('excerpt, body, seo_description','safe'),
    ); }
    public function relations() { return array('tags'=>array(self::MANY_MANY,'Tag','post_tag(post_id,tag_id)')); }
    protected function beforeValidate() {
       if(!$this->slug) $this->slug=Slug::unique('Post',$this->title,$this->isNewRecord?null:$this->id);
       return parent::beforeValidate();
    }
    public function link() { return Yii::app()->createUrl('blog/view',array('slug'=>$this->slug)); }
}
