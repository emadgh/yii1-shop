<?php
/** Allows service-owned transactions to participate in an atomic API mutation. */
class TransactionScope
{
    public static function begin()
    {
        $db=Yii::app()->db;
        return $db->currentTransaction ? new TransactionSavepoint($db) : $db->beginTransaction();
    }
}
