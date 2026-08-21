<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Rule;

use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Shopware\PhpStan\Rule\FutureCompatibility\AnnouncedTypeResolver;
use Shopware\PhpStan\Rule\FutureCompatibility\FutureCallSiteRule;

/**
 * @extends RuleTestCase<FutureCallSiteRule>
 * @internal
 */
class FutureCallSiteRuleTest extends RuleTestCase
{
    public function testReportsFutureIncompatibleCallSites(): void
    {
        $this->analyse([__DIR__ . '/fixtures/FutureCallSiteRule/call-site.php'], [
            ['"Shopware\\PhpStan\\Tests\\Fixture\\FutureCallSiteRule\\BCSubject::internalMethod()" will become internal in v6.8.0. Stop calling it to stay compatible.', 49],
            ['"Shopware\\PhpStan\\Tests\\Fixture\\FutureCallSiteRule\\BCSubject::becomesProtected()" will become protected in v6.8.0. This call will break; stop calling it from outside that scope.', 50],
            ['Parameter $options of "Shopware\\PhpStan\\Tests\\Fixture\\FutureCallSiteRule\\BCSubject::withRemoval()" will be removed in v6.8.0. Stop passing it to stay compatible with both versions.', 51],
            ['Parameter $options of "Shopware\\PhpStan\\Tests\\Fixture\\FutureCallSiteRule\\BCSubject::withRemoval()" will be removed in v6.8.0. Stop passing it to stay compatible with both versions.', 52],
            ['Parameter $oldName of "Shopware\\PhpStan\\Tests\\Fixture\\FutureCallSiteRule\\BCSubject::withRename()" will be renamed to $newName in v6.8.0. A named argument cannot be compatible with both versions; pass it positionally.', 55],
            ['Parameter $id of "Shopware\\PhpStan\\Tests\\Fixture\\FutureCallSiteRule\\BCSubject::withNarrowing()" will be narrowed to string in v6.8.0, but int is passed. Pass string to stay compatible with both versions.', 57],
            ['"Shopware\\PhpStan\\Tests\\Fixture\\FutureCallSiteRule\\BCSubject::withNewRequired()" will require a new parameter $context in v6.8.0. Pass it positionally now to stay compatible with both versions.', 58],
            ['The default value of parameter $strict of "Shopware\\PhpStan\\Tests\\Fixture\\FutureCallSiteRule\\BCSubject::withDefaultValue()" will change in v6.8.0. Pass the current default explicitly to retain current behavior.', 60],
            ['Class "Shopware\\PhpStan\\Tests\\Fixture\\FutureCallSiteRule\\InternalSubject" will become internal in v6.8.0. Stop using it to stay compatible.', 62],
            ['Class "Shopware\\PhpStan\\Tests\\Fixture\\FutureCallSiteRule\\InternalSubject" will become internal in v6.8.0. Stop using it to stay compatible.', 63],
            ['"Shopware\\PhpStan\\Tests\\Fixture\\FutureCallSiteRule\\BCSubject::internalMethod()" will become internal in v6.8.0. Stop calling it to stay compatible.', 100],
        ]);
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = self::createReflectionProvider();

        return new FutureCallSiteRule($reflectionProvider, new AnnouncedTypeResolver(
            self::getContainer()->getByType(TypeStringResolver::class),
            $reflectionProvider,
        ));
    }
}
