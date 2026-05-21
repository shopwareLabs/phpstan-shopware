<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Rule\BestPractise;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Shopware\PhpStan\Collector\DALDefinitionCollector;
use Shopware\PhpStan\Collector\DALEntityCollector;
use Shopware\PhpStan\Rule\BestPractise\DALDefinitionRule;

/**
 * @extends RuleTestCase<DALDefinitionRule>
 */
class DALDefinitionRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new DALDefinitionRule();
    }

    protected function getCollectors(): array
    {
        return [
            new DALDefinitionCollector(self::createReflectionProvider()),
            new DALEntityCollector(),
        ];
    }

    public function testMissingProperty(): void
    {
        $this->analyse([__DIR__ . '/fixtures/DALDefinitionRule/missing-property.php'], [
            [
                'The field "name" in the definition "foo" is not defined in the entity "Shopware\Tests\Rule\BestPractise\fixtures\DALDefinitionRule\FooEntity".',
                1,
            ],
        ]);
    }

    public function testMissingGetterSetter(): void
    {
        $this->analyse([__DIR__ . '/fixtures/DALDefinitionRule/missing-getter-setter.php'], [
            [
                'The field "name" in the definition "foo" is protected, but has no getter method',
                42,
            ],
            [
                'The field "name" in the definition "foo" is protected, but has no setter method',
                42,
            ],
        ]);
    }


    public function testPublicProperty(): void
    {
        $this->analyse([__DIR__ . '/fixtures/DALDefinitionRule/public-property.php'], []);
    }

    public function testChildrenAssociationField(): void
    {
        $this->analyse([__DIR__ . '/fixtures/DALDefinitionRule/children-association-field.php'], [
            [
                'The field "children" in the definition "tree" is not defined in the entity "Shopware\Tests\Rule\BestPractise\fixtures\DALDefinitionRule\TreeEntity".',
                1,
            ],
            [
                'The field "customChildren" in the definition "tree" is not defined in the entity "Shopware\Tests\Rule\BestPractise\fixtures\DALDefinitionRule\TreeEntity".',
                1,
            ],
        ]);
    }

    public function testReferenceVersionField(): void
    {
        $this->analyse([__DIR__ . '/fixtures/DALDefinitionRule/reference-version-field.php'], [
            [
                'The field "parentVersionId" in the definition "foo" is not defined in the entity "Shopware\Tests\Rule\BestPractise\fixtures\DALDefinitionRule\FooEntity".',
                1,
            ],
            [
                'The field "fooVersionId" in the definition "foo" is not defined in the entity "Shopware\Tests\Rule\BestPractise\fixtures\DALDefinitionRule\FooEntity".',
                1,
            ],
        ]);
    }

    public function testConstantValuesInField(): void
    {
        $this->analyse([__DIR__ . '/fixtures/DALDefinitionRule/constants-in-field.php'], []);
    }
}
