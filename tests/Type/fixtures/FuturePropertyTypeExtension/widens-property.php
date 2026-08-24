<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Fixture\FuturePropertyTypeExtension;

use Shopware\Core\Framework\Deprecation\BCChange\PropertyTypeWidening;

use function PHPStan\Testing\assertType;

class Subject
{
    #[PropertyTypeWidening(version: 'v6.8.0', newType: 'string|null')]
    public string $value = 'value';
}

$subject = new Subject();
assertType('string|null', $subject->value);
