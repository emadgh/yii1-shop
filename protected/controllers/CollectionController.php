<?php
class CollectionController extends AppController {
 public function actionView($slug){$m=ProductCollection::model()->findByAttributes(array('slug'=>$slug,'is_active'=>1));if(!$m)$this->notFound();$this->pageTitle=$m->title;$this->render('view',array('collection'=>$m));}
}
