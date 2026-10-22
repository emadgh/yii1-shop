<?php
class TwoFactor {
 public static function secret(){return self::encode(random_bytes(20));}
 private static function encode($bytes){$bits='';foreach(str_split($bytes) as $b)$bits.=str_pad(decbin(ord($b)),8,'0',STR_PAD_LEFT);$out='';foreach(str_split($bits,5) as $chunk)$out.='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'[bindec(str_pad($chunk,5,'0'))];return $out;}
 public static function crypt($value,$decrypt=false){
  $file=Yii::app()->basePath.'/config/security.key';if(!is_file($file)){if($decrypt)throw new Exception('Encryption key missing');$h=@fopen($file,'x');if($h){fwrite($h,bin2hex(random_bytes(32)));fclose($h);@chmod($file,0600);}}
  $key=hex2bin(trim(file_get_contents($file)));if(strlen($key)!==32)throw new Exception('Encryption key invalid');
  if($decrypt){$data=base64_decode($value,true);$result=openssl_decrypt(substr($data,28),'aes-256-gcm',$key,OPENSSL_RAW_DATA,substr($data,0,12),substr($data,12,16));if($result===false)throw new Exception('Secret cannot be decrypted');return $result;}
  $iv=random_bytes(12);$tag='';$encrypted=openssl_encrypt($value,'aes-256-gcm',$key,OPENSSL_RAW_DATA,$iv,$tag);return base64_encode($iv.$tag.$encrypted);
 }
 public static function code($secret,$counter){$bits='';foreach(str_split($secret) as $ch){$n=strpos('ABCDEFGHIJKLMNOPQRSTUVWXYZ234567',$ch);if($n===false)throw new Exception('Invalid secret');$bits.=str_pad(decbin($n),5,'0',STR_PAD_LEFT);}$bytes='';foreach(str_split($bits,8) as $b)if(strlen($b)===8)$bytes.=chr(bindec($b));$hash=hash_hmac('sha1',pack('N2',0,$counter),$bytes,true);$offset=ord($hash[19])&15;$value=unpack('N',substr($hash,$offset,4))[1]&0x7fffffff;return str_pad((string)($value%1000000),6,'0',STR_PAD_LEFT);}
 public static function step($secret,$code){$code=JalaliDate::digits($code);if(!preg_match('/^[0-9]{6}$/D',$code))return false;$now=intdiv(time(),30);foreach(array(0,-1,1) as $offset)if(hash_equals(self::code($secret,$now+$offset),$code))return $now+$offset;return false;}
 public static function authenticate($user,$code){if(!$user->totp_secret)return true;if(!is_string($code))return false;$step=self::step(self::crypt($user->totp_secret,true),$code);if($step===false)return false;return Yii::app()->db->createCommand('UPDATE users SET totp_step=:s WHERE id=:id AND totp_step<:s2')->execute(array(':s'=>$step,':s2'=>$step,':id'=>$user->id))===1;}
}
