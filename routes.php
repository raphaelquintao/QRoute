<?php

use Quintao\QRoute;

QRoute::HandleReturn(function ($resp) {
    QRoute::HEADERS(['Content-Type' => 'application/json']);
    
    $r['result'] = $resp;
    
    echo json_encode($r);
});


QRoute::BadRequest(function () {
    $resp['error'] = true;
    $resp['msg'] = 'Bad Request';
    
    QRoute::HEADERS(['HTTP/1.1' => '400 Bad Request']);
    
    return $resp;
});

QRoute::NotFound(function () {
    $resp['error'] = true;
    $resp['msg'] = 'Route Not Found';
    
    QRoute::HEADERS(['HTTP/1.1' => '404 Not Found']);
    
    return $resp;
});


QRoute::GET('/test')
    ->setCallback(function () {
        
        return ['test' => true, 'msg' => 'Hello World'];
        
    });


QRoute::catchErrors();