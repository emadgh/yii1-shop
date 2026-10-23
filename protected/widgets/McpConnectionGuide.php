<?php
/** Renders a new credential only in the issuing response; never persists the secret. */
class McpConnectionGuide extends CWidget
{
    public $issued;
    public $apiUrl;
    public $enabled=false;
    public function run()
    {
        if(!$this->issued||empty($this->issued['token']))return;
        $root='/absolute/path/to/yii1-shop';$private='/absolute/private/ai-client.json';
        $direct=array('mcpServers'=>array('bazaarche'=>array('command'=>'node','args'=>array($root.'/integrations/ai/mcp-server.mjs'),'env'=>array('BAZAARCHE_API_URL'=>$this->apiUrl,'BAZAARCHE_API_TOKEN'=>$this->issued['token']))));
        $privateConfig=$direct; $privateConfig['mcpServers']['bazaarche']['env']=array('BAZAARCHE_TOKEN_FILE'=>$private);
        $json=function($value){return json_encode($value,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);};
        $cs=Yii::app()->clientScript;$cs->registerCssFile(Yii::app()->baseUrl.'/assets/css/mcp-setup.css');$cs->registerScriptFile(Yii::app()->baseUrl.'/assets/js/mcp-setup.js',CClientScript::POS_END);
        $this->render('mcp-connection-guide',array('direct'=>$json($direct),'privateConfig'=>$json($privateConfig),'credential'=>$json(array('url'=>$this->apiUrl,'token'=>$this->issued['token'])),'root'=>$root,'privatePath'=>$private));
    }
}
