<?php
/** Render without controllers, database queries, sessions or URL resolution. */
class StoreErrorHandler extends CErrorHandler
{
    protected function renderError()
    {
        $error=$this->getError();
        if((int)$error['code']>=500)error_log('Store error: '.$error['message'].' at '.($error['file']??'').':'.($error['line']??''));
        if(!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
            header('Cache-Control: no-store');
            header('X-Robots-Tag: noindex, nofollow');
        }
        require dirname(__DIR__).'/views/site/error.php';
    }
}
