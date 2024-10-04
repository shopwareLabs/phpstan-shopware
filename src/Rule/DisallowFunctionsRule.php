<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<FuncCall>
 */
class DisallowFunctionsRule implements Rule
{
    private const NOT_ALLOWED_FUNCTIONS = [
        'var_dump',
        'exit',
        'die',
        'dd',
        'dump',
    ];

    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    /**
     * @return array<array-key, RuleError|string>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node instanceof FuncCall) {
            return [];
        }

        if (!$node->name instanceof Node\Name) {
            return [];
        }

        $name = $node->name->toString();

        if (\in_array($name, self::NOT_ALLOWED_FUNCTIONS, true)) {
            return [
                RuleErrorBuilder::message(\sprintf('Do not use %s function in code.', $name))->build(),
            ];
        }

        return [];
    }
}
