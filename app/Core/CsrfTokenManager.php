<?php

declare(strict_types=1);

namespace App\Core;

final class CsrfTokenManager
{
    private const SESSION_KEY = '_csrf_token';

    public function __construct(private readonly Session $session)
    {
    }

    public function token(): string
    {
        $token = $this->session->get(self::SESSION_KEY);

        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            $this->session->put(self::SESSION_KEY, $token);
        }

        return $token;
    }

    public function validate(string $submittedToken): bool
    {
        $storedToken = $this->session->get(self::SESSION_KEY);

        if (!is_string($storedToken) || $storedToken === '' || $submittedToken === '') {
            return false;
        }

        $valid = hash_equals($storedToken, $submittedToken);

        if ($valid) {
            $this->session->remove(self::SESSION_KEY);
        }

        return $valid;
    }
}
