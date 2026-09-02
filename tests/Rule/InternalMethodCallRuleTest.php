<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Shopware\PhpStan\Rule\InternalMethodCallRule;

class InternalMethodCallRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new InternalMethodCallRule($this->createReflectionProvider());
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../phpstan.neon',
        ];
    }

    public function testInternalMethodCallRule(): void
    {
        $this->analyse([
            __DIR__ . '/fixtures/InternalMethodCallRule/app/Test/case.php',
            __DIR__ . '/fixtures/InternalMethodCallRule/SomeClass.php',
        ], [
            [
                'Call of internal method Shopware\PhpStan\Tests\Rule\fixtures\InternalMethodCallRule\SomeClass::internalMethod Please refrain from using methods which are annotated with @internal in the Shopware 6 repository.',
                11,
            ],
        ]);
    }

    public function testUnknownClassDoesNotCauseAnInternalError(): void
    {
        $this->analyse([
            __DIR__ . '/fixtures/InternalMethodCallRule/app/Test/unknown-class.php',
        ], []);
    }
}
