<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Class_>
 */
class ClassExtendUsesAbstractClassWhenExisting implements Rule
{
    private ReflectionProvider $reflectionProvider;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
    }

    /**
     * This rule is applied to class nodes.
     */
    public function getNodeType(): string
    {
        return Class_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        // Only proceed if the class extends a parent.
        if ($node->extends === null) {
            return [];
        }

        $parentClassName = (string) $node->extends;
        $parentClassReflection = $this->reflectionProvider->getClass($parentClassName);

        if (!$parentClassReflection->hasMethod('getDecorated')) {
            return [];
        }

        if ($parentClassReflection->isAbstract()) {
            return [];
        }

        foreach ($parentClassReflection->getParents() as $parent) {
            if ($parent->isAbstract()) {
                return [
                    RuleErrorBuilder::message(sprintf('Class %s should extend %s to not break typehints', (string) $node->name, $parent->getName()))
                        ->line($node->getLine())
                        ->identifier('shopware.class.extends.abstract')
                        ->build(),
                ];
            }
        }

        return [];
    }
}
