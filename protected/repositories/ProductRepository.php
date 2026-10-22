<?php
/** Product persistence primitives; callers own the transaction. */
class ProductRepository
{
    /** Repair imported MySQL identity counters outside any business transaction. */
    public static function advanceIdentityPastHistory()
    {
        $db = Yii::app()->db;
        if ($db->driverName !== 'mysql') {
            return false;
        }
        if ($db->currentTransaction && $db->currentTransaction->active) {
            throw new LogicException('Identity maintenance must run outside transactions.');
        }
        $highest = (int)$db->createCommand(
            'SELECT GREATEST(COALESCE((SELECT MAX(id) FROM products),0),COALESCE((SELECT MAX(product_id) FROM inventory_movements),0))'
        )->queryScalar();
        $table = $db->createCommand('SHOW CREATE TABLE products')->queryRow();
        preg_match('/\bAUTO_INCREMENT=(\d+)/', $table['Create Table'], $match);
        $next = isset($match[1]) ? (int)$match[1] : 1;
        if ($next > $highest) {
            return false;
        }
        $db->createCommand('ALTER TABLE products AUTO_INCREMENT='.($highest + 1))->execute();
        return true;
    }

    public static function lockStock($id)
    {
        $db = Yii::app()->db;
        $sql = 'SELECT stock FROM products WHERE id=:id';
        if ($db->driverName === 'mysql') {
            $sql .= ' FOR UPDATE';
        }
        $stock = $db->createCommand($sql)->queryScalar(array(':id' => $id));
        if ($stock === false) {
            throw new Exception('محصول دیگر موجود نیست.');
        }
        return (int)$stock;
    }

    public static function replaceProperties($productId, $specs, $metadata)
    {
        $db = Yii::app()->db;
        foreach (array('product_attributes', 'product_meta') as $table) {
            $db->createCommand()->delete($table, 'product_id=:id', array(':id' => $productId));
        }
        foreach ($specs as $order => $pair) {
            $db->createCommand()->insert('product_attributes', array(
                'product_id' => $productId, 'name' => $pair[0],
                'value' => $pair[1], 'sort_order' => $order,
            ));
        }
        foreach ($metadata as $pair) {
            $db->createCommand()->insert('product_meta', array(
                'product_id' => $productId, 'meta_key' => $pair[0], 'meta_value' => $pair[1],
            ));
        }
    }

    public static function hasActiveReservations($id)
    {
        return (bool)Yii::app()->db->createCommand(
            "SELECT COUNT(*) FROM order_items i JOIN orders o ON o.id=i.order_id
             WHERE i.product_id=:id AND o.stock_held=1
             AND o.status NOT IN ('completed','shipped','paid')"
        )->queryScalar(array(':id' => $id));
    }

    public static function delete($product)
    {
        Yii::app()->db->createCommand()->delete(
            'collection_products', 'product_id=:id', array(':id' => $product->id)
        );
        $product->delete();
    }
}
