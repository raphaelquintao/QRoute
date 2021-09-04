<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'vendor/autoload.php';

use Quintao\QRoute;

// Set up global Headers
QRoute::HEADERS(['Access-Control-Allow-Origin' => (@$_SERVER['HTTP_ORIGIN']) ?: '*']);
QRoute::HEADERS(['Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS, HEAD']);
QRoute::HEADERS(['Access-Control-Allow-Headers' => 'DEV, cookie, *']);
QRoute::HEADERS(['Access-Control-Allow-Credentials' => 'true']);


// ORDERS MATTERS, so always put the functions in right order.
// If using HandleReturn this needs to be the first function,
// following by the errors handlers, helpers and routes


// Handling Returns Globally
QRoute::HandleReturn(function ($data) {
    QRoute::HEADERS(['Content-Type' => 'application/json']);
    
    $resp['result'] = $data;
    
    echo json_encode($resp);
});

// Handling Errors
QRoute::BadRequest(function () {
    $resp['error'] = true;
    $resp['msg'] = 'Bad Request';
    
    QRoute::STATUS(400);
    
    return $resp;
});

QRoute::NotFound(function () {
    $resp['error'] = true;
    $resp['msg'] = 'Route Not Found';
    
    QRoute::STATUS(404);
    
    return $resp;
});

QRoute::HREGISTER('401', function () {
    $resp['error'] = true;
    $resp['msg'] = 'Unauthorized';
    
    QRoute::STATUS(401);
    QRoute::HEADERS(['Content-Type' => 'application/json']);
    
    echo json_encode($resp);
    exit(); // Doing that HandleReturn is not called.
});

// Helper function to return raw png data
QRoute::HREGISTER('raw_png', function ($binary_data) {
    QRoute::HEADERS(['Content-Type' => 'image/png']);
    echo $binary_data;
    exit();
});


// Set base url of the main router file, remove if you route file in on root diretory.
// In that case the router.php is under http://mysite.com/qroute
QRoute::BaseURL('/qroute');

QRoute::GET('/')
    ->setCallback(function () {
        return ['msg' => 'Hello World'];
    });

// Get with params and regex
QRoute::GET('/im/{name:(\d\d\d)}') // Example: /im/007
->setCallback(function ($name) {
    return ['msg' => "Hello agent $name!"];
});

// Get with url params
QRoute::GET('/im/{name}') // URl Params always match the name on the callback function
->setQuery([], ['age']) // First group required, second group optional
->setCallback(function ($name, $q) {
    $msg = $q['age'] ? "Hello {$q['age']} years old $name!" : "Hello $name!";
    return ['msg' => $msg];
});

// All kind of params
QRoute::POST('/login/{url_param1}/{url_param2}')
    ->setParams([], ['username', 'password'])
    ->setQuery([], ['q1', 'q2'])
    // Urls Params go first matched by name, body parameters go next and query params are the last.
    ->setCallback(function ($url_param1, $url_param2, $body_params, $query_params) {
        
        return [
            'url_param1' => $url_param1,
            'url_param2' => $url_param2,
            'body_params' => $body_params,
            'query_params' => $query_params,
        ];
        
    });

// All kind of params
QRoute::PUT('/login/{url_param1}/{url_param2}')
    ->setParams([], ['username', 'password'])
    ->setQuery([], ['q1', 'q2'])
    // Urls Params go first matched by name, body parameters go next and query params are the last.
    ->setCallback(function ($url_param1, $url_param2, $body_params, $query_params) {
        return [
            'url_param1' => $url_param1,
            'url_param2' => $url_param2,
            'body_params' => $body_params,
            'query_params' => $query_params
        ];
        
    });


// The url params will match: favicon.<any extension>
QRoute::GET('/favicon.(.+)')
    ->setCallback(function () {
        $png_base64 = 'iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAMAAABEpIrGAAAABGdBTUEAALGPC/xhBQAAACBjSFJNAAB6JgAAgIQAAPoAAACA6AAAdTAAAOpgAAA6mAAAF3CculE8AAAAflBMVEUAAAD1RG72RW/2RW/1RG71RG71RG71RG71RG71RG71RG71RG71RG71RG71RG71RG71RG71RG71RG71RG71RG71RG71RG71RG71RG71RG71RG71RG71RG71RG71RG71RG71RG71RG71RG72RW/1RG71RG71RG71RG71RG7////ggj9AAAAAKHRSTlMA4gQFIy4TFMhyqNdfpz1PIoSpS4NhfDdgHzFGWicQP74NKAP8jMf9701eXgAAAAFiS0dEKcq3hSQAAAAHdElNRQflBxQEJyOYrVeCAAAAtUlEQVQ4y62T6w6CMAxGC6IORUSGooLiXd//CSW0oayL2RI5f0g/TrZCWoCO4GMRwJD/hXBiEfYvoykyY39OUdRVis6MWYgpUn7CYokkLCQUrcCLdI1kHGUUpSM1uckRzYKmqPBrcrtDSo5KivYjNem8wsngMw+5SSF7OIqpUn5CVSMngHNtUvk1KQamofLSC+JHaWvsnYIY+yuVN6MPuThtdDeWR6zeo42e7fP1S3iToDxO+ALNmDjmvWlyoAAAACV0RVh0ZGF0ZTpjcmVhdGUAMjAyMS0wNy0yMFQwNDozOTozNSswMDowMPxPFqcAAAAldEVYdGRhdGU6bW9kaWZ5ADIwMjEtMDctMjBUMDQ6Mzk6MzUrMDA6MDCNEq4bAAAAAElFTkSuQmCC';
        $png_bin = base64_decode($png_base64);
        QRoute::HCALL('raw_png', $png_bin);
    });


// Sub URLs
QRoute::SubURL('/web');

QRoute::GET('/')
    ->setCallback(function () {
        return ['msg' => '/web'];
    });

QRoute::SubURL('/images');

QRoute::GET('/')
    ->setCallback(function () {
        return ['msg' => '/images'];
    });

QRoute::SubURL('/');

QRoute::GET('/again')
    ->setCallback(function () {
        return ['msg' => '/again'];
    });


// Process all functions above. This is a mandatory function.
// This needs to be the last function and is called only once.
QRoute::finish();