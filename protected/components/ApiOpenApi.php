<?php
class ApiOpenApi
{
    public static function document()
    {
        $variants=array();foreach(ApiSchema::operations() as $name=>$operation)$variants[]=ApiSchema::object(array('operation'=>array('type'=>'string','enum'=>array($name)),'arguments'=>$operation['inputSchema']),array('operation','arguments'));
        return array('openapi'=>'3.1.0','info'=>array('title'=>'Bazaarche Management API','version'=>'1.0.0'),
            'servers'=>array(array('url'=>'/api/v1')),'security'=>array(array('bearerAuth'=>array())),
            'components'=>array('securitySchemes'=>array('bearerAuth'=>array('type'=>'http','scheme'=>'bearer','bearerFormat'=>'bz_ token'))),
            'paths'=>array('/call'=>array('post'=>array('operationId'=>'callManagementOperation','summary'=>'Execute a scoped operation',
                'parameters'=>array(array('name'=>'Idempotency-Key','in'=>'header','required'=>false,'description'=>'Required for every mutation; reuse only for the same request.','schema'=>array('type'=>'string','minLength'=>8,'maxLength'=>100))),
                'requestBody'=>array('required'=>true,'content'=>array('application/json'=>array('schema'=>array('oneOf'=>$variants)))),
                'responses'=>array('200'=>array('description'=>'JSON {ok:true,data}'),'401'=>array('description'=>'Invalid token'),'403'=>array('description'=>'Missing scope'),'409'=>array('description'=>'Stale revision or conflicting idempotency key'),'422'=>array('description'=>'Validation failure'),'429'=>array('description'=>'Rate limit')))),
                '/schema'=>array('get'=>array('operationId'=>'getOperations','responses'=>array('200'=>array('description'=>'Allowed operations and JSON schemas')))),
                '/whoami'=>array('get'=>array('operationId'=>'getCredential','responses'=>array('200'=>array('description'=>'Credential name, scopes and expiry'))))));
    }
}
