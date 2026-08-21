<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Fixture\FutureExtensionRule;

use Shopware\Core\Framework\Deprecation\BCChange\BecomesAbstract;
use Shopware\Core\Framework\Deprecation\BCChange\BecomesFinal;
use Shopware\Core\Framework\Deprecation\BCChange\BecomesInternal;
use Shopware\Core\Framework\Deprecation\BCChange\ClassHierarchyChange;
use Shopware\Core\Framework\Deprecation\BCChange\NewOptionalParameter;
use Shopware\Core\Framework\Deprecation\BCChange\ParameterTypeWidening;
use Shopware\Core\Framework\Deprecation\BCChange\ReturnTypeNarrowing;

#[BecomesFinal(version: 'v6.8.0')]
class WillBeFinal {}

#[BecomesInternal(version: 'v6.8.0')]
class WillBeInternal {}

#[ClassHierarchyChange(version: 'v6.8.0', description: 'Will extend EntityCollection directly.')]
class HierarchyChanges {}

abstract class ExtensionPointBase
{
    #[BecomesAbstract(version: 'v6.8.0')]
    public function toBeAbstract(): void {}

    #[NewOptionalParameter(version: 'v6.8.0', parameterName: 'states', parameterType: 'array')]
    public function gainsParameter(string $existing): void {}

    #[ParameterTypeWidening(version: 'v6.8.0', parameterName: 'value', newType: 'string|int')]
    public function widensParameter(string $value): void {}

    #[ReturnTypeNarrowing(version: 'v6.8.0', newType: 'string')]
    public function narrowsReturn(): ?string
    {
        return null;
    }
}

class ExtendsFinal extends WillBeFinal {}

class ExtendsInternal extends WillBeInternal {}

class ExtendsChangingHierarchy extends HierarchyChanges {}

class IncompatibleExtension extends ExtensionPointBase
{
    public function gainsParameter(string $existing): void {}

    public function widensParameter(string $value): void {}

    public function narrowsReturn(): ?string
    {
        return null;
    }
}

class CompatibleExtension extends ExtensionPointBase
{
    public function toBeAbstract(): void {}

    public function gainsParameter(string $existing, array $states = []): void {}

    public function widensParameter(string|int $value): void {}

    public function narrowsReturn(): string
    {
        return 'x';
    }
}

/** @deprecated tag:v6.8.0 - This extension will be removed with the next major version. */
class DeprecatedExtension extends WillBeFinal {}

class MethodDeprecatedExtension extends ExtensionPointBase
{
    public function toBeAbstract(): void {}

    /** @deprecated tag:v6.8.0 - This extension hook will be removed with the next major version. */
    public function gainsParameter(string $existing): void {}

    /** @deprecated tag:v6.8.0 - This extension hook will be removed with the next major version. */
    public function widensParameter(string $value): void {}

    /** @deprecated tag:v6.8.0 - This extension hook will be removed with the next major version. */
    public function narrowsReturn(): ?string
    {
        return null;
    }
}

/** @deprecated tag:v6.9.0 - This extension remains available in v6.8.0. */
class LaterDeprecatedExtension extends WillBeFinal {}
