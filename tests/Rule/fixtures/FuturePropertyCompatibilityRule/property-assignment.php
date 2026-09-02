<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Fixture\FuturePropertyCompatibilityRule;

use Shopware\Core\Framework\Deprecation\BCChange\BecomesReadonly;
use Shopware\Core\Framework\Deprecation\BCChange\PropertyTypeNarrowing;

class FutureNarrowedProperty
{
    #[PropertyTypeNarrowing(version: 'v6.8.0', newType: 'string')]
    protected ?string $value = null;
}

class FutureReadonlyProperty
{
    #[BecomesReadonly(version: 'v6.8.0')]
    protected string $value = 'value';
}

class FuturePropertyConsumer extends FutureNarrowedProperty
{
    public function assignNull(): void
    {
        $this->value = null;
    }

    public function assignString(): void
    {
        $this->value = 'value';
    }
}

class FutureReadonlyPropertyConsumer extends FutureReadonlyProperty
{
    public function assignValue(): void
    {
        $this->value = 'value';
    }
}
