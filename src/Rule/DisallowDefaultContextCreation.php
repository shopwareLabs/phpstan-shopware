<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<StaticCall>
 *
 * @internal
 */
class DisallowDefaultContextCreation implements Rule
{
    public function __construct(private readonly ReflectionProvider $reflectionProvider) {}

    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Node\Identifier) {
            return [];
        }

        if ($node->name->name !== 'createDefaultContext') {
            return [];
        }

        if (!$node->class instanceof Node\Name) {
            return [];
        }

        if (implode('\\', $node->class->getParts()) !== 'Shopware\\Core\\Framework\\Context') {
            return [];
        }

        if (!$this->reflectionProvider->hasClass('Shopware\Core\Framework\Context')) {
            return [];
        }

        $class = $this->reflectionProvider->getClass('Shopware\Core\Framework\Context');

        if (!$class->hasMethod('createCLIContext')) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf('Do not use %s::createDefaultContext() function in code.', $node->class->toString()))
                ->addTip('If you are in a CLI context, use %s::createCLIContext() instead.')
                ->addTip('If you are in a web context, pass down the context from the controller.')
                ->identifier('shopware.disallow.default.context.creation')
                ->build(),
        ];
    }
}
