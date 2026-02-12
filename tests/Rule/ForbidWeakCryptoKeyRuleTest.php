<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Shopware\PhpStan\Rule\ForbidWeakCryptoKeyRule;

/**
 * @extends RuleTestCase<ForbidWeakCryptoKeyRule>
 */
class ForbidWeakCryptoKeyRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ForbidWeakCryptoKeyRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/fixtures/ForbidWeakCryptoKeyRule/correct-usage.php'], []);
        $this->analyse([__DIR__ . '/fixtures/ForbidWeakCryptoKeyRule/wrong-usage.php'], [
            [
                'Weak cryptographic key size (1024 bits). Use at least 2048 bits for RSA keys.',
                11,
            ],
        ]);
    }
}
