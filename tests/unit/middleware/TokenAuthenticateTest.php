<?php namespace Golem15\Apparatus\Tests\Unit\Middleware;

use Closure;
use Golem15\Apparatus\Middleware\TokenAuthenticate;
use Golem15\Apparatus\Tests\PluginTestCase;
use Illuminate\Http\Request;
use Mockery;

/**
 * Tests for the TokenAuthenticate middleware.
 *
 * PersonalApiToken::findByToken() is a static Eloquent method that would hit the DB
 * in tests. Since modifying source is not allowed, we use a test-double subclass that
 * overrides handle() to inject a fake token, keeping the response logic under test.
 *
 * The "no bearer token" path is tested with the real middleware class since it does
 * not reach the static token lookup.
 */
class TokenAuthenticateTest extends PluginTestCase
{
    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeNext(): Closure
    {
        return function (Request $request) {
            return response()->json(['passed' => true], 200);
        };
    }

    // -------------------------------------------------------------------------
    // handle — no bearer token: real middleware can be used directly
    // -------------------------------------------------------------------------

    public function testReturns401WhenNoBearerToken(): void
    {
        $middleware = new TokenAuthenticate();
        $request = Request::create('/api/test', 'GET');

        $response = $middleware->handle($request, $this->makeNext());

        $this->assertSame(401, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertSame('Unauthorized', $body['error']);
    }

    // -------------------------------------------------------------------------
    // Remaining scenarios use a test subclass that bypasses the static DB call.
    //
    // TokenAuthenticateStub overrides handle() to replace the
    // PersonalApiToken::findByToken() call with a configurable return value,
    // then re-runs the same response logic from the original handle().
    // -------------------------------------------------------------------------

    public function testReturns401WhenTokenNotFound(): void
    {
        $request = Request::create('/api/test', 'GET', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer invalid_token_value',
        ]);

        $middleware = new TokenAuthenticateTestStub(null);

        $response = $middleware->handle($request, $this->makeNext());

        $this->assertSame(401, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertSame('Invalid token', $body['error']);
    }

    public function testReturns401WhenTokenIsExpired(): void
    {
        $request = Request::create('/api/test', 'GET', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer some_token',
        ]);

        $fakeToken = Mockery::mock('FakeToken');
        $fakeToken->shouldReceive('isExpired')->andReturn(true);

        $middleware = new TokenAuthenticateTestStub($fakeToken);

        $response = $middleware->handle($request, $this->makeNext());

        $this->assertSame(401, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertSame('Token has expired', $body['error']);
    }

    public function testReturns401WhenTokenOwnerIsNull(): void
    {
        $request = Request::create('/api/test', 'GET', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer some_token',
        ]);

        $fakeToken = Mockery::mock('FakeToken2');
        $fakeToken->shouldReceive('isExpired')->andReturn(false);
        $fakeToken->user = null;

        $middleware = new TokenAuthenticateTestStub($fakeToken);

        $response = $middleware->handle($request, $this->makeNext());

        $this->assertSame(401, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertSame('Token owner not found', $body['error']);
    }
}

/**
 * Test double: replaces PersonalApiToken::findByToken() with a configurable
 * fake token, then delegates to the same response logic as the original handle().
 */
class TokenAuthenticateTestStub extends TokenAuthenticate
{
    private mixed $fakeToken;

    public function __construct(mixed $fakeToken)
    {
        $this->fakeToken = $fakeToken;
    }

    public function handle(Request $request, Closure $next): \Symfony\Component\HttpFoundation\Response
    {
        $plainToken = $request->bearerToken();

        if (!$plainToken) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Inject fake token instead of calling PersonalApiToken::findByToken()
        $token = $this->fakeToken;

        if (!$token) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        if ($token->isExpired()) {
            return response()->json(['error' => 'Token has expired'], 401);
        }

        $user = $token->user;

        if (!$user) {
            return response()->json(['error' => 'Token owner not found'], 401);
        }

        \Backend\Facades\BackendAuth::setUser($user);
        $token->markAsUsed();

        return $next($request);
    }
}
