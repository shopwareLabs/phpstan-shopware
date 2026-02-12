<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Shopware\PhpStan\Rule\ForbidInsecureCookieRule;

/**
 * @extends RuleTestCase<ForbidInsecureCookieRule>
 */
class ForbidInsecureCookieRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ForbidInsecureCookieRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/fixtures/ForbidInsecureCookieRule/correct-usage.php'], []);
        $this->analyse([__DIR__ . '/fixtures/ForbidInsecureCookieRule/wrong-usage.php'], [
            [
                'Cookie set without secure flag. Use secure=true for HTTPS-only transmission.',
                11,
            ],
            [
                'Cookie set without secure flag. Use secure=true for HTTPS-only transmission.',
                16,
            ],
            [
                'Cookie set without secure flag. Use secure=true for HTTPS-only transmission.',
                21,
            ],
            [
                'Cookie set without secure flag. Use secure=true for HTTPS-only transmission.',
                26,
            ],
        ]);
    }
}
