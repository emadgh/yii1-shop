<?php
class StoreIdentity extends CUserIdentity
{
    private $_id;public $otp='';
    private function passwordUser(){
        $user=User::model()->findByAttributes(array('email'=>strtolower(trim($this->username))));
        if(!$user || !$user->is_active || !password_verify($this->password,$user->password_hash)) {
           $this->errorCode=self::ERROR_PASSWORD_INVALID;
           return false;
        }
        return $user;
    }
    public function authenticate()
    {
        $user=$this->passwordUser();if(!$user)return false;
        if(!TwoFactor::authenticate($user,$this->otp)){$this->errorCode=self::ERROR_PASSWORD_INVALID;return false;}
        $this->accept($user);return true;
    }
    private function accept($user){
        $this->_id=(int)$user->id;
        $this->username=$user->name;
        $this->setState('role',$user->role);
        $this->errorCode=self::ERROR_NONE;
    }
    /** Password proof is kept in a short-lived session; the password is never stored. */
    public function beginLogin(){
        $this->_id=null;$this->errorCode=self::ERROR_PASSWORD_INVALID;
        Yii::app()->session->remove('login_challenge');$user=$this->passwordUser();if(!$user)return false;
        if(!$user->totp_secret){$this->accept($user);return 'authenticated';}
        Yii::app()->session->regenerateID(true);
        Yii::app()->session['login_challenge']=array('id'=>(int)$user->id,'expires'=>time()+300,'attempts'=>0,'proof'=>self::fingerprint($user));
        return 'challenge';
    }
    private static function fingerprint($user){return hash('sha256',$user->password_hash.'|'.$user->totp_secret);}
    public static function challengeUser(){
        $pending=Yii::app()->session['login_challenge'];
        if(!is_array($pending)||$pending['expires']<time()||$pending['attempts']>=5){Yii::app()->session->remove('login_challenge');return null;}
        $user=User::model()->findByPk($pending['id']);
        if(!$user||!$user->is_active||!$user->totp_secret||!hash_equals($pending['proof'],self::fingerprint($user))){Yii::app()->session->remove('login_challenge');return null;}
        return $user;
    }
    public static function authenticateChallenge($code){
        $user=self::challengeUser();if(!$user)return false;
        $pending=Yii::app()->session['login_challenge'];$pending['attempts']++;Yii::app()->session['login_challenge']=$pending;
        if(!TwoFactor::authenticate($user,$code))return false;
        Yii::app()->session->remove('login_challenge');$identity=new self($user->email,'');$identity->accept($user);return $identity;
    }
    public function getId() { return $this->_id; }
}
