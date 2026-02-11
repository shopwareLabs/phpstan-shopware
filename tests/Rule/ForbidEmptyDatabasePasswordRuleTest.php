<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Shopware\PhpStan\Rule\ForbidEmptyDatabasePasswordRule;

/**
 * @extends RuleTestCase<ForbidEmptyDatabasePasswordRule>
 */
class ForbidEmptyDatabasePasswordRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ForbidEmptyDatabasePasswordRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/fixtures/ForbidEmptyDatabasePasswordRule/correct-usage.php'], []);
        $this->analyse([__DIR__ . '/fixtures/ForbidEmptyDatabasePasswordRule/wrong-usage.php'], [
            [
                'Database connection with empty password. Use secure credentials.',
                11,
            ],
            [
                'Database connection with empty password. Use secure credentials.',
                16,
            ],
            [
                'Database connection with empty password. Use secure credentials.',
                21,
            ],
        ]);
    }
}
