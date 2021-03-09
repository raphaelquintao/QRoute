<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("vendor/autoload.php");

//echo "test";

use Quintao\QRoute;

QRoute::HEADERS(['Access-Control-Allow-Origin' => (@$_SERVER['HTTP_ORIGIN']) ?: '*']);
QRoute::HEADERS(['Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS, HEAD']);
QRoute::HEADERS(['Access-Control-Allow-Headers' => 'DEV, cookie, *']);
QRoute::HEADERS(['Access-Control-Allow-Credentials' => 'true']);

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


QRoute::BaseURL('/test/QRoute');

QRoute::GET('/')
    ->setCallback(function () {
        
        return ['test' => true, 'msg' => 'Hello World'];
        
    });

QRoute::GET('/i')
    ->setCallback(function () {
        return $_SERVER;
    });

QRoute::POST('/test3/{as}')
    ->setParams(null, ['p1', 'p2'])
    ->setCallback(function ($as, $pasd) {

//        $p[] = QRoute::InputGET('p1');
//        $p[] = QRoute::InputPOST('p1');
        
        return [$as, $pasd];
    });


QRoute::POST('/test2/{as:(ok)}')
//    ->setParams(null, ['p1', 'p2'])
    ->setCallback(function ($as) {

//        $p[] = QRoute::InputGET('p1');
//        $p = QRoute::InputPOST('p1');
        
//        return [$as, $p];
        
        return [$as];
    });




QRoute::GET('/test/{as}')
//    ->setParams(null, ['p1', 'p2'])
    ->setCallback(function ($as) {
        
        $p[] = QRoute::InputGET('p1');
//        $p[] = QRoute::InputPOST('p1');
        
        return [$as, $p];
    });


QRoute::GET('/hi/{name:(Raphael)}')
    ->setCallback(function ($name) {
        
        return ['test' => true, 'msg' => "Hi $name!"];
        
    });

QRoute::GET('/hi/{name}')
    ->setQuery(['age'])
    ->setCallback(function ($name, $q) {
        
//        $age = QRoute::InputGET('age');
        
        return ['test' => true, 'msg' => "Hello {$q['age']} years old $name"];
        
    });

QRoute::GET('/hi/{name}')
    ->setCallback(function ($name) {

        return ['test' => true, 'msg' => "Hello $name"];

    });




QRoute::catchErrors();