<?php
/** Legacy adapter for extensions that used the category-only widget. */
class CategoryTree extends HierarchyTree {
 public function run(){$this->id='category-tree';if(!$this->data)$this->data=HierarchyTreeQuery::data('categories',null,$this->selected);parent::run();}
}
