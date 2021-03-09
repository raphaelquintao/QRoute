# QRoute

Powerfull and Simple PHP Route library. Just one file. 

## Instalation
Navigate to your project folder in terminal and run the following command:

`composer require quintao/qroute`

### Setting up Apache

Make sure the mod_rewrite module (htaccess support) is enabled in the Apache configuration.

Simply create a new `.htaccess` file in your projects public directory and paste the contents below in your newly created file. 
This will redirect all requests to your `index.php` file.

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
include("vendor/autoload.php");

use Quintao\QRoute;

// Set up global Headers
QRoute::HEADERS(['Access-Control-Allow-Origin' => (@$_SERVER['HTTP_ORIGIN']) ?: '*']);
QRoute::HEADERS(['Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS, HEAD']);
QRoute::HEADERS(['Access-Control-Allow-Headers' => 'DEV, cookie, *']);
QRoute::HEADERS(['Access-Control-Allow-Credentials' => 'true']);

// ORDERS MATTERS, so always put the functions in right order.
// If using HandleReturn this needs to be the first function,
// following by the errors handlers and the urls 


// Hadling Returns Globaly
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


// Set base url of the main router file, remove if you route file in on root diretory.
// In that case the index.php is under http://mysite.com/test/QRoute
QRoute::BaseURL('/test/QRoute'); 

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
QRoute::GET('/hi/{name}')
    ->setQuery(['age']) // First group required, second group optional
    // Query params always goes as last argument on callback function
    ->setCallback(function ($name, $q) {               
        return ['msg' => "Hello {$q['age']} years old $name"];        
    });


// Get without url params
QRoute::GET('/hi/{name}')
    ->setCallback(function ($name) {
        return ['msg' => "Hello $name"];
    });

// Post with params
QRoute::POST('/hi/{name}')
    ->setParams(null, ['p1', 'p2']) // First group required, second group optional
    // Post params always goes as first argument on callback function
    ->setCallback(function ($p, $name) {

        return [$p, $name];
    });

// Process all functions above. This is a mandatory function.
// This needs to be the last function and is called only once. 
QRoute::finish();

```