<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Shopware\PhpStan\Rule\ForbidInsecureSymfonyCookieRule;

/**
 * @extends RuleTestCase<ForbidInsecureSymfonyCookieRule>
 */
class ForbidInsecureSymfonyCookieRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ForbidInsecureSymfonyCookieRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/fixtures/ForbidInsecureSymfonyCookieRule/correct-usage.php'], []);
        $this->analyse([__DIR__ . '/fixtures/ForbidInsecureSymfonyCookieRule/wrong-usage.php'], [
            [
                'Symfony Cookie created without explicit secure flag. Use the $secure parameter set to true for HTTPS-only transmission.',
                13,
            ],
            [
                'Symfony Cookie created without explicit secure flag. Use the $secure parameter set to true for HTTPS-only transmission.',
                18,
            ],
            [
                'Symfony Cookie created without explicit secure flag. Use the $secure parameter set to true for HTTPS-only transmission.',
                23,
            ],
            [
                'Symfony Cookie created without explicit secure flag. Use the $secure parameter set to true for HTTPS-only transmission.',
                28,
            ],
            [
                'Symfony Cookie created without explicit secure flag. Use the $secure parameter set to true for HTTPS-only transmission.',
                33,
            ],
            [
                'Symfony Cookie created without explicit secure flag. Use the $secure parameter set to true for HTTPS-only transmission.',
                39,
            ],
            [
                'Symfony Cookie created without explicit secure flag. Use the $secure parameter set to true for HTTPS-only transmission.',
                45,
            ],
            [
                'Symfony Cookie created without explicit secure flag. Use the $secure parameter set to true for HTTPS-only transmission.',
                51,
            ],
            [
                'Symfony Cookie created without explicit secure flag. Use the $secure parameter set to true for HTTPS-only transmission.',
                57,
            ],
            [
                'Symfony Cookie created without explicit secure flag. Use the $secure parameter set to true for HTTPS-only transmission.',
                63,
            ],
        ]);
    }
}
