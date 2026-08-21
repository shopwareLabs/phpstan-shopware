<?php

declare(strict_types=1);

use Shopware\Core\Framework\Deprecation\BCChange\ReturnTypeWidening;

use function PHPStan\Testing\assertType;

class WidensReturn
{
    #[ReturnTypeWidening(version: 'v6.8.0', newType: '?string')]
    public function getUrl(): string
    {
        return 'https://example.com';
    }

    public function unchanged(): string
    {
        return 'stable';
    }
}

function (WidensReturn $subject): void {
    assertType('string|null', $subject->getUrl());
    assertType('string', $subject->unchanged());
};

/** @deprecated tag:v6.8.0 - This extension will be removed with the next major version. */
class DeprecatedCaller
{
    public function getUrl(WidensReturn $subject): void
    {
        assertType('string', $subject->getUrl());
    }
}

class MethodDeprecatedCaller
{
    /** @deprecated tag:v6.8.0 - This extension hook will be removed with the next major version. */
    public function getUrl(WidensReturn $subject): void
    {
        assertType('string', $subject->getUrl());
    }
}

class LaterDeprecatedCaller
{
    /** @deprecated tag:v6.9.0 - This extension hook remains available in v6.8.0. */
    public function getUrl(WidensReturn $subject): void
    {
        assertType('string|null', $subject->getUrl());
    }
}
