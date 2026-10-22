<?php
if(PHP_SAPI!=='cli')exit(1);
$copy=dirname(__DIR__).'/protected/runtime/hierarchy-check-'.bin2hex(random_bytes(5)).'.sqlite';copy(dirname(__DIR__).'/protected/runtime/store.sqlite',$copy);putenv('SHOP_DSN=sqlite:'.$copy);require __DIR__.'/bootstrap.php';$checks=0;
function hierarchyCheck($ok,$label){global $checks;if(!$ok)throw new Exception('FAILED '.$label);$checks++;echo "PASS $label\n";}
function hierarchyReject($fn,$status,$label){try{$fn();}catch(CHttpException $e){hierarchyCheck($e->statusCode===$status,$label);return;}throw new Exception('FAILED '.$label);}
try{
 $ids=array();foreach(array('header','footer') as $location)foreach(array('a','b','child') as $key){$m=new MenuItem;$m->label=$location.$key;$m->url='/shop';$m->location=$location;$m->is_active=1;$m->parent_id=$key==='child'?$ids[$location.'a']:null;hierarchyCheck(Hierarchy::save($m),'create '.$location.$key);$ids[$location.$key]=(int)$m->id;}
 $header=new HierarchyTreeService('menu_items','header');$footer=new HierarchyTreeService('menu_items','footer');$old=$header->revision();$footerRevision=$footer->revision();
 $footer->move($ids['footera'],$ids['footerb'],'inside',$footerRevision);hierarchyCheck($header->revision()===$old,'another location does not invalidate header revision');
 $header->move($ids['headera'],$ids['headerb'],'inside',$old);hierarchyCheck((int)MenuItem::model()->findByPk($ids['headerchild'])->parent_id===$ids['headera'],'menu descendants remain with moved subtree');
 hierarchyCheck(!isset($header->options($ids['headera'])[$ids['headerchild']])&&!isset($header->options()[$ids['footera']]),'parent choices exclude descendants and other locations');
 $revision=$header->revision();hierarchyReject(function()use($header,$ids,$revision){$header->move($ids['headera'],$ids['footera'],'inside',$revision);},422,'cross-location target rejected');hierarchyCheck($header->revision()===$revision,'invalid move has no writes');
 hierarchyReject(function()use($header,$ids,$old){$header->move($ids['headera'],0,'root',$old);},409,'stale menu move rejected');
 $model=MenuItem::model()->findByPk($ids['headera']);$model->label='not persisted';hierarchyReject(function()use($model,$old){Hierarchy::save($model,$old);},409,'stale menu form rejected');hierarchyCheck(MenuItem::model()->findByPk($model->id)->label!=='not persisted','stale form preserves saved label');
 hierarchyReject(function(){new HierarchyTreeService('menu_items',array('header'));},400,'invalid menu scope rejected');
 echo "All $checks shared hierarchy checks passed on isolated database.\n";
}finally{Yii::app()->db->active=false;@unlink($copy);}
