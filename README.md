# QRoute

Powerful and Simple PHP Route library. Just one file.

## Installation

Navigate to your project folder in terminal and run the following command:

`composer require quintao/qroute`

### Setting up Apache

Make sure the `mod_rewrite` module (htaccess support) is enabled in the Apache configuration.

Simply create a new `.htaccess` file in your projects public directory and paste the contents below in your newly
created file. This will redirect all requests to your `index.php` file.

```apache
RewriteEngine on
RewriteCond %{SCRIPT_FILENAME} !-f
RewriteCond %{SCRIPT_FILENAME} !-d
RewriteCond %{SCRIPT_FILENAME} !-l
RewriteRule ^(.*)$ index.php/$1
```

### Settings up Nginx

You can enable url-rewriting by adding the following configuration for the Nginx configuration-file.

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### Supported methods

* GET
* POST
* PUT
* PATCH
* DELETE
* OPTIONS

## Routes Example

```php
require 'vendor/autoload.php';

use Quintao\QRoute;

// Set up global Headers
QRoute::HEADERS(['Access-Control-Allow-Origin' => (@$_SERVER['HTTP_ORIGIN']) ?: '*']);
QRoute::HEADERS(['Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS, HEAD']);
QRoute::HEADERS(['Access-Control-Allow-Headers' => 'DEV, cookie, *']);
QRoute::HEADERS(['Access-Control-Allow-Credentials' => 'true']);

// ORDERS MATTERS, so always put the functions in right order.
// If using HandleReturn this needs to be the first function,
// following by the errors handlers and the urls 


// Handling Returns Globally
QRoute::HandleReturn(function ($resp) {
    QRoute::HEADERS(['Content-Type' => 'application/json']);

    $r['result'] = $resp;

    echo json_encode($r);
});

// Handling Errors
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

QRoute::HREGISTER('401', function () {
    $resp['error'] = true;
    $resp['msg'] = 'Unauthorized';
    
    QRoute::HEADERS(['HTTP/1.1' => '401 Unauthorized']);
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
// In that case the index.php is under http://mysite.com/test/qroute
QRoute::BaseURL('/test/qroute'); 

QRoute::GET('/')
    ->setCallback(function () {
        return ['msg' => 'Hello World'];
    });

// Get with params and regex
QRoute::GET('/hi/{name:(\d\d\d)}') // Example: /hi/007
    ->setCallback(function ($name) {
        return ['msg' => "Hello agent $name!"];
    });    

// Get with url params
QRoute::GET('/hi/{name}') // URl Params always match the name on the callback function
    ->setQuery(['age']) // First group required, second group optional
    // Query params always goes as last argument on callback function
    ->setCallback(function ($name, $q) {               
        return ['msg' => "Hello {$q['age']} years old $name"];        
    });



// All kind of params
QRoute::PUT('/login/{url_param}')
    ->setParams([], ['password'])
    ->setQuery([], ['q1', 'q2' ])
    ->setCallback(function ($url_param, $body_param, $query_param) {
//        $password = $p['password'];
//        $password = QRoute::InputPOST('password');
//        parse_str(file_get_contents("php://input", "r"), $password);
//        $password = $_POST;

//        if ($passwd == $password) {
//            $session->start();
//        }
        return [
            'url_param' => $url_param,
            'body_param' => $body_param,
            'query_param' => $query_param
        ];
    });
    

// ADVANCED USAGE

// The url params will match: favicon.<any extension>
QRoute::GET('/favicon(.)+')
    ->setCallback(function (){
        $png_base64 = 'iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAMAAABEpIrGAAAABGdBTUEAALGPC/xhBQAAACBjSFJNAAB6JgAAgIQAAPoAAACA6AAAdTAAAOpgAAA6mAAAF3CculE8AAAAflBMVEUAAAD1RG72RW/2RW/1RG71RG71RG71RG71RG71RG71RG71RG71RG71RG71RG71RG71RG71RG71RG71RG71RG71RG71RG71RG71RG71RG71RG71RG71RG71RG71RG71RG71RG71RG71RG72RW/1RG71RG71RG71RG71RG7////ggj9AAAAAKHRSTlMA4gQFIy4TFMhyqNdfpz1PIoSpS4NhfDdgHzFGWicQP74NKAP8jMf9701eXgAAAAFiS0dEKcq3hSQAAAAHdElNRQflBxQEJyOYrVeCAAAAtUlEQVQ4y62T6w6CMAxGC6IORUSGooLiXd//CSW0oayL2RI5f0g/TrZCWoCO4GMRwJD/hXBiEfYvoykyY39OUdRVis6MWYgpUn7CYokkLCQUrcCLdI1kHGUUpSM1uckRzYKmqPBrcrtDSo5KivYjNem8wsngMw+5SSF7OIqpUn5CVSMngHNtUvk1KQamofLSC+JHaWvsnYIY+yuVN6MPuThtdDeWR6zeo42e7fP1S3iToDxO+ALNmDjmvWlyoAAAACV0RVh0ZGF0ZTpjcmVhdGUAMjAyMS0wNy0yMFQwNDozOTozNSswMDowMPxPFqcAAAAldEVYdGRhdGU6bW9kaWZ5ADIwMjEtMDctMjBUMDQ6Mzk6MzUrMDA6MDCNEq4bAAAAAElFTkSuQmCC';
        $png_bin = base64_decode($png_base64);
        
        QRoute::HCALL('raw_png', $png_bin);
    });

// Process all functions above. This is a mandatory function.
// This needs to be the last function and is called only once. 
QRoute::finish();

```