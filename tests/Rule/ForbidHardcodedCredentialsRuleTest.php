<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Shopware\PhpStan\Rule\ForbidHardcodedCredentialsRule;

/**
 * @extends RuleTestCase<ForbidHardcodedCredentialsRule>
 */
class ForbidHardcodedCredentialsRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ForbidHardcodedCredentialsRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/fixtures/ForbidHardcodedCredentialsRule/correct-usage.php'], []);
        $this->analyse([__DIR__ . '/fixtures/ForbidHardcodedCredentialsRule/wrong-usage.php'], [
            [
                'Possible hardcoded credentials detected. Use environment variables.',
                12,
            ],
        ]);
    }
}
