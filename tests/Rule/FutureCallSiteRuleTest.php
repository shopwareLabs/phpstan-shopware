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
            ['"Shopware\\PhpStan\\Tests\\Fixture\\FutureCallSiteRule\\BCSubject::internalMethod()" will become internal in v6.8.0. Stop calling it to stay compatible.', 55],
            ['"Shopware\\PhpStan\\Tests\\Fixture\\FutureCallSiteRule\\BCSubject::becomesProtected()" will become protected in v6.8.0. This call will break; stop calling it from outside that scope.', 56],
            ['Property "Shopware\\PhpStan\\Tests\\Fixture\\FutureCallSiteRule\\BCSubject::$becomesProtectedProperty" will become protected in v6.8.0. This access will break; stop accessing it from outside that scope.', 57],
            ['Property "Shopware\\PhpStan\\Tests\\Fixture\\FutureCallSiteRule\\BCSubject::$becomesPrivateProperty" will become private in v6.8.0. This access will break; stop accessing it from outside that scope.', 58],
            ['Parameter $options of "Shopware\\PhpStan\\Tests\\Fixture\\FutureCallSiteRule\\BCSubject::withRemoval()" will be removed in v6.8.0. Stop passing it to stay compatible with both versions.', 59],
            ['Parameter $options of "Shopware\\PhpStan\\Tests\\Fixture\\FutureCallSiteRule\\BCSubject::withRemoval()" will be removed in v6.8.0. Stop passing it to stay compatible with both versions.', 60],
            ['Parameter $oldName of "Shopware\\PhpStan\\Tests\\Fixture\\FutureCallSiteRule\\BCSubject::withRename()" will be renamed to $newName in v6.8.0. A named argument cannot be compatible with both versions; pass it positionally.', 63],
            ['Parameter $id of "Shopware\\PhpStan\\Tests\\Fixture\\FutureCallSiteRule\\BCSubject::withNarrowing()" will be narrowed to string in v6.8.0, but int is passed. Pass string to stay compatible with both versions.', 65],
            ['"Shopware\\PhpStan\\Tests\\Fixture\\FutureCallSiteRule\\BCSubject::withNewRequired()" will require a new parameter $context in v6.8.0. Pass it positionally now to stay compatible with both versions.', 66],
            ['The default value of parameter $strict of "Shopware\\PhpStan\\Tests\\Fixture\\FutureCallSiteRule\\BCSubject::withDefaultValue()" will change in v6.8.0. Pass the current default explicitly to retain current behavior.', 68],
            ['Class "Shopware\\PhpStan\\Tests\\Fixture\\FutureCallSiteRule\\InternalSubject" will become internal in v6.8.0. Stop using it to stay compatible.', 70],
            ['Class "Shopware\\PhpStan\\Tests\\Fixture\\FutureCallSiteRule\\InternalSubject" will become internal in v6.8.0. Stop using it to stay compatible.', 71],
            ['"Shopware\\PhpStan\\Tests\\Fixture\\FutureCallSiteRule\\BCSubject::internalMethod()" will become internal in v6.8.0. Stop calling it to stay compatible.', 109],
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
