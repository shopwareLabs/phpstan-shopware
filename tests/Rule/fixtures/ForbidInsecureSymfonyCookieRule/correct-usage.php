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

    // Named arguments
    public function newCookieWithNamedArgsSecureTrue(): void
    {
        new Cookie(name: 'session', secure: true);
    }

    // Fluent builder – create() + withSecure(true)
    public function cookieCreateFluentWithSecureTrue(): void
    {
        Cookie::create('session')->withSecure(true);
    }

    // Fluent builder – create() + withSecure() default
    public function cookieCreateFluentWithSecureDefault(): void
    {
        Cookie::create('session')->withSecure();
    }

    // Fluent builder – new Cookie + withSecure(true)
    public function newCookieFluentWithSecureTrue(): void
    {
        (new Cookie('session'))->withSecure(true);
    }

    // Fluent builder – new Cookie + withSecure() default
    public function newCookieFluentWithSecureDefault(): void
    {
        (new Cookie('session'))->withSecure();
    }
}
