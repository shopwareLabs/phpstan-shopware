<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Rule;

use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Shopware\PhpStan\Rule\FutureCompatibility\AnnouncedTypeResolver;
use Shopware\PhpStan\Rule\FutureCompatibility\FuturePropertyCompatibilityRule;

/**
 * @extends RuleTestCase<FuturePropertyCompatibilityRule>
 * @internal
 */
class FuturePropertyCompatibilityRuleTest extends RuleTestCase
{
    public function testReportsAssignmentsIncompatibleWithFutureProperties(): void
    {
        $this->analyse([__DIR__ . '/fixtures/FuturePropertyCompatibilityRule/property-assignment.php'], [
            ['Property "Shopware\\PhpStan\\Tests\\Fixture\\FuturePropertyCompatibilityRule\\FutureNarrowedProperty::$value" will be narrowed to string in v6.8.0, but null is assigned. Assign string to stay compatible with both versions.', 26],
            ['Property "Shopware\\PhpStan\\Tests\\Fixture\\FuturePropertyCompatibilityRule\\FutureReadonlyProperty::$value" will become readonly in v6.8.0. Stop assigning to it outside the declaring class.', 39],
        ]);
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = self::createReflectionProvider();

        return new FuturePropertyCompatibilityRule($reflectionProvider, new AnnouncedTypeResolver(
            self::getContainer()->getByType(TypeStringResolver::class),
            $reflectionProvider,
        ));
    }
}
