<?php
/** Album SQL is kept together; business validation belongs to the album service. */
class ProductAlbumRepository
{
    public static function state($productId)
    {
        $db = Yii::app()->db;
        $params = array(':p' => $productId);
        return array(
            $db->createCommand('SELECT id,title,layout,sort_order FROM product_albums WHERE product_id=:p ORDER BY sort_order,id')->queryAll(true, $params),
            $db->createCommand('SELECT id,album_id,filename,sort_order FROM product_images WHERE product_id=:p ORDER BY sort_order,id')->queryAll(true, $params),
        );
    }

    public static function ids($productId)
    {
        return array_map('intval', Yii::app()->db->createCommand(
            'SELECT id FROM product_albums WHERE product_id=:p'
        )->queryColumn(array(':p' => $productId)));
    }

    public static function replace($product, $rows, $existingIds)
    {
        $db = Yii::app()->db;
        $db->createCommand()->delete('product_images', 'product_id=:p', array(':p' => $product->id));
        $keep = array();
        $imageOrder = 0;
        foreach ($rows as $order => $row) {
            $data = array('title' => $row['title'], 'layout' => $row['layout'], 'sort_order' => $order);
            if ($row['id']) {
                $id = $row['id'];
                $db->createCommand()->update('product_albums', $data, 'id=:id AND product_id=:p', array(':id' => $id, ':p' => $product->id));
            } else {
                $data['product_id'] = $product->id;
                $db->createCommand()->insert('product_albums', $data);
                $id = (int)$db->lastInsertID;
            }
            $keep[] = $id;
            foreach ($row['media'] as $media) {
                $db->createCommand()->insert('product_images', array(
                    'product_id' => $product->id, 'album_id' => $id,
                    'filename' => $media['filename'], 'alt_text' => $product->title,
                    'sort_order' => $imageOrder++,
                ));
            }
        }
        foreach (array_diff($existingIds, $keep) as $id) {
            $db->createCommand()->delete('product_albums', 'id=:id AND product_id=:p', array(':id' => $id, ':p' => $product->id));
        }
        return $imageOrder;
    }
}
