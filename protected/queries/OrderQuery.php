<?php
class OrderQuery
{
    public static function events($orderId)
    {
        return Yii::app()->db->createCommand(
            'SELECT * FROM order_events WHERE order_id=:id ORDER BY id'
        )->queryAll(true, array(':id' => $orderId));
    }
}
