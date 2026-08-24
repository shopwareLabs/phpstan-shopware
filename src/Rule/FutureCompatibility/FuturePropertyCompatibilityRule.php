<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Rule\FutureCompatibility;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;

/**
 * Reports extension assignments incompatible with BC-change attributes announced by Core.
 *
 * @implements Rule<Expr>
 * @internal
 */
final class FuturePropertyCompatibilityRule implements Rule
{
    private const ATTRIBUTE_NAMESPACE = 'Shopware\\Core\\Framework\\Deprecation\\BCChange\\';

    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
        private readonly AnnouncedTypeResolver $typeResolver,
    ) {}

    public function getNodeType(): string
    {
        return Expr::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node instanceof Assign || (!$node->var instanceof PropertyFetch && !$node->var instanceof StaticPropertyFetch) || !$node->var->name instanceof Identifier) {
            return [];
        }

        $propertyName = $node->var->name->toString();
        foreach ($this->properties($node->var, $propertyName, $scope) as $property) {
            if ($this->isDeclaredInCurrentClass($property, $scope)) {
                continue;
            }

            foreach ($property->getAttributes() as $attribute) {
                $arguments = $attribute->getArguments();
                $version = $this->stringArgument($arguments, 'version', 0);

                if ($attribute->getName() === self::ATTRIBUTE_NAMESPACE . 'BecomesReadonly') {
                    return [$this->error(
                        sprintf('Property "%s::$%s" will become readonly in %s. Stop assigning to it outside the declaring class.', $property->getDeclaringClass()->getName(), $property->getName(), $version),
                        'propertyBecomesReadonly',
                    )];
                }

                if ($attribute->getName() !== self::ATTRIBUTE_NAMESPACE . 'PropertyTypeNarrowing') {
                    continue;
                }

                $newType = $arguments['newType'] ?? $arguments[1] ?? null;
                $announced = is_string($newType) ? $this->typeResolver->resolve($newType, $property->getDeclaringClass()->getName()) : null;
                if ($announced === null) {
                    continue;
                }

                $actual = $scope->getType($node->expr);
                if (!$announced->isSuperTypeOf($actual)->no()) {
                    continue;
                }

                return [$this->error(
                    sprintf('Property "%s::$%s" will be narrowed to %s in %s, but %s is assigned. Assign %s to stay compatible with both versions.', $property->getDeclaringClass()->getName(), $property->getName(), $newType, $version, $actual->describe(VerbosityLevel::typeOnly()), $newType),
                    'propertyTypeNarrowing',
                )];
            }
        }

        return [];
    }

    /**
     * @return iterable<\ReflectionProperty>
     */
    private function properties(PropertyFetch|StaticPropertyFetch $fetch, string $propertyName, Scope $scope): iterable
    {
        $classes = [];
        if ($fetch instanceof PropertyFetch) {
            $classes = $scope->getType($fetch->var)->getObjectClassReflections();
        } elseif ($fetch->class instanceof Name) {
            $className = $scope->resolveName($fetch->class);
            if ($this->reflectionProvider->hasClass($className)) {
                $classes[] = $this->reflectionProvider->getClass($className);
            }
        }

        foreach ($classes as $class) {
            $native = $class->getNativeReflection();
            if ($native->hasProperty($propertyName)) {
                yield $native->getProperty($propertyName);
            }
        }
    }

    private function isDeclaredInCurrentClass(\ReflectionProperty $property, Scope $scope): bool
    {
        return $scope->isInClass()
            && $scope->getClassReflection()->getName() === $property->getDeclaringClass()->getName();
    }

    /** @param array<int|string, mixed> $arguments */
    private function stringArgument(array $arguments, string $name, int $position): string
    {
        $value = $arguments[$name] ?? $arguments[$position] ?? '?';

        return is_string($value) ? $value : '?';
    }

    private function error(string $message, string $identifier): IdentifierRuleError
    {
        return RuleErrorBuilder::message($message)
            ->identifier('shopware.futureIncompatibility.' . $identifier)
            ->build();
    }
}
