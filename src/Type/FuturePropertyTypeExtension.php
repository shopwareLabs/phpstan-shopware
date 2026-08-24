<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Type;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ExpressionTypeResolverExtension;
use PHPStan\Type\Type;
use Shopware\PhpStan\Rule\FutureCompatibility\AnnouncedTypeResolver;

/** @internal */
final readonly class FuturePropertyTypeExtension implements ExpressionTypeResolverExtension
{
    private const PROPERTY_TYPE_WIDENING = 'Shopware\\Core\\Framework\\Deprecation\\BCChange\\PropertyTypeWidening';

    public function __construct(
        private ReflectionProvider $reflectionProvider,
        private AnnouncedTypeResolver $typeResolver,
    ) {}

    public function getType(Expr $expr, Scope $scope): ?Type
    {
        if (($expr instanceof PropertyFetch || $expr instanceof StaticPropertyFetch) && $expr->name instanceof Identifier) {
            foreach ($this->propertyClasses($expr, $scope) as $class) {
                $native = $class->getNativeReflection();
                if (!$native->hasProperty($expr->name->toString())) {
                    continue;
                }

                foreach ($native->getProperty($expr->name->toString())->getAttributes() as $attribute) {
                    if ($attribute->getName() === self::PROPERTY_TYPE_WIDENING) {
                        $newType = $attribute->getArguments()['newType'] ?? $attribute->getArguments()[1] ?? null;

                        return is_string($newType) ? $this->typeResolver->resolve($newType, $native->getName()) : null;
                    }
                }
            }
        }

        return null;
    }

    /** @return list<\PHPStan\Reflection\ClassReflection> */
    private function propertyClasses(PropertyFetch|StaticPropertyFetch $fetch, Scope $scope): array
    {
        if ($fetch instanceof PropertyFetch) {
            return $scope->getType($fetch->var)->getObjectClassReflections();
        }
        if ($fetch->class instanceof Name) {
            $className = $scope->resolveName($fetch->class);

            return $this->reflectionProvider->hasClass($className) ? [$this->reflectionProvider->getClass($className)] : [];
        }

        return [];
    }
}
