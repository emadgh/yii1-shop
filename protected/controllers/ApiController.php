<?php
/** Stateless API: no cookie/session authentication and no HTML error responses. */
class ApiController extends CController
{
    public function runAction($action)
    {
        header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');
        try{
            $request=Yii::app()->request;
            // Loopback development may use HTTP on any port, with a loopback Host.
            $local=in_array($_SERVER['REMOTE_ADDR']??'',array('127.0.0.1','::1'),true)&&preg_match('/^(?:localhost|127\.0\.0\.1|\[::1\])(?::\d+)?$/D',strtolower($_SERVER['HTTP_HOST']??''));
            if(!$request->isSecureConnection&&!$local)throw new CHttpException(403,'HTTPS required');
            if(isset($_SERVER['HTTP_ORIGIN']))throw new CHttpException(403,'Browser cross-origin API calls are disabled');
            ApiTokenService::authenticate($_SERVER['HTTP_AUTHORIZATION']??($_SERVER['REDIRECT_HTTP_AUTHORIZATION']??''));
            parent::runAction($action);
        }catch(Exception $e){
            $status=$e instanceof CHttpException?$e->statusCode:422;
            if($e instanceof CDbException){$status=409;$message='Database conflict; read current data before retrying.';}else $message=$e->getMessage();
            if($status===401)header('WWW-Authenticate: Bearer');if($status===429)header('Retry-After: 60');
            $this->json(array('ok'=>false,'error'=>array('status'=>$status,'message'=>$message)),$status);
        }finally{ApiTokenService::$actor=null;}
    }
    private function method($method){if(Yii::app()->request->requestType!==$method)throw new CHttpException(405,'Method not allowed');}
    private function json($value,$status=200){http_response_code($status);echo CJSON::encode($value);}
    public function actionWhoami()
    {
        $this->method('GET');$token=ApiTokenService::$actor;
        $this->json(array('ok'=>true,'data'=>array('name'=>$token['name'],'user_id'=>(int)$token['user_id'],'scopes'=>$token['permissions'],'expires_at'=>$token['expires_at'])));
    }
    public function actionSchema()
    {
        $this->method('GET');$operations=array_filter(ApiSchema::operations(),function($operation){return !array_diff($operation['requiredScopes'],ApiTokenService::$actor['permissions']);});
        $this->json(array('ok'=>true,'data'=>array('version'=>'1','money_unit'=>'toman','timezone'=>'Asia/Tehran','operations'=>$operations)));
    }
    public function actionOpenapi()
    {
        $this->method('GET');$this->json(ApiOpenApi::document());
    }
    public function actionCall()
    {
        $this->method('POST');
        if(stripos($_SERVER['CONTENT_TYPE']??'','application/json')!==0)throw new CHttpException(415,'application/json required');
        if((int)($_SERVER['CONTENT_LENGTH']??0)>14500000)throw new CHttpException(413,'Request too large');
        $raw=file_get_contents('php://input',false,null,0,14500001);if(strlen($raw)>14500000)throw new CHttpException(413,'Request too large');
        $body=json_decode($raw,true,64);if(json_last_error()!==JSON_ERROR_NONE||!is_array($body)||!is_string($body['operation']??null)||!is_array($body['arguments']??null)||array_diff(array_keys($body),array('operation','arguments')))throw new CHttpException(400,'Expected {operation, arguments} JSON object');
        $result=ApiGatewayService::call($body['operation'],$body['arguments'],$_SERVER['HTTP_IDEMPOTENCY_KEY']??null);
        $this->json(array('ok'=>true,'data'=>$result));
    }
}
