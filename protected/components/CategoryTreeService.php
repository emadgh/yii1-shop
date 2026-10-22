<?php
/** Compatibility facade; shared operations live in HierarchyTreeService. */
class CategoryTreeService {
 public static function revision($rows=null){return (new HierarchyTreeService('categories'))->revision($rows);}
 public static function move($id,$target,$position,$revision){return (new HierarchyTreeService('categories'))->move($id,$target,$position,$revision);}
}
