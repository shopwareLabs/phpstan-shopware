<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Rule\FutureCompatibility;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Type\ObjectType;

/**
 * Reports extension classes incompatible with BC-change attributes announced by Core.
 *
 * @implements Rule<InClassNode>
 * @internal
 */
final class FutureExtensionRule implements Rule
{
    private const ATTRIBUTE_NAMESPACE = 'Shopware\\Core\\Framework\\Deprecation\\BCChange\\';

    private const EXTENDS_CLASS_BECOMING_FINAL = 'shopware.futureIncompatibility.extendsClassBecomingFinal';
    private const EXTENDS_CLASS_BECOMING_INTERNAL = 'shopware.futureIncompatibility.extendsClassBecomingInternal';
    private const MISSING_BECOMES_ABSTRACT_METHOD_IMPLEMENTATION = 'shopware.futureIncompatibility.missingBecomesAbstractMethodImplementation';
    private const MISSING_NEW_OPTIONAL_PARAMETER_IN_OVERRIDE = 'shopware.futureIncompatibility.missingNewOptionalParameterInOverride';
    private const PARAMETER_TYPE_WIDENING_IN_OVERRIDE = 'shopware.futureIncompatibility.parameterTypeWideningInOverride';
    private const RETURN_TYPE_NARROWING_IN_OVERRIDE = 'shopware.futureIncompatibility.returnTypeNarrowingInOverride';
    private const PROPERTY_REDECLARATION = 'shopware.futureIncompatibility.propertyRedeclaration';

    public function __construct(private readonly AnnouncedTypeResolver $typeResolver) {}

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $class = $node->getClassReflection();
        $errors = [];

        foreach ($class->getParents() as $parent) {
            foreach ($parent->getNativeReflection()->getAttributes() as $attribute) {
                $arguments = $attribute->getArguments();
                $version = $this->stringArgument($arguments, 'version', 0);
                if ($attribute->getName() === self::ATTRIBUTE_NAMESPACE . 'BecomesFinal' && !$this->isDeprecatedInVersion($class, null, $scope, $version)) {
                    $errors[] = $this->error(sprintf('"%s" extends "%s", which will become final in %s. There is no forward-compatible way to keep extending it.', $class->getDisplayName(), $parent->getDisplayName(), $version), self::EXTENDS_CLASS_BECOMING_FINAL);
                }
                if ($attribute->getName() === self::ATTRIBUTE_NAMESPACE . 'BecomesInternal' && !$this->isDeprecatedInVersion($class, null, $scope, $version)) {
                    $errors[] = $this->error(sprintf('"%s" extends "%s", which will become internal in %s. Stop extending it to stay compatible.', $class->getDisplayName(), $parent->getDisplayName(), $version), self::EXTENDS_CLASS_BECOMING_INTERNAL);
                }
            }

            foreach ($parent->getNativeReflection()->getMethods() as $method) {
                if ($method->getDeclaringClass()->getName() !== $parent->getName()) {
                    continue;
                }
                $override = $this->override($class, $method->getName());
                foreach ($method->getAttributes() as $attribute) {
                    $arguments = $attribute->getArguments();
                    $version = $this->stringArgument($arguments, 'version', 0);
                    $name = $attribute->getName();
                    if ($name === self::ATTRIBUTE_NAMESPACE . 'BecomesAbstract' && !$this->hasConcreteImplementation($class, $parent, $method->getName()) && !$class->isAbstract() && !$this->isDeprecatedInVersion($class, null, $scope, $version)) {
                        $errors[] = $this->error(sprintf('"%s::%s()" will become abstract in %s. Implement it in "%s" now to stay compatible with both versions.', $parent->getDisplayName(), $method->getName(), $version, $class->getDisplayName()), self::MISSING_BECOMES_ABSTRACT_METHOD_IMPLEMENTATION);
                    }
                    if ($override === null) {
                        continue;
                    }
                    if ($this->isDeprecatedInVersion($class, $override->getName(), $scope, $version)) {
                        continue;
                    }
                    if ($name === self::ATTRIBUTE_NAMESPACE . 'NewOptionalParameter') {
                        $parameter = $arguments['parameterName'] ?? $arguments[1] ?? null;
                        if (is_string($parameter) && !$this->hasParameter($override, $parameter)) {
                            $type = $this->stringArgument($arguments, 'parameterType', 2);
                            $errors[] = $this->error(sprintf('"%s::%s()" will get a new optional parameter $%s (%s) in %s. Add it to the override in "%s" now to stay compatible with both versions.', $parent->getDisplayName(), $method->getName(), $parameter, $type, $version, $class->getDisplayName()), self::MISSING_NEW_OPTIONAL_PARAMETER_IN_OVERRIDE);
                        }
                    }
                    if ($name === self::ATTRIBUTE_NAMESPACE . 'ParameterTypeWidening') {
                        $parameter = $arguments['parameterName'] ?? $arguments[1] ?? null;
                        $newType = $arguments['newType'] ?? $arguments[2] ?? null;
                        $announced = is_string($newType) ? $this->typeResolver->resolve($newType, $method->getDeclaringClass()->getName()) : null;
                        $current = is_string($parameter) ? $this->parameterType($class, $override->getName(), $parameter) : null;
                        if ($announced !== null && $current !== null && !$current->isSuperTypeOf($announced)->yes()) {
                            $errors[] = $this->error(sprintf('Parameter $%s of "%s::%s()" will be widened to %s in %s. Widen the override in "%s" now to stay compatible with both versions.', $parameter, $parent->getDisplayName(), $method->getName(), $newType, $version, $class->getDisplayName()), self::PARAMETER_TYPE_WIDENING_IN_OVERRIDE);
                        }
                    }
                    if ($name === self::ATTRIBUTE_NAMESPACE . 'ReturnTypeNarrowing') {
                        $newType = $arguments['newType'] ?? $arguments[1] ?? null;
                        $announced = is_string($newType) && in_array(strtolower($newType), ['self', 'static', '$this'], true) ? new ObjectType($class->getName()) : (is_string($newType) ? $this->typeResolver->resolve($newType, $method->getDeclaringClass()->getName()) : null);
                        $return = $class->getNativeMethod($override->getName())->getVariants()[0]->getReturnType();
                        if ($announced !== null && !$announced->isSuperTypeOf($return)->yes()) {
                            $errors[] = $this->error(sprintf('The return type of "%s::%s()" will be narrowed to %s in %s. Narrow the override in "%s" now to stay compatible with both versions.', $parent->getDisplayName(), $method->getName(), $newType, $version, $class->getDisplayName()), self::RETURN_TYPE_NARROWING_IN_OVERRIDE);
                        }
                    }
                }
            }

            foreach ($parent->getNativeReflection()->getProperties() as $property) {
                $native = $class->getNativeReflection();
                if ($property->getDeclaringClass()->getName() !== $parent->getName()
                    || !$native->hasProperty($property->getName())
                    || $native->getProperty($property->getName())->getDeclaringClass()->getName() !== $native->getName()
                ) {
                    continue;
                }

                foreach ($property->getAttributes() as $attribute) {
                    $arguments = $attribute->getArguments();
                    $version = $this->stringArgument($arguments, 'version', 0);
                    $name = $attribute->getName();
                    if (!in_array($name, [
                        self::ATTRIBUTE_NAMESPACE . 'BecomesReadonly',
                        self::ATTRIBUTE_NAMESPACE . 'PropertyTypeNarrowing',
                        self::ATTRIBUTE_NAMESPACE . 'PropertyTypeWidening',
                    ], true)
                        && !($name === self::ATTRIBUTE_NAMESPACE . 'VisibilityChange' && ($arguments['newVisibility'] ?? $arguments[1] ?? null) === 'private')
                    ) {
                        continue;
                    }

                    $errors[] = $this->error(sprintf('Property "%s::$%s" redeclares "%s::$%s", which has an incompatible property change in %s. Stop redeclaring it; there is no forward-compatible declaration.', $class->getDisplayName(), $property->getName(), $parent->getDisplayName(), $property->getName(), $version), self::PROPERTY_REDECLARATION);
                }
            }
        }

        return $errors;
    }

    private function override(ClassReflection $class, string $method): ?\ReflectionMethod
    {
        $native = $class->getNativeReflection();

        return $native->hasMethod($method) && $native->getMethod($method)->getDeclaringClass()->getName() === $native->getName() ? $native->getMethod($method) : null;
    }

    private function hasConcreteImplementation(ClassReflection $class, ClassReflection $abstractParent, string $method): bool
    {
        $native = $class->getNativeReflection();
        if (!$native->hasMethod($method)) {
            return false;
        }

        $implementation = $native->getMethod($method);

        return !$implementation->isAbstract() && $implementation->getDeclaringClass()->getName() !== $abstractParent->getName();
    }

    private function hasParameter(\ReflectionMethod $method, string $name): bool
    {
        foreach ($method->getParameters() as $parameter) {
            if ($parameter->getName() === $name) {
                return true;
            }
        }

        return false;
    }

    private function parameterType(ClassReflection $class, string $method, string $name): ?\PHPStan\Type\Type
    {
        foreach ($class->getNativeMethod($method)->getVariants()[0]->getParameters() as $parameter) {
            if ($parameter->getName() === $name) {
                return $parameter->getType();
            }
        }

        return null;
    }

    /** @param array<int|string, mixed> $arguments */
    private function stringArgument(array $arguments, string $name, int $position): string
    {
        $value = $arguments[$name] ?? $arguments[$position] ?? '?';

        return is_string($value) ? $value : '?';
    }

    private function isDeprecatedInVersion(ClassReflection $class, ?string $method, Scope $scope, string $version): bool
    {
        return $this->hasDeprecationTagForVersion($class->getDeprecatedDescription(), $version)
            || ($method !== null && $this->hasDeprecationTagForVersion($class->getMethod($method, $scope)->getDeprecatedDescription(), $version));
    }

    private function hasDeprecationTagForVersion(?string $description, string $version): bool
    {
        return $description !== null && preg_match(sprintf('/(?:^|\\s)tag:%s(?:\\s|$)/', preg_quote($version, '/')), $description) === 1;
    }

    private function error(string $message, string $identifier): IdentifierRuleError
    {
        return RuleErrorBuilder::message($message)->identifier($identifier)->build();
    }
}
