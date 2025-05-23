<?php

/**
 * @package     KhantLoonThu\PhpRouter
 * @author      Khant Loon Thu <khant@hmmserver.net>
 * @copyright   Copyright (c) 2025 Khant Loon Thu
 * @license     MIT
 */

namespace KhantLoonThu\PhpRouter;

/**
 * Router Class
 * 
 * A simple HTTP router supporting route handling, middleware,
 * route parameters, and custom error responses.
 * 
 * TODO: Support for HEAD, OPTIONS (CORS), PATCH methods, and JSON response helper ($this->json($data, $status)) (optional)
 * TODO: Route Caching (for medium-large apps, not needed for basic websites)
 * TODO: data types for route parameters for route parameters (e.g., int, string, etc.) --> this is super important.
 */
class Router
{
    /**
     * All registered routes.
     * Each route includes method, path, handler, and optional middleware.
     * 
     * @var array
     */
    private array $routes = [];

    /**
     * Global middleware functions executed before any route handler.
     *
     * @var array
     */
    private array $middleware = [];

    /**
     * Error handlers indexed by HTTP status code.
     *
     * @var array
     */
    private array $errorHandlers = [];

    /**
     * The HTTP method of the current request.
     *
     * @var string
     */
    private string $requestedMethod = '';

    /**
     * Register a global middleware function.
     * Middleware runs before route-specific middleware and handlers.
     *
     * @param callable $middleware Middleware function
     * @return void
     */
    public function middleware(callable $middleware): void
    {
        $this->middleware[] = $middleware;
    }

    /**
     * Register a GET route.
     *
     * @param string   $path       Route path (e.g. /users/{id})
     * @param callable $handler    Route handler function
     * @param array    $middleware Optional route-specific middleware
     * @return void
     */
    public function get(string $path, callable $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    /**
     * Register a POST route.
     *
     * @param string   $path       Route path
     * @param callable $handler    Route handler function
     * @param array    $middleware Optional route-specific middleware
     * @return void
     */
    public function post(string $path, callable $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    /**
     * Register a PUT route.
     *
     * @param string   $path       Route path
     * @param callable $handler    Route handler function
     * @param array    $middleware Optional route-specific middleware
     * @return void
     */
    public function put(string $path, callable $handler, array $middleware = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middleware);
    }

    /**
     * Register a DELETE route.
     *
     * @param string   $path       Route path
     * @param callable $handler    Route handler function
     * @param array    $middleware Optional route-specific middleware
     * @return void
     */
    public function delete(string $path, callable $handler, array $middleware = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    /**
     * Add a route to the internal route table.
     *
     * @param string   $method     HTTP method (GET, POST, etc.)
     * @param string   $path       Route path
     * @param callable $handler    Route handler
     * @param array    $middleware Optional route-specific middleware
     * @return void
     */
    protected function addRoute(string $method, string $path, callable $handler, array $middleware = []): void
    {
        $this->routes[] = compact('method', 'path', 'handler', 'middleware');
    }

    /**
     * Register a custom handler for an HTTP error status code.
     *
     * @param int      $statusCode HTTP status code (e.g. 404, 403)
     * @param callable $handler    Error handler function
     * @return void
     */
    public function handle(int $statusCode, callable $handler): void
    {
        $this->errorHandlers[$statusCode] = $handler;
    }

    /**
     * Abort the request with an HTTP error response.
     *
     * @param int $statusCode HTTP status code to return
     * @return void
     */
    public function abort(int $statusCode): void
    {
        $this->respondWithError($statusCode);
    }

    /**
     * Send an HTTP error response with optional custom error handler.
     *
     * @param int $statusCode HTTP status code
     * @return void
     */
    protected function respondWithError(int $statusCode): void
    {
        // Set the HTTP response code
        http_response_code($statusCode);

        // Define default error messages
        $errorMessages = [
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            500 => 'Internal Server Error',
        ];

        // Check if a custom error handler is registered for the status code
        if (isset($this->errorHandlers[$statusCode])) {
            try {
                ob_start();
                // Call the custom error handler and capture any returned output
                $returned = call_user_func($this->errorHandlers[$statusCode], $statusCode);
                $buffered = ob_get_clean();

                if ($buffered !== '') {
                    echo $buffered;
                } elseif ($returned !== null) {
                    echo $returned;
                }
            } catch (\Throwable $e) {
                // Fallback if error handler throws an exception
                http_response_code(500);
                echo '<html><body><h1>500 - Internal Server Error</h1></body></html>';
            }
        }
        // Otherwise, use the default error message
        else {
            $errorMessage = $errorMessages[$statusCode] ?? 'Unknown Error';

            // Check if the request expects a JSON response
            if ($this->isJsonRequest()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => $statusCode,
                    'error' => $errorMessage,
                ]);
            }
            // If not, send a plain HTML response
            else {
                header('Content-Type: text/html; charset=UTF-8');
                echo "<html><body><h1>$statusCode - $errorMessage</h1></body></html>";
            }
        }
    }

    /**
     * Register a redirect route.
     * Can be used inside any route handler.
     *
     * @param string $toPath Target URL or route
     * @param int    $status HTTP status code (default 302 = temporary redirect)
     * @return void
     */
    public function redirect(string $toPath, int $status = 302): void
    {
        http_response_code($status);
        header("Location: $toPath");

        if (!defined('PHPUNIT_RUNNING')) {
            exit;
        }
    }

    /**
     * Get all HTTP request headers.
     * Uses getallheaders() when available; falls back to $_SERVER.
     *
     * @return array<string, string> An associative array of normalized headers.
     */
    public function getRequestedHeaders(): array
    {
        $headers = [];

        // Use apache_request_headers() if available
        if (function_exists('apache_request_headers')) {
            $rawHeaders = apache_request_headers();
            foreach ($rawHeaders as $name => $value) {
                $normalized = ucwords(strtolower($name), '-');
                $headers[$normalized] = $value;
            }
            return $headers;
        }

        // Fallback: manually extract headers from $_SERVER
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headerName = substr($key, 5); // Strip "HTTP_" prefix
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'])) {
                $headerName = $key;
            } else {
                continue; // Skip non-header entries
            }

            // Convert to readable format: "ACCEPT_ENCODING" → "Accept-Encoding"
            $normalized = ucwords(strtolower(str_replace('_', '-', $headerName)), '-');
            $headers[$normalized] = $value;
        }

        return $headers;
    }

    /**
     * Get the effective HTTP request method.
     * Supports overrides via POST _method field or X-HTTP-Method-Override header.
     *
     * @return string The HTTP method in uppercase (e.g., GET, POST, PUT, DELETE)
     */
    public function getRequestedMethod(): string
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // Support _method field override for POST requests (e.g., from HTML forms)
        if (strtoupper($method) === 'POST') {
            // Check for override via POST body
            if (isset($_POST['_method'])) {
                return strtoupper($_POST['_method']);
            }

            // Check for override via X-HTTP-Method-Override header
            $headers = $this->getRequestedHeaders();
            if (isset($headers['X-Http-Method-Override'])) {
                return strtoupper($headers['X-Http-Method-Override']);
            }
        }

        return strtoupper($method);
    }


    /**
     * Start processing the current HTTP request.
     * Matches the request to a route, applies middleware, and runs the handler.
     *
     * @return void
     */
    public function start(): void
    {
        $this->requestedMethod = $this->getRequestedMethod();
        $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        // Normalize: remove trailing slash '/' at the end (except for root)
        if ($requestPath !== '/' && str_ends_with($requestPath, '/')) {
            $requestPath = rtrim($requestPath, '/');
        }

        $methodAllowedForPath = false;

        $missingParamsRoute = null;

        foreach ($this->routes as $route) {
            $paramNames = [];
            $regex = $this->convertPathToRegex($route['path'], $paramNames);

            // Path matches
            if (preg_match($regex, $requestPath, $matches)) {
                // Method matches
                if ($route['method'] === $this->requestedMethod) {
                    // Correct method too — this is the handler to run
                    $params = [];
                    foreach ($paramNames as $index => $name) {
                        $params[$name] = $matches[$index + 1];
                    }

                    // Run global middleware
                    foreach ($this->middleware as $middleware) {
                        $response = $middleware();
                        if ($response !== true) {
                            $this->handleMiddlewareRejection($response);
                            return;
                        }
                    }

                    // Run route-specific middleware
                    foreach ($route['middleware'] as $middleware) {
                        $response = $middleware();
                        if ($response !== true) {
                            $this->handleMiddlewareRejection($response);
                            return;
                        }
                    }

                    // Execute handler
                    try {
                        ob_start();
                        $returned = call_user_func_array($route['handler'], $params);
                        $buffered = ob_get_clean();

                        if ($buffered !== '') {
                            echo $buffered;
                        } elseif ($returned !== null) {
                            echo $returned;
                        }
                    } catch (\Throwable $e) {
                        $this->respondWithError(500);
                    }
                    return;
                } else {
                    // Method doesn't match, but path does
                    $methodAllowedForPath = true;
                }
            } else {
                // Regex did NOT match. Check if path partially matches route static part to detect missing parameters.
                $staticRoutePath = preg_replace('/\{[^\/]+\}/', '', $route['path']); // Remove param placeholders
                $staticRoutePath = rtrim($staticRoutePath, '/'); // Remove trailing slash

                // Check if requestPath starts with the static part of the route path
                if ($staticRoutePath !== '' && str_starts_with($requestPath, $staticRoutePath)) {
                    // The URL matches the base route but parameters are missing or incorrect
                    $missingParamsRoute = $route;
                }
            }
        }

        // If route was found but missing parameters, respond with 400 Bad Request
        if ($missingParamsRoute) {
            $this->respondWithError(400);
            return;
        }

        // If route was found and the method is valid but mismatched, respond with 405 Method Not Allowed
        if ($methodAllowedForPath) {
            $this->respondWithError(405);
            return;
        }

        // No matching route found 
        $this->respondWithError(404);
        return;
    }

    /**
     * Handle middleware rejection responses.
     *
     * @param bool|array $response Return value from middleware which can be:
     *                           - false: Deny access
     *                           - array: Custom response with 'status' and 'message'
     * @return void
     */
    private function handleMiddlewareRejection(bool|array $response): void
    {
        if ($response === false) {
            $this->respondWithError(403);
            return;
        }

        if (is_array($response) && isset($response['status'])) {
            $status = (int)$response['status'];
            $message = $response['message'] ?? null;

            http_response_code($status);

            if ($this->isJsonRequest()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => $status,
                    'error' => $message ?? "Error $status"
                ]);
            } else {
                header('Content-Type: text/html; charset=UTF-8');
                echo "<html><body><h1>$status - " . htmlspecialchars($message ?? "Error") . "</h1></body></html>";
            }
            return;
        }

        $this->respondWithError(403);
    }


    /**
     * Convert a route path with parameters to a regular expression.
     * Also extracts parameter names into an array.
     *
     * Example:
     *   /users/{id} → #^/users/([^/]+)$#
     *   $paramNames = ['id']
     *
     * @param string $path              Route path
     * @param array|null  &$paramNames  Output parameter names
     * @return string                   Regex pattern
     */
    protected function convertPathToRegex(string $path, array|null &$paramNames = null): string
    {
        if ($paramNames === null) {
            $paramNames = [];
        }

        $regex = preg_replace_callback('#\{([^}]+)\}#', function ($matches) use (&$paramNames) {
            $paramNames[] = $matches[1];
            return '([^/]+)';
        }, $path);

        return '#^' . $regex . '$#';
    }

    /**
     * Determine whether the request expects a JSON response.
     *
     * @return bool
     */
    private function isJsonRequest(): bool
    {
        $headers = $this->getRequestedHeaders();

        foreach ($headers as $key => $value) {
            if (strtolower($key) === 'accept' && strpos($value, 'application/json') !== false) {
                return true;
            }
        }

        return false;
    }
}
