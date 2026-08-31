<?php

namespace App\Tests\EventListener;

use App\EventListener\UserTokenCookieListener;
use App\Service\UserTokenProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Unit test suite for UserTokenCookieListener.
 *
 * Ensures that the event listener correctly attaches the user token cookie
 * to outgoing HTTP response headers.
 */
class UserTokenCookieListenerTest extends TestCase
{
    /**
     * Tests that a 'token' cookie is properly attached to the HTTP response
     * when a token is generated or present in the current request context.
     */
    public function testAttachesCookieToResponseWhenTokenPresentInRequest(): void
    {
        // Initialize mock kernel and request stack.
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $requestStack = new RequestStack();
        $requestStack->push($request);

        // Obtain a user token via UserTokenProvider.
        $provider = new UserTokenProvider($requestStack);
        $token = $provider->getToken();

        // Prepare HTTP response and response event.
        $response = new Response();
        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        // Invoke the event listener.
        $listener = new UserTokenCookieListener($provider);
        $listener->onResponse($event);

        // Verify that the response contains the expected 'token' cookie.
        $cookies = $response->headers->getCookies();
        $this->assertCount(1, $cookies);

        /** @var Cookie $cookie */
        $cookie = $cookies[0];
        $this->assertSame('token', $cookie->getName());
        $this->assertSame($token, $cookie->getValue());
    }
}
