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
                13,
            ],
            [
                'Response with empty body. Return meaningful content or use a status code like 204 (No Content) for intentionally empty responses.',
                18,
            ],
            [
                'Response with empty body. Return meaningful content or use a status code like 204 (No Content) for intentionally empty responses.',
                23,
            ],
            [
                'Response with empty body. Return meaningful content or use a status code like 204 (No Content) for intentionally empty responses.',
                28,
            ],
            [
                'Response with empty body. Return meaningful content or use a status code like 204 (No Content) for intentionally empty responses.',
                33,
            ],
            [
                'Response with empty body. Return meaningful content or use a status code like 204 (No Content) for intentionally empty responses.',
                38,
            ],
            [
                'Response with empty body. Return meaningful content or use a status code like 204 (No Content) for intentionally empty responses.',
                43,
            ],
            [
                'Response with empty body. Return meaningful content or use a status code like 204 (No Content) for intentionally empty responses.',
                48,
            ],
            [
                'Response with empty body. Return meaningful content or use a status code like 204 (No Content) for intentionally empty responses.',
                53,
            ],
            [
                'Response with empty body. Return meaningful content or use a status code like 204 (No Content) for intentionally empty responses.',
                58,
            ],
            [
                'Response with empty body. Return meaningful content or use a status code like 204 (No Content) for intentionally empty responses.',
                63,
            ],
            [
                'Response with empty body. Return meaningful content or use a status code like 204 (No Content) for intentionally empty responses.',
                68,
            ],
        ]);
    }
}
