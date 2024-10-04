<?php

namespace Shopware\PhpStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Shopware\PhpStan\Rule\SetForeignKeyRule;

class SetForeignKeyRuleTest extends RuleTestCase
{
    public function testAnalyse(): void
    {
        $this->analyse([__DIR__ . '/../data/SetForeignKeyRule/foreign-key.php'], [
            [
                'Do not disable FOREIGN KEY checks in migrations. Delete the data in the right order',
                15,
            ],
        ]);
    }

    protected function getRule(): Rule
    {
        return new SetForeignKeyRule();
    }
}
