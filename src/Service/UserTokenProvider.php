<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Single source of truth (SST) for managing user tokens in an application.
 *
 * Responsible for:
 * - Retrieving an existing user token from an HTTP cookie ('token').
 * - Automatic lazy generation of a new 64-character token if a cookie is missing.
 * - Caching the generated token in memory within a single PHP process/HTTP request.
 * - Explicitly setting a token in the context of CLI commands, background tasks, or unit tests.
 */
class UserTokenProvider
{
    /**
     * Cookie name for storing the user's authorization/session token.
     */
    public const COOKIE_NAME = 'token';

    /**
     * Token cache for execution scenarios outside of an HTTP request (CLI terminal, background workers, tests).
     */
    private ?string $fallbackToken = null;

    /**
     * @param RequestStack $requestStack Symfony HTTP request stack
     */
    public function __construct(
        private readonly RequestStack $requestStack
    ) {}

    /**
    * Returns the currently active user token.
    *
    * Operation logic:
    * - If the call occurs outside of an HTTP request (CLI/tests), it uses and remembers the $fallbackToken.
    * - If the 'token' cookie was already received in the HTTP request, it returns its value.
    * - If there is no 'token' cookie, it generates a cryptographically strong 64-character hex token,
    * places it in the internal cookies array of the current $request (so that all calls within the request
    * receive a single token), and returns it.
    *
    * @return string 64-character unique user token
    */
    public function getToken(): string
    {
        $request = $this->requestStack->getCurrentRequest();

        // Outside the context of an HTTP request (CLI command, console script, worker, unit test).
        if (!$request) {
            return $this->fallbackToken ??= bin2hex(random_bytes(32));
        }

        // If a cookie with a token is already present in the incoming request.
        if ($request->cookies->has(self::COOKIE_NAME)) {
            return (string) $request->cookies->get(self::COOKIE_NAME);
        }

        // If there is no cookie, we generate a new 64-character token.
        $newToken = bin2hex(random_bytes(32));

        // Place the token in the current Request's internal cookie collection,
        // so that all services receive the same token within the current HTTP request.
        $request->cookies->set(self::COOKIE_NAME, $newToken);

        return $newToken;
    }

    /**
    * Explicitly sets the user token.
    *
    * Used in console commands (CLI), background tasks, or integration tests
    * to emulate a specific user session.
    *
    * @param string $token Value of the token to set
    */
    public function setToken(string $token): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $request->cookies->set(self::COOKIE_NAME, $token);
        } else {
            $this->fallbackToken = $token;
        }
    }
}
