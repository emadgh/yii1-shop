<?php
/** The bearer-only JSON API never authenticates using browser cookies. */
class ApiHttpRequest extends CHttpRequest
{
    public function validateCsrfToken($event)
    {
        if(preg_match('~^api/v1/(call|schema|openapi|whoami)$~D',$this->pathInfo,$match)&&Yii::app()->urlManager->parseUrl($this)==='api/'.$match[1])return;
        parent::validateCsrfToken($event);
    }
}
