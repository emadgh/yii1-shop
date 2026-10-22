<?php
class UserQuery
{
    public static function all()
    {
        return User::model()->findAll(array('order' => 'id'));
    }
}
