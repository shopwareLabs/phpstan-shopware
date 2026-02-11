<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<FuncCall>
 */
class ForbidPredictableSaltRule implements Rule
{
    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    /**
     * @return array<array-key, RuleError|string>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Node\Name) {
            return [];
        }

        $funcName = $node->name->toLowerString();

        if ($funcName === 'crypt') {
            $args = $node->getArgs();
            // crypt($password, $salt): 2nd argument is a hardcoded salt string
            if (count($args) >= 2 && $args[1]->value instanceof String_) {
                return [
                    RuleErrorBuilder::message('Hardcoded salt in password hashing is predictable and weakens security.')
                        ->identifier('shopware.forbidPredictableSalt')
                        ->line($node->getLine())
                        ->build(),
                ];
            }

            return [];
        }

        if ($funcName === 'password_hash') {
            $args = $node->getArgs();
            // password_hash($password, $algo, $options): no 3rd argument means no custom salt
            if (count($args) < 3) {
                return [];
            }

            $optionsArg = $args[2]->value;
            if (!$optionsArg instanceof Array_) {
                return [];
            }

            foreach ($optionsArg->items as $item) {
                // 3rd argument contains ['salt' => ...]: explicit salt in options array
                if ($item->key instanceof String_ && $item->key->value === 'salt') {
                    return [
                        RuleErrorBuilder::message('Hardcoded salt in password hashing is predictable and weakens security.')
                            ->identifier('shopware.forbidPredictableSalt')
                            ->line($node->getLine())
                            ->build(),
                    ];
                }
            }
        }

        return [];
    }
}
