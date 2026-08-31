<?php

namespace App\EventListener;

use App\Service\UserTokenProvider;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
* Symfony kernel event listener for automatically sending an HTTP cookie to the user.
*
* Responsible for:
* - Intercepting the final formation of the HTTP response (KernelEvents::RESPONSE).
* - Checking for the presence of a user token in the current request object.
* - Generating and adding the Set-Cookie HTTP header with security properties (HttpOnly, SameSite=Lax).
*/
class UserTokenCookieListener
{
    /**
     * @param UserTokenProvider $userTokenProvider Service for receiving and managing tokens.
     */
    public function __construct(
        private readonly UserTokenProvider $userTokenProvider
    ) {}

    /**
    * Handles the HTTP response generation event (KernelEvents::RESPONSE).
    *
    * If a user token was requested/generated during request processing,
    * this method attaches the corresponding 'token' HTTP cookie to the Response object
    * with an expiration date of 1 year.
    *
    * @param ResponseEvent $event Symfony kernel response event.
    */
    #[AsEventListener(event: KernelEvents::RESPONSE)]
    public function onResponse(ResponseEvent $event): void
    {
        // Process only the main HTTP request (ignore Render/Forward subrequests).
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // If the token is present in the Request object (was passed in cookies or created in this request).
        if ($request->cookies->has(UserTokenProvider::COOKIE_NAME)) {
            $token = $request->cookies->get(UserTokenProvider::COOKIE_NAME);

            // Create a secure HTTP cookie to send to the client browser.
            $cookie = Cookie::create(UserTokenProvider::COOKIE_NAME, $token)
                ->withExpires(new \DateTime('+1 year')) // Lifespan: 1 year.
                ->withPath('/')                         // Available to all domain endpoints.
                ->withHttpOnly(true)                    // Not accessible from JavaScript (XSS protection).
                ->withSameSite(Cookie::SAMESITE_LAX);   // CSRF protection during transitions.

            $event->getResponse()->headers->setCookie($cookie);
        }
    }
}
