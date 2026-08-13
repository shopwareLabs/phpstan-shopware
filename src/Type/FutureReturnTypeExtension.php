<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Type;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnum;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ExpressionTypeResolverExtension;
use PHPStan\Type\Type;
use Shopware\PhpStan\Rule\FutureCompatibility\AnnouncedTypeResolver;

/** @internal */
final readonly class FutureReturnTypeExtension implements ExpressionTypeResolverExtension
{
    private const RETURN_TYPE_WIDENING = 'Shopware\\Core\\Framework\\Deprecation\\BCChange\\ReturnTypeWidening';

    public function __construct(
        private ReflectionProvider $reflectionProvider,
        private AnnouncedTypeResolver $typeResolver,
    ) {}

    public function getType(Expr $expr, Scope $scope): ?Type
    {
        if ($expr instanceof MethodCall && $expr->name instanceof Identifier) {
            foreach ($scope->getType($expr->var)->getObjectClassReflections() as $class) {
                $type = $this->returnType($class->getNativeReflection(), $expr->name->toString());
                if ($type !== null) {
                    return $type;
                }
            }

            return null;
        }
        if ($expr instanceof StaticCall && $expr->name instanceof Identifier && $expr->class instanceof Name) {
            $class = $scope->resolveName($expr->class);

            return $this->reflectionProvider->hasClass($class) ? $this->returnType($this->reflectionProvider->getClass($class)->getNativeReflection(), $expr->name->toString()) : null;
        }

        return null;
    }

    private function returnType(ReflectionClass|ReflectionEnum $class, string $method): ?Type
    {
        if (!$class->hasMethod($method)) {
            return null;
        }
        $reflection = $class->getMethod($method);
        foreach ($reflection->getAttributes() as $attribute) {
            if ($attribute->getName() === self::RETURN_TYPE_WIDENING) {
                $arguments = $attribute->getArguments();
                $type = $arguments['newType'] ?? $arguments[1] ?? null;

                return is_string($type) ? $this->typeResolver->resolve($type, $reflection->getDeclaringClass()->getName()) : null;
            }
        }

        return null;
    }
}
