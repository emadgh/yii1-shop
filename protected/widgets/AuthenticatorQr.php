<?php
/** Local QR generation: provisioning secrets never leave this application. */
class AuthenticatorQr extends CWidget {
 public $secret,$account,$issuer;
 public function run(){
  $uri='otpauth://totp/'.rawurlencode($this->issuer.':'.$this->account).'?'.http_build_query(array('secret'=>$this->secret,'issuer'=>$this->issuer,'algorithm'=>'SHA1','digits'=>6,'period'=>30),'','&',PHP_QUERY_RFC3986);
  $options=new \chillerlan\QRCode\QROptions(array('outputType'=>\chillerlan\QRCode\Output\QROutputInterface::MARKUP_SVG,'outputBase64'=>true));
  $src=(new \chillerlan\QRCode\QRCode($options))->render($uri);
  echo CHtml::image($src,'کد QR برای افزودن حساب به Authenticator',array('class'=>'authenticator-qr','width'=>240,'height'=>240));
 }
}
