<?php
/** Legacy adapter for extensions that used the category-only widget. */
class CategoryTree extends HierarchyTree {
 public function run(){$this->id='category-tree';parent::run();}
}
