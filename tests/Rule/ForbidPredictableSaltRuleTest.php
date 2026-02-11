<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Shopware\PhpStan\Rule\ForbidPredictableSaltRule;

/**
 * @extends RuleTestCase<ForbidPredictableSaltRule>
 */
class ForbidPredictableSaltRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ForbidPredictableSaltRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/fixtures/ForbidPredictableSaltRule/correct-usage.php'], []);
        $this->analyse([__DIR__ . '/fixtures/ForbidPredictableSaltRule/wrong-usage.php'], [
            [
                'Hardcoded salt in password hashing is predictable and weakens security.',
                11,
            ],
            [
                'Hardcoded salt in password hashing is predictable and weakens security.',
                16,
            ],
        ]);
    }
}
