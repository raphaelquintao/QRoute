# QRoute

Powerfull and Simple PHP Route library. Just one file. 


### Setting up Apache

Simply create a new `.htaccess` file in your projects public directory and paste the contents below in your newly created file. 
This will redirect all requests to your `index.php` file.

```apache
RewriteEngine on
RewriteCond %{SCRIPT_FILENAME} !-f
RewriteCond %{SCRIPT_FILENAME} !-d
RewriteCond %{SCRIPT_FILENAME} !-l
RewriteRule ^(.*)$ index.php/$1
```

### Settings up NGinx
...

### Supported methods
GET, POST, PUT, PATCH, DELETE, OPTIONS

### Example 
```php
include("vendor/autoload.php");

use Quintao\QRoute;

QRoute::HEADERS(['Access-Control-Allow-Origin' => (@$_SERVER['HTTP_ORIGIN']) ?: '*']); // Enable Cors
QRoute::HEADERS(['Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS, HEAD']);
QRoute::HEADERS(['Access-Control-Allow-Headers' => 'DEV, cookie, *']);
QRoute::HEADERS(['Access-Control-Allow-Credentials' => 'true']);


// Hadling Returns Globaly
QRoute::HandleReturn(function ($resp) {
    QRoute::HEADERS(['Content-Type' => 'application/json']);

    $r['result'] = $resp;

    echo json_encode($r);
});

// Set base url of the main router file, remove if you route file in on root diretory.
// In that case the index.php is under http://mysite.com/test/QRoute
QRoute::BaseURL('/test/QRoute'); 


// Order matters, so always put the urls in right order.

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

```