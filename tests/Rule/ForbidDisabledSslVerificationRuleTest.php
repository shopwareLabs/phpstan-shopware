<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Shopware\PhpStan\Rule\ForbidDisabledSslVerificationRule;

/**
 * @extends RuleTestCase<ForbidDisabledSslVerificationRule>
 */
class ForbidDisabledSslVerificationRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ForbidDisabledSslVerificationRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/fixtures/ForbidDisabledSslVerificationRule/correct-usage.php'], []);
        $this->analyse([__DIR__ . '/fixtures/ForbidDisabledSslVerificationRule/wrong-usage.php'], [
            [
                'SSL/TLS certificate verification disabled: this allows man-in-the-middle attacks.',
                12,
            ],
            [
                'SSL/TLS certificate verification disabled: this allows man-in-the-middle attacks.',
                18,
            ],
            [
                'SSL/TLS certificate verification disabled: this allows man-in-the-middle attacks.',
                24,
            ],
            [
                'SSL/TLS certificate verification disabled: this allows man-in-the-middle attacks.',
                30,
            ],
            [
                'SSL/TLS certificate verification disabled: this allows man-in-the-middle attacks.',
                35,
            ],
            [
                'SSL/TLS certificate verification disabled: this allows man-in-the-middle attacks.',
                44,
            ],
            [
                'SSL/TLS certificate verification disabled: this allows man-in-the-middle attacks.',
                53,
            ],
        ]);
    }
}
