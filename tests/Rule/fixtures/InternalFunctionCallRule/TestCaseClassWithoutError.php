<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Rule\fixtures\InternalFunctionCallRule;

class TestCaseClassWithoutError
{
    public function something(): void
    {
        internalFunction();
    }
}
