<?php
class CollectionQuery
{
    public static function options()
    {
        return CHtml::listData(ProductCollection::model()->findAll(), 'id', 'title');
    }

    public static function page($slug)
    {
        $collection = ProductCollection::model()->findByAttributes(array('slug' => $slug, 'is_active' => 1));
        if (!$collection) {
            throw new CHttpException(404, 'مورد درخواستی پیدا نشد.');
        }
        $products = $collection->products(500);
        CatalogQuery::primeProducts($products);
        MediaRepository::primeFilenames(array($collection->image));
        return array('collection' => $collection, 'products' => $products);
    }
}
