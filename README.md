# khantloonthu/php-router
 
[![Version](https://img.shields.io/packagist/v/khantloonthu/php-router.svg?style=flat-square)](https://packagist.org/packages/khantloonthu/php-router) 
[![Downloads](https://img.shields.io/packagist/dt/khantloonthu/php-router.svg?style=flat-square)](https://packagist.org/packages/khantloonthu/php-router/stats) 
[![License](https://img.shields.io/packagist/l/khantloonthu/php-router.svg?style=flat-square)](https://github.com/khantloonthu/php-router/blob/master/LICENSE)

# Table of Contents

- [Overview](#overview)
- [Installation](#installation)
- [Usage](#usage)
- [Routing](#routing)
- [Routing with params](#routing-with-params)
- [Global Middleware](#global-middleware)
- [Route-Specific middlewares](#route-specific-middlewares)
- [Aborting](#aborting)
- [Redirecting](#redirecting)
- [Handle custom error pages](#handle-custom-error-pages)


## Overview
This is a lightweight, simple, and extensible HTTP router implemented in PHP designed for basic to medium complexity web applications. It supports route registration, middleware, dynamic route parameters, error handling, and HTTP method overrides.
Built by Khant Loon Thu _([https://github.com/KhantLoonThu](https://github.com/KhantLoonThu))_.

## Installation 

Installation is possible using Composer

```
composer require khantloonthu/php-router
```

## Usage

Create an instance of `\KhantLoonThu\PhpRouter\Router`, define some routes onto it, and run it.

```php
// Require composer autoloader
require __DIR__ . '/vendor/autoload.php';

use \KhantLoonThu\PhpRouter\Router;

// Create Router instance
$router = new Router();

// Define routes
// ...

// Run it!
$router->run();
```

### Routing

Routing shorthands for single request methods are provided:

```php
$router->get('/', function() {
    return "Hello World";
});

$router->get('/about-us', fn() => require __DIR__ . '/about-us.php');

$router->get('/contact-us', function () {
    return require __DIR__ . '/contact-us.php';
});

$router->post('/pattern', function() {
    // ...
});

$router->put('/pattern', function() {
    // ...
});

$router->delete('/pattern', function() {
    // ...
});

```
Note: 
- [Routes must be hooked before $router->start(); is being called].
- [Unregistered routes will trigger 404 - not found].


### Routing with params
```php
// Required parameter
$router->get('/user/{id}', function($id) {
    return "User ID: " . $id;
});

// Multiple required parameters
$router->get('/post/{postId}/comment/{commentId}', function($postId, $commentId) {
    return "Post ID: $postId, Comment ID: $commentId";
});

```
Note: If route doesn't passed param, it will trigger 400 - Bad Request.

### Global Middleware
middlewares accept only `true`, `false`, and `['status' => $statusCode, 'message' => 'Unauthorized']`;
```php
function checkLoggedIn($request) {
    global $isLoggedIn;

    if (!$isLoggedIn) {
        return ['status' => 401, 'message' => 'Unauthorized: Please log in.'];
    }

    return true; // continue processing
}

$router->middleware('checkLoggedIn');

// or anonymous function style

$router->middleware(function ($request) {
    global $isLoggedIn;

    if (!$isLoggedIn) {
        return false; // triggers 403 Forbidden by default
    }

    return true;
});

```

### Route-Specific middlewares
Route specific middlewares accept only `true`, `false`, and `['status' => $statusCode, 'message' => 'Unauthorized']`;
```php
$router->get('/dashboard', function () {
    return require __DIR__ . '/dashboard.php';
}, [
    function checkAdmin() {
        $isAdmin = false;
        if (!$isAdmin) return false;
        return true;
    }
]);

```

### Aborting
If we want to abort before rendering we can use $router->abort($statusCode);
```php
$router->post('/create-user', function () use ($router) {
    $isAdmin = false;

    if (!$isAdmin) {
        $router->abort(403);
    }

    // continue with user creation...
});

```

### Redirecting
If we want to redirect after finishing the logic, we can use $router->redirect($path);
```php
$router->post('/send-mail', function () use ($router) {
    // send mail logic
    $mail->send();

    $router->redirect('/');
});

```

### Handle custom error pages
If we want to show Custom Error Pages like `403`, `404`, `500`, we can use $router->handle($statusCode, callable);
```php
$router->handle(404, function() {
    return require __DIR__ . '/404.php';
});

```
