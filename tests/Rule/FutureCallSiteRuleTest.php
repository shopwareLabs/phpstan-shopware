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
    public function testReportsFutureIncompatibleCallSite(): void
    {
        $this->analyse([__DIR__ . '/fixtures/FutureCallSiteRule/call-site.php'], [
            ['Parameter $id of "Shopware\\PhpStan\\Tests\\Fixture\\FutureSubject::find()" will be narrowed to string in v6.8.0, but int is passed. Pass string to stay compatible with both versions.', 21],
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
