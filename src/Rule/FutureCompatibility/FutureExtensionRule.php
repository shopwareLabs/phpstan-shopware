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
                if ($attribute->getName() === self::ATTRIBUTE_NAMESPACE . 'BecomesFinal') {
                    $errors[] = $this->error(sprintf('"%s" extends "%s", which will become final in %s. There is no forward-compatible way to keep extending it.', $class->getDisplayName(), $parent->getDisplayName(), $version));
                }
                if ($attribute->getName() === self::ATTRIBUTE_NAMESPACE . 'BecomesInternal') {
                    $errors[] = $this->error(sprintf('"%s" extends "%s", which will become internal in %s. Stop extending it to stay compatible.', $class->getDisplayName(), $parent->getDisplayName(), $version));
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
                    if ($name === self::ATTRIBUTE_NAMESPACE . 'BecomesAbstract' && $override === null && !$class->isAbstract()) {
                        $errors[] = $this->error(sprintf('"%s::%s()" will become abstract in %s. Implement it in "%s" now to stay compatible with both versions.', $parent->getDisplayName(), $method->getName(), $version, $class->getDisplayName()));
                    }
                    if ($override === null) {
                        continue;
                    }
                    if ($name === self::ATTRIBUTE_NAMESPACE . 'NewOptionalParameter') {
                        $parameter = $arguments['parameterName'] ?? $arguments[1] ?? null;
                        if (is_string($parameter) && !$this->hasParameter($override, $parameter)) {
                            $type = $this->stringArgument($arguments, 'parameterType', 2);
                            $errors[] = $this->error(sprintf('"%s::%s()" will get a new optional parameter $%s (%s) in %s. Add it to the override in "%s" now to stay compatible with both versions.', $parent->getDisplayName(), $method->getName(), $parameter, $type, $version, $class->getDisplayName()));
                        }
                    }
                    if ($name === self::ATTRIBUTE_NAMESPACE . 'ParameterTypeWidening') {
                        $parameter = $arguments['parameterName'] ?? $arguments[1] ?? null;
                        $newType = $arguments['newType'] ?? $arguments[2] ?? null;
                        $announced = is_string($newType) ? $this->typeResolver->resolve($newType, $method->getDeclaringClass()->getName()) : null;
                        $current = is_string($parameter) ? $this->parameterType($class, $override->getName(), $parameter) : null;
                        if ($announced !== null && $current !== null && !$current->isSuperTypeOf($announced)->yes()) {
                            $errors[] = $this->error(sprintf('Parameter $%s of "%s::%s()" will be widened to %s in %s. Widen the override in "%s" now to stay compatible with both versions.', $parameter, $parent->getDisplayName(), $method->getName(), $newType, $version, $class->getDisplayName()));
                        }
                    }
                    if ($name === self::ATTRIBUTE_NAMESPACE . 'ReturnTypeNarrowing') {
                        $newType = $arguments['newType'] ?? $arguments[1] ?? null;
                        $announced = is_string($newType) && in_array(strtolower($newType), ['self', 'static', '$this'], true) ? new ObjectType($class->getName()) : (is_string($newType) ? $this->typeResolver->resolve($newType, $method->getDeclaringClass()->getName()) : null);
                        $return = $class->getNativeMethod($override->getName())->getVariants()[0]->getReturnType();
                        if ($announced !== null && !$announced->isSuperTypeOf($return)->yes()) {
                            $errors[] = $this->error(sprintf('The return type of "%s::%s()" will be narrowed to %s in %s. Narrow the override in "%s" now to stay compatible with both versions.', $parent->getDisplayName(), $method->getName(), $newType, $version, $class->getDisplayName()));
                        }
                    }
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

    private function error(string $message): IdentifierRuleError
    {
        return RuleErrorBuilder::message($message)->identifier('shopware.futureIncompatibility')->build();
    }
}
