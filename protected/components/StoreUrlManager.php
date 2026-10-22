<?php
class StoreUrlManager extends CUrlManager {
 public function parseUrl($request){$path='/'.$request->pathInfo;
  if($request->getRequestType()==='GET'&&preg_match('~^/(product|blog|category|collection)/~',$path)){$target=Yii::app()->db->createCommand('SELECT target FROM redirects WHERE source=:p')->queryScalar(array(':p'=>$path));if($target&&preg_match('~^/(product|blog|category|collection)/[a-z0-9-]+$~D',$target))$request->redirect(Yii::app()->baseUrl.$target,true,301);}
  return parent::parseUrl($request);
 }
}
