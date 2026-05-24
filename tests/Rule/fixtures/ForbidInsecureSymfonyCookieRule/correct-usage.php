<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Rule\fixtures\ForbidInsecureSymfonyCookieRule;

use Symfony\Component\HttpFoundation\Cookie;

class CorrectUsage
{
    public function newCookieWithSecureTrue(): void
    {
        new Cookie('session', 'abc123', 0, '/', '', true);
    }

    public function cookieCreateWithSecureTrue(): void
    {
        Cookie::create('session', 'abc123', 0, '/', '', true);
    }

    public function withSecureTrue(): void
    {
        $cookie = new Cookie('session', 'abc123', 0, '/', '', true);
        $cookie->withSecure(true);
    }

    public function withSecureDefault(): void
    {
        $cookie = new Cookie('session', 'abc123', 0, '/', '', true);
        $cookie->withSecure();
    }

    public function newCookieWithNamedSecureTrue(): void
    {
        new Cookie('session', secure: true);
    }

    public function cookieCreateFluentWithSecureTrue(): void
    {
        Cookie::create('session')->withSecure(true);
    }

    public function cookieCreateFluentWithSecureDefault(): void
    {
        Cookie::create('session')->withSecure();
    }

    public function cookieCreateWithNamedSecureTrue(): void
    {
        Cookie::create('session', secure: true);
    }
}
