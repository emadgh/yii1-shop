<?php
class SearchService {
 public static function normalize($value){return strtr(JalaliDate::digits($value),array('ي'=>'ی','ك'=>'ک',"\u{200c}"=>' '));}
 private static function expression($column){return "REPLACE(REPLACE(REPLACE(".$column.", 'ي', 'ی'), 'ك', 'ک'), '‌', ' ')";}
 public static function products($criteria,$query) {
   $query=mb_substr(trim(self::normalize((string)$query)),0,100);if($query==='')return;
   $like='%'.strtr($query,array('!'=>'!!','%'=>'!%','_'=>'!_')).'%';
   $columns=array('t.title','t.sku','t.summary','t.description');$clauses=array();foreach($columns as $i=>$column){$key=':search'.$i;$clauses[]=self::expression($column)." LIKE ".$key." ESCAPE '!'";$criteria->params[$key]=$like;}
   $clauses[]="EXISTS (SELECT 1 FROM product_attributes a WHERE a.product_id=t.id AND (a.name LIKE :ak ESCAPE '!' OR a.value LIKE :av ESCAPE '!'))";
   $clauses[]="EXISTS (SELECT 1 FROM product_meta m WHERE m.product_id=t.id AND (m.meta_key LIKE :mk ESCAPE '!' OR m.meta_value LIKE :mv ESCAPE '!'))";
   $clauses[]="EXISTS (SELECT 1 FROM product_tag pt JOIN tags tg ON tg.id=pt.tag_id WHERE pt.product_id=t.id AND tg.name LIKE :tg ESCAPE '!')";
   $clauses[]="EXISTS (SELECT 1 FROM categories c WHERE c.id=t.category_id AND c.name LIKE :cat ESCAPE '!')";
   foreach(array(':ak',':av',':mk',':mv',':tg',':cat') as $key)$criteria->params[$key]=$like;
   foreach(array('a.name','a.value','m.meta_key','m.meta_value','tg.name','c.name') as $column)foreach($clauses as &$clause)$clause=str_replace($column,self::expression($column),$clause);unset($clause);
   $criteria->addCondition('('.implode(' OR ',$clauses).')');
 }
}
