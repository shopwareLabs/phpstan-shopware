<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Rule;

use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Shopware\PhpStan\Rule\FutureCompatibility\AnnouncedTypeResolver;
use Shopware\PhpStan\Rule\FutureCompatibility\FutureExtensionRule;

/**
 * @extends RuleTestCase<FutureExtensionRule>
 * @internal
 */
class FutureExtensionRuleTest extends RuleTestCase
{
    public function testReportsFutureIncompatibleExtensions(): void
    {
        $this->analyse([__DIR__ . '/fixtures/FutureExtensionRule/future-extenders.php'], [
            ['"Shopware\\PhpStan\\Tests\\Fixture\\FutureExtensionRule\\ExtendsFinal" extends "Shopware\\PhpStan\\Tests\\Fixture\\FutureExtensionRule\\WillBeFinal", which will become final in v6.8.0. There is no forward-compatible way to keep extending it.', 42],
            ['"Shopware\\PhpStan\\Tests\\Fixture\\FutureExtensionRule\\ExtendsInternal" extends "Shopware\\PhpStan\\Tests\\Fixture\\FutureExtensionRule\\WillBeInternal", which will become internal in v6.8.0. Stop extending it to stay compatible.', 44],
            ['"Shopware\\PhpStan\\Tests\\Fixture\\FutureExtensionRule\\ExtensionPointBase::gainsParameter()" will get a new optional parameter $states (array) in v6.8.0. Add it to the override in "Shopware\\PhpStan\\Tests\\Fixture\\FutureExtensionRule\\IncompatibleExtension" now to stay compatible with both versions.', 48],
            ['"Shopware\\PhpStan\\Tests\\Fixture\\FutureExtensionRule\\ExtensionPointBase::toBeAbstract()" will become abstract in v6.8.0. Implement it in "Shopware\\PhpStan\\Tests\\Fixture\\FutureExtensionRule\\IncompatibleExtension" now to stay compatible with both versions.', 48],
            ['Parameter $value of "Shopware\\PhpStan\\Tests\\Fixture\\FutureExtensionRule\\ExtensionPointBase::widensParameter()" will be widened to string|int in v6.8.0. Widen the override in "Shopware\\PhpStan\\Tests\\Fixture\\FutureExtensionRule\\IncompatibleExtension" now to stay compatible with both versions.', 48],
            ['The return type of "Shopware\\PhpStan\\Tests\\Fixture\\FutureExtensionRule\\ExtensionPointBase::narrowsReturn()" will be narrowed to string in v6.8.0. Narrow the override in "Shopware\\PhpStan\\Tests\\Fixture\\FutureExtensionRule\\IncompatibleExtension" now to stay compatible with both versions.', 48],
        ]);
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = self::createReflectionProvider();

        return new FutureExtensionRule(new AnnouncedTypeResolver(
            self::getContainer()->getByType(TypeStringResolver::class),
            $reflectionProvider,
        ));
    }
}
