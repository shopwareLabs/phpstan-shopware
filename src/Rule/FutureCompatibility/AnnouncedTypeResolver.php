<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Rule\FutureCompatibility;

use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/** @internal */
final readonly class AnnouncedTypeResolver
{
    public function __construct(
        private TypeStringResolver $typeStringResolver,
        private ReflectionProvider $reflectionProvider,
    ) {}

    public function resolve(string $typeString, string $declaringClass): ?Type
    {
        if (in_array(strtolower($typeString), ['self', 'static', '$this'], true)) {
            return new ObjectType($declaringClass);
        }

        try {
            $type = $this->typeStringResolver->resolve($typeString);
        } catch (\Throwable) {
            return null;
        }

        foreach ($type->getReferencedClasses() as $class) {
            if (!$this->reflectionProvider->hasClass($class)) {
                return null;
            }
        }

        return $type;
    }
}
