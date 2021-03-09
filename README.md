# QRoute

Powerfull and Simple PHP Route library. Just one file. 


### Setting up Apache

Nothing special is required for Apache to work. We've include the .htaccess file in the public folder. If rewriting is not working for you, please check that the mod_rewrite module (htaccess support) is enabled in the Apache configuration.
.htaccess example

Below is an example of an working .htaccess file used by simple-php-router.

Simply create a new `.htaccess` file in your projects public directory and paste the contents below in your newly created file. 
This will redirect all requests to your `index.php` file (see Configuration section below).

```
RewriteEngine on
RewriteCond %{SCRIPT_FILENAME} !-f
RewriteCond %{SCRIPT_FILENAME} !-d
RewriteCond %{SCRIPT_FILENAME} !-l
RewriteRule ^(.*)$ index.php/$1
```

### Example 
```php
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

// Set base url of the main router file,
// remove if you route file in on root diretory
QRoute::BaseURL('/test/QRoute'); 


QRoute::GET('/')
    ->setCallback(function () {
        return ['msg' => 'Hello World'];
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