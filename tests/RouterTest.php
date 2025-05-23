<?php

use KhantLoonThu\PhpRouter\Router;

/**
 * Class RouterTest
 *
 * This class contains test cases to ensure the correct functionality of the Router class.
 * It includes tests for various HTTP methods, route registration, middleware functionality,
 * custom error handling, and other essential router operations.
 */
class RouterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Router
     */
    private Router $router;

    /**
     * Set up the test environment by initializing the Router instance.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->router = new Router();

        parent::setUp();

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
    }

    /**
     * Tear down the test environment by resetting the $_SERVER variable.
     * 
     * @return void
     */
    protected function tearDown(): void
    {
        $_SERVER = [];
        parent::tearDown();
    }

    /**
     * Test if the Router can register a GET route correctly.
     *
     * This test checks whether a GET route is correctly registered and returns the expected response.
     *
     * @return void
     */
    public function testRegisterGetRoute(): void
    {
        $this->router->get('/test', function () {
            return 'GET Route';
        });

        // Simulate a GET request
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/test';

        ob_start();
        $this->router->start();
        $response = ob_get_clean();

        $this->assertEquals('GET Route', $response);
    }

    /**
     * Test if the Router can register a POST route correctly.
     *
     * This test checks whether a POST route is correctly registered and returns the expected response.
     *
     * @return void
     */
    public function testRegisterPostRoute(): void
    {
        $this->router->post('/submit', function () {
            return 'POST Route';
        });

        // Simulate a POST request
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/submit';

        ob_start();
        $this->router->start();
        $response = ob_get_clean();

        $this->assertEquals('POST Route', $response);
    }

    /**
     * Test if the Router can register a PUT route correctly.
     *
     * This test checks whether a PUT route is correctly registered and returns the expected response.
     *
     * @return void
     */
    public function testRegisterPutRoute(): void
    {
        $this->router->put('/update', function () {
            return 'PUT Route';
        });

        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['REQUEST_URI'] = '/update';

        ob_start();
        $this->router->start();
        $response = ob_get_clean();

        $this->assertEquals('PUT Route', $response);
    }

    /**
     * Test if the Router can register a DELETE route correctly.
     *
     * This test checks whether a DELETE route is correctly registered and returns the expected response.
     *
     * @return void
     */
    public function testRegisterDeleteRoute(): void
    {
        $this->router->delete('/delete', function () {
            return 'DELETE Route';
        });

        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $_SERVER['REQUEST_URI'] = '/delete';

        ob_start();
        $this->router->start();
        $response = ob_get_clean();

        $this->assertEquals('DELETE Route', $response);
    }

    /**
     * Test if the Router can handle route-specific middleware correctly.
     *
     * This test checks whether middleware specific to a route executes correctly and rejects requests when necessary.
     *
     * @return void
     */
    public function testRouteSpecificMiddlewareExecution(): void
    {
        $this->router->get('/admin', function () {
            return 'Welcome Admin';
        }, [
            function () {
                return false;
            }
        ]);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/admin';

        ob_start();
        $this->router->start();
        $response = ob_get_clean();

        $this->assertStringContainsString('403 - Forbidden', $response);
    }

    /**
     * Test middleware rejection with a custom message.
     *
     * This test checks whether the middleware correctly rejects requests and returns a custom message.
     *
     * @return void
     */
    public function testMiddlewareRejectionWithCustomMessage(): void
    {
        $this->router->middleware(function () {
            return ['status' => 401, 'message' => 'Unauthorized access'];
        });

        $this->router->get('/secure', function () {
            return 'Secure content';
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/secure';

        ob_start();
        $this->router->start();
        $response = ob_get_clean();

        $this->assertStringContainsString('401 - Unauthorized access', $response);
    }

    /**
     * Test the router's abort method functionality.
     *
     * This test ensures the abort method works and returns the correct HTTP status code and message.
     *
     * @return void
     */
    public function testAbortMethod(): void
    {
        $this->router->get('/abort', function () {
            $this->router->abort(403);
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/abort';

        ob_start();
        $this->router->start();
        $response = ob_get_clean();

        $this->assertStringContainsString('403 - Forbidden', $response);
    }

    /**
     * Test the router's redirect functionality.
     *
     * This test ensures the redirect method sets the correct Location header.
     *
     * @return void
     */
    public function testRedirect(): void
    {
        $this->router->get('/redirect', function () {
            $this->router->redirect('/new-location');
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/redirect';

        ob_start();
        $this->router->start();
        $response = ob_get_clean();

        $headers = headers_list();

        $this->assertContains('Location: /new-location', $headers);
    }

    /**
     * Test method override using the _method POST field.
     *
     * This test ensures the method can be overridden via the _method POST field.
     *
     * @return void
     */
    public function testMethodOverrideUsingPostField(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/override';
        $_POST['_method'] = 'PUT';

        $this->router->put('/override', function () {
            return 'Method Overridden to PUT';
        });

        ob_start();
        $this->router->start();
        $response = ob_get_clean();

        $this->assertEquals('Method Overridden to PUT', $response);
    }

    /**
     * Test method override using the X-HTTP-Method-Override header.
     *
     * This test ensures the method can be overridden via the X-HTTP-Method-Override header.
     *
     * @return void
     */
    public function testMethodOverrideUsingHeader(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] = 'DELETE';
        $_SERVER['REQUEST_URI'] = '/override-header';

        $this->router->delete('/override-header', function () {
            return 'Method Overridden to DELETE';
        });

        ob_start();
        $this->router->start();
        $response = ob_get_clean();

        $this->assertEquals('Method Overridden to DELETE', $response);
    }

    /**
     * Test for a 404 error when the route is not found.
     *
     * This test checks whether a 404 error is returned for a non-existent route.
     *
     * @return void
     */
    public function testRouteNotFound(): void
    {
        $this->router->get('/test', function () {
            return 'GET Route';
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/nonexistent';

        ob_start();
        $this->router->start();
        $response = ob_get_clean();

        $this->assertStringContainsString('404 - Not Found', $response);
    }

    /**
     * Test that the router sends the correct HTTP status code header.
     *
     * This test verifies that when a route is matched, the proper HTTP status code is sent.
     *
     * @return void
     */
    public function testStatusCodeIsSent(): void
    {
        $this->router->get('/created', function () {
            http_response_code(201);
            return 'Resource Created';
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/created';

        ob_start();
        $this->router->start();
        ob_end_clean();

        $this->assertEquals(201, http_response_code(), 'Expected HTTP status code 201.');
    }


    /**
     * Test for a 405 Method Not Allowed error when the method is not allowed.
     *
     * This test ensures that the router returns a 405 error for a method mismatch.
     *
     * @return void
     */
    public function testMethodNotAllowed(): void
    {
        $this->router->get('/test', function () {
            return 'GET Route';
        });

        // Simulate a POST request for a GET route
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/test';

        ob_start();
        $this->router->start();
        $response = ob_get_clean();

        $this->assertStringContainsString('405 - Method Not Allowed', $response);
    }

    /**
     * Test handling of route parameters.
     *
     * This test checks if the router correctly processes route parameters.
     *
     * @return void
     */
    public function testRouteWithParameters(): void
    {
        $this->router->get('/user/{id}', function ($id) {
            return "User ID: $id";
        });

        // Simulate a GET request with parameters
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/user/123';

        ob_start();
        $this->router->start();
        $response = ob_get_clean();

        $this->assertEquals('User ID: 123', $response);
    }

    /**
     * Test handling of multiple route parameters.
     *
     * This test checks if the router correctly processes multiple route parameters
     * and passes them to the callback in the correct order.
     *
     * @return void
     */
    public function testRouteWithMultipleParameters(): void
    {
        $this->router->get('/user/{userId}/post/{postId}', function ($userId, $postId) {
            return "User ID: $userId, Post ID: $postId";
        });

        // Simulate a GET request with parameters
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/user/42/post/99';

        ob_start();
        $this->router->start();
        $response = ob_get_clean();

        $this->assertEquals('User ID: 42, Post ID: 99', $response);
    }

    /**
     * Test middleware execution.
     *
     * This test checks if the middleware executes correctly before routing.
     *
     * @return void
     */
    public function testMiddlewareExecution(): void
    {
        // Example middleware that adds "Middleware passed"
        $this->router->middleware(function () {
            return true;
        });

        $this->router->get('/middleware', function () {
            return 'Middleware passed';
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/middleware';

        ob_start();
        $this->router->start();
        $response = ob_get_clean();

        $this->assertStringContainsString('Middleware passed', $response);
    }

    /**
     * Test middleware rejection (e.g., returning 403 Forbidden).
     *
     * This test checks whether the middleware rejects the request and returns the correct response.
     *
     * @return void
     */
    public function testMiddlewareRejection(): void
    {
        // Middleware that blocks access
        $this->router->middleware(function () {
            return false;
        });

        $this->router->get('/restricted', function () {
            return 'GET Route';
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/restricted';

        ob_start();
        $this->router->start();
        $response = ob_get_clean();

        $this->assertStringContainsString('403 - Forbidden', $response);
    }

    /**
     * Test custom error handler for 404.
     *
     * This test ensures the router uses a custom error handler for 404 errors.
     *
     * @return void
     */
    public function testCustomErrorHandler(): void
    {
        $this->router->handle(404, function () {
            echo "Custom 404 Error";
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/nonexistent';

        ob_start();
        $this->router->start();
        $response = ob_get_clean();

        $this->assertEquals('Custom 404 Error', $response);
    }

    /**
     * Test that routes with and without trailing slash are treated the same.
     *
     * This ensures both /test and /test/ match the same route.
     *
     * @return void
     */
    public function testRouteWithTrailingSlash(): void
    {
        $this->router->get('/test', function () {
            return 'Trailing slash test';
        });

        // Test without trailing slash
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/test';

        ob_start();
        $this->router->start();
        $responseWithoutSlash = ob_get_clean();

        // Test with trailing slash
        $_SERVER['REQUEST_URI'] = '/test/';

        ob_start();
        $this->router->start();
        $responseWithSlash = ob_get_clean();

        $this->assertEquals('Trailing slash test', $responseWithoutSlash);
        $this->assertEquals('Trailing slash test', $responseWithSlash);
    }
}
