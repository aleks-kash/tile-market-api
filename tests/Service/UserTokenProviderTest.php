<?php

namespace App\Tests\Service;

use App\Service\UserTokenProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Unit test suite for UserTokenProvider service.
 *
 * Verifies token retrieval from cookies, lazy token generation, fallback behavior
 * outside of HTTP requests, and manual token assignment.
 */
class UserTokenProviderTest extends TestCase
{
    /**
     * Tests that getToken returns an existing token value when the 'token' cookie is present in the request.
     */
    public function testGetTokenReturnsCookieValueWhenPresent(): void
    {
        // Set up an incoming HTTP request containing an existing 'token' cookie.
        $request = new Request();
        $request->cookies->set('token', 'my_existing_cookie_token_123');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        // Verify that the provider returns the cookie token value.
        $provider = new UserTokenProvider($requestStack);
        $this->assertSame('my_existing_cookie_token_123', $provider->getToken());
    }

    /**
     * Tests that getToken generates a 64-character hex token and injects it into the request cookies when missing.
     */
    public function testGetTokenGeneratesNewTokenAndSetsCookieInRequestWhenMissing(): void
    {
        // Ensure the initial request has no 'token' cookie.
        $request = new Request();
        $this->assertFalse($request->cookies->has('token'));

        $requestStack = new RequestStack();
        $requestStack->push($request);

        // Generate a new token via the provider.
        $provider = new UserTokenProvider($requestStack);
        $token = $provider->getToken();

        // Assert the generated token is 64 hex characters and stored in request cookies.
        $this->assertSame(64, strlen($token));
        $this->assertTrue($request->cookies->has('token'));
        $this->assertSame($token, $request->cookies->get('token'));
    }

    /**
     * Tests that getToken provides a cached fallback token when running outside an HTTP request context (e.g. CLI/tests).
     */
    public function testGetTokenReturnsFallbackTokenWhenNoRequestPresent(): void
    {
        // Execute without an active HTTP request in the stack.
        $provider = new UserTokenProvider(new RequestStack());
        $token1 = $provider->getToken();
        $token2 = $provider->getToken();

        // Verify fallback token is generated once and cached for subsequent calls.
        $this->assertNotEmpty($token1);
        $this->assertSame(64, strlen($token1));
        $this->assertSame($token1, $token2);
    }

    /**
     * Tests that setToken manually overrides the token in both CLI (no request) and HTTP request contexts.
     */
    public function testSetTokenManuallyOverridesTokenInCliAndRequest(): void
    {
        // Test manual token override in CLI context (no request).
        $providerNoReq = new UserTokenProvider(new RequestStack());
        $providerNoReq->setToken('custom_cli_token_999');
        $this->assertSame('custom_cli_token_999', $providerNoReq->getToken());

        // Test manual token override in HTTP request context.
        $request = new Request();
        $requestStack = new RequestStack();
        $requestStack->push($request);
        $providerWithReq = new UserTokenProvider($requestStack);
        $providerWithReq->setToken('custom_http_token_888');
        $this->assertSame('custom_http_token_888', $providerWithReq->getToken());
    }
}
