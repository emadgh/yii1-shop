<?php
/** Read models for catalog pages. Widgets receive these prepared records. */
class CatalogQuery
{
    public static function categoryTiles($parent = null)
    {
        $criteria = new CDbCriteria;
        $criteria->order = 'sort_order,id';
        if ($parent) {
            $criteria->compare('parent_id', $parent);
        } else {
            $criteria->addCondition('parent_id IS NULL OR parent_id=0');
        }
        $rows = Category::model()->findAll($criteria);
        MediaRepository::primeFilenames(array_map(function ($row) { return $row->image; }, $rows));
        return $rows;
    }

    public static function primeProducts($products)
    {
        $filenames = array();
        foreach ($products as $product) {
            foreach ($product->images as $image) {
                $filenames[] = $image->filename;
            }
        }
        MediaRepository::primeFilenames($filenames);
    }

    public static function detail($slug)
    {
        $product = Product::model()->with('images', 'category', 'tags', 'specifications', 'metadata')
            ->findByAttributes(array('slug' => $slug, 'status' => 'published'));
        if (!$product) {
            throw new CHttpException(404, 'مورد درخواستی پیدا نشد.');
        }
        $albums = ProductAlbumQuery::forProduct($product->id);
        $filenames = array_map(function ($image) { return $image->filename; }, $product->images);
        foreach ($albums as $album) {
            foreach ($album->images as $image) {
                $filenames[] = $image->filename;
            }
        }
        MediaRepository::primeFilenames($filenames);
        return array('product' => $product, 'albums' => $albums, 'variants' => $product->variants());
    }

    public static function featured($limit)
    {
        $products = Product::model()->with('images', 'category')->findAll(array(
            'condition' => "t.status='published' AND t.featured=1", 'order' => 't.id DESC', 'limit' => (int)$limit,
        ));
        self::primeProducts($products);
        return $products;
    }
}
