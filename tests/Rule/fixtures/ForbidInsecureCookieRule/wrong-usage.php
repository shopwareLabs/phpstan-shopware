<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Rule\fixtures\ForbidInsecureCookieRule;

class WrongUsage
{
    public function setcookieWithoutSecure(): void
    {
        setcookie('session', 'abc123');
    }

    public function setcookieWithSecureFalse(): void
    {
        setcookie('session', 'abc123', 0, '/', '', false);
    }

    public function setcookieArrayOptionsWithoutSecure(): void
    {
        setcookie('session', 'abc123', ['expires' => 0, 'path' => '/']);
    }

    public function setrawcookieWithoutSecure(): void
    {
        setrawcookie('session', 'abc123');
    }
}
