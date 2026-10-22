<?php
class ProductEditorQuery
{
    public static function form($product)
    {
        $input = new ProductEditForm;
        $input->albums = ProductAlbumQuery::editorRows($product);
        $input->albumRevision = $product->isNewRecord ? '' : ProductAlbumService::revision($product->id);
        $input->stockSnapshot = $product->isNewRecord ? 0 : (int)$product->stock;
        if (!$product->isNewRecord) {
            $input->tagsText = $product->tagsText();
            foreach ($product->specifications as $spec) {
                $input->specs[] = array('key' => $spec->name, 'value' => $spec->value);
            }
            foreach ($product->metadata as $meta) {
                $input->meta[] = array('key' => $meta->meta_key, 'value' => $meta->meta_value);
            }
        }
        return $input;
    }

    public static function parentOptions($excludeId)
    {
        $criteria = new CDbCriteria;
        $criteria->condition = 'parent_product_id IS NULL';
        if ($excludeId) {
            $criteria->addCondition('id<>:excluded');
            $criteria->params[':excluded'] = $excludeId;
        }
        $criteria->order = 'title';
        return CHtml::listData(Product::model()->findAll($criteria), 'id', 'title');
    }

    public static function lowStock()
    {
        return Product::model()->findAll(array(
            'condition' => "status='published' AND stock<=5 AND NOT EXISTS (SELECT 1 FROM products child WHERE child.parent_product_id=t.id)",
            'order' => 'stock,id', 'limit' => 20,
        ));
    }
}
