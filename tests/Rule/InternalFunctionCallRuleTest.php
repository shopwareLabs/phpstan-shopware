<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Shopware\PhpStan\Rule\InternalFunctionCallRule;

class InternalFunctionCallRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new InternalFunctionCallRule($this->createReflectionProvider());
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../phpstan.neon',
        ];
    }

    public function testInternalFunctionCallRule(): void
    {
        $this->analyse([
            __DIR__ . '/fixtures/InternalFunctionCallRule/app/Test/case.php',
            __DIR__ . '/fixtures/InternalFunctionCallRule/TestCaseClassWithoutError.php',
            __DIR__ . '/fixtures/InternalFunctionCallRule/internal-function.php',
        ], [
            [
                'Call of internal function Shopware\PhpStan\Tests\Rule\fixtures\InternalFunctionCallRule\internalFunction Please refrain from using functions which are annotated with @internal in the Shopware 6 repository.',
                9,
            ],
        ]);
    }
}
