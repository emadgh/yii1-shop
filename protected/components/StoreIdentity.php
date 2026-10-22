<?php
class StoreIdentity extends CUserIdentity
{
    private $_id;
    public function authenticate()
    {
        $user=User::model()->findByAttributes(array('email'=>strtolower(trim($this->username))));
        if(!$user || !$user->is_active || !password_verify($this->password,$user->password_hash)) {
           $this->errorCode=self::ERROR_PASSWORD_INVALID;
           return false;
        }
        $this->_id=(int)$user->id;
        $this->username=$user->name;
        $this->setState('role',$user->role);
        $this->errorCode=self::ERROR_NONE;
        return true;
    }
    public function getId() { return $this->_id; }
}
