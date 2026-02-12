<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Rule\fixtures\ForbidInsecureSymfonyCookieRule;

use Symfony\Component\HttpFoundation\Cookie;

class WrongUsage
{
    public function newCookieWithoutSecure(): void
    {
        new Cookie('session', 'abc123');
    }

    public function newCookieWithSecureFalse(): void
    {
        new Cookie('session', 'abc123', 0, '/', '', false);
    }

    public function newCookieWithSecureNull(): void
    {
        new Cookie('session', 'abc123', 0, '/', '', null);
    }

    public function cookieCreateWithoutSecure(): void
    {
        Cookie::create('session', 'abc123');
    }

    public function cookieCreateWithSecureFalse(): void
    {
        Cookie::create('session', 'abc123', 0, '/', '', false);
    }

    public function withSecureFalse(): void
    {
        $cookie = new Cookie('session', 'abc123', 0, '/', '', true);
        $cookie->withSecure(false);
    }
}
