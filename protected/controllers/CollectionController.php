<?php
class CollectionController extends AppController {
 public function actionView($slug){$data=CollectionQuery::page($slug);$m=$data['collection'];if(!$m)$this->notFound();$this->pageTitle=$m->title;$this->render('view',$data);}
}
