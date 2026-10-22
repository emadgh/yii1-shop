<?php
class HierarchyTreeQuery
{
    public static function data($type = 'categories', $location = null, $selectedId = 0)
    {
        $service = new HierarchyTreeService($type, $location);
        $rows = $service->rows();
        $counts = array();
        if ($type === 'categories') {
            foreach (Yii::app()->db->createCommand(
                'SELECT category_id,COUNT(*) count FROM products GROUP BY category_id'
            )->queryAll() as $row) {
                $counts[$row['category_id']] = (int)$row['count'];
            }
            MediaRepository::primeFilenames(array_column($rows, 'image'));
        }
        $selected = null;
        foreach ($rows as $row) {
            if ((int)$row['id'] === (int)$selectedId) {
                $selected = array('id' => (int)$row['id'], 'parent' => (int)$row['parent_id'], 'sort' => (int)$row['sort_order']);
            }
        }
        $revision = $service->revision($rows);
        return array('rows' => Hierarchy::flatten($rows), 'revision' => $revision,
            'menu' => $type === 'menu_items', 'counts' => $counts,
            'editorState' => array('revision' => $revision, 'options' => $service->options($selectedId, $rows), 'selected' => $selected));
    }
}
