<?php
class ProductAlbumQuery
{
    public static function forProduct($productId)
    {
        return ProductAlbum::model()->with('images')->findAllByAttributes(
            array('product_id' => $productId), array('order' => 't.sort_order,t.id')
        );
    }

    public static function editorRows($product)
    {
        if ($product->isNewRecord) {
            return array(array('id' => 0, 'title' => 'تصاویر محصول', 'layout' => 'grid', 'media_ids' => ''));
        }
        $albums = self::forProduct($product->id);
        $filenames = array();
        foreach ($albums as $album) {
            foreach ($album->images as $image) {
                $filenames[] = $image->filename;
            }
        }
        MediaRepository::primeFilenames($filenames);
        $rows = array();
        foreach ($albums as $album) {
            $ids = array();
            foreach ($album->images as $image) {
                $media = MediaRepository::byFilename($image->filename);
                if ($media) {
                    $ids[] = $media['id'];
                }
            }
            $rows[] = array('id' => (int)$album->id, 'title' => $album->title,
                'layout' => $album->layout, 'media_ids' => implode(',', $ids));
        }
        return $rows;
    }
}
