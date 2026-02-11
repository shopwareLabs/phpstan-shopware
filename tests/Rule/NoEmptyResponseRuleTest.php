<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Shopware\PhpStan\Rule\NoEmptyResponseRule;

/**
 * @extends RuleTestCase<NoEmptyResponseRule>
 */
class NoEmptyResponseRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoEmptyResponseRule(self::createReflectionProvider());
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/fixtures/NoEmptyResponseRule/correct-usage.php'], []);
        $this->analyse([__DIR__ . '/fixtures/NoEmptyResponseRule/wrong-usage.php'], [
            [
                'Response with empty body. Return meaningful content or use a status code like 204 (No Content) for intentionally empty responses.',
                15,
            ],
            [
                'Response with empty body. Return meaningful content or use a status code like 204 (No Content) for intentionally empty responses.',
                20,
            ],
            [
                'Response with empty body. Return meaningful content or use a status code like 204 (No Content) for intentionally empty responses.',
                25,
            ],
            [
                'Response with empty body. Return meaningful content or use a status code like 204 (No Content) for intentionally empty responses.',
                30,
            ],
            [
                'Response with empty body. Return meaningful content or use a status code like 204 (No Content) for intentionally empty responses.',
                35,
            ],
            [
                'Response with empty body. Return meaningful content or use a status code like 204 (No Content) for intentionally empty responses.',
                40,
            ],
        ]);
    }
}
