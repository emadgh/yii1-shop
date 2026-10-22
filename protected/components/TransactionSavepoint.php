<?php
class TransactionSavepoint extends CComponent
{
    private $db,$name,$active=true;
    public function __construct($db)
    {
        $this->db=$db;$this->name='service_'.bin2hex(random_bytes(8));
        $db->createCommand('SAVEPOINT '.$this->name)->execute();
    }
    public function getActive(){return $this->active;}
    public function commit(){if($this->active){$this->db->createCommand('RELEASE SAVEPOINT '.$this->name)->execute();$this->active=false;}}
    public function rollback(){if($this->active){$this->db->createCommand('ROLLBACK TO SAVEPOINT '.$this->name)->execute();$this->db->createCommand('RELEASE SAVEPOINT '.$this->name)->execute();$this->active=false;}}
}
