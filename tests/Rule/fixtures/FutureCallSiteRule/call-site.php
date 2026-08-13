<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Fixture;

use Shopware\Core\Framework\Deprecation\BCChange\ParameterTypeNarrowing;

class FutureSubject
{
    #[ParameterTypeNarrowing(version: 'v6.8.0', parameterName: 'id', newType: 'string')]
    public function find(string|int $id): void {}
}

class FutureCaller
{
    public function find(FutureSubject $subject): void
    {
        $subject->find(1);
    }
}
