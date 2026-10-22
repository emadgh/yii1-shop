<?php
class NavigationQuery
{
    public static function all()
    {
        $locations = array('header' => array(), 'footer' => array());
        $rows = Yii::app()->db->createCommand(
            'SELECT * FROM menu_items WHERE is_active=1 ORDER BY sort_order,id'
        )->queryAll();
        foreach ($rows as $row) {
            if (isset($locations[$row['location']])) {
                $locations[$row['location']][] = $row;
            }
        }
        return $locations;
    }
}
