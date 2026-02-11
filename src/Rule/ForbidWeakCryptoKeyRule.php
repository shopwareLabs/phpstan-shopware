<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<FuncCall>
 */
class ForbidWeakCryptoKeyRule implements Rule
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
        if (!$node->name instanceof Node\Name || $node->name->toLowerString() !== 'openssl_pkey_new') {
            return [];
        }

        $args = $node->getArgs();
        if (count($args) < 1) {
            return [];
        }

        // openssl_pkey_new($config): 1st argument is the config array
        $configArg = $args[0]->value;
        if (!$configArg instanceof Array_) {
            return [];
        }

        foreach ($configArg->items as $item) {
            if (!$item->key instanceof String_) {
                continue;
            }

            // config contains ['private_key_bits' => N] with N < 2048
            if ($item->key->value === 'private_key_bits' && $item->value instanceof Int_ && $item->value->value < 2048) {
                return [
                    RuleErrorBuilder::message(
                        \sprintf(
                            'Weak cryptographic key size (%d bits). Use at least 2048 bits for RSA keys.',
                            $item->value->value,
                        ),
                    )
                        ->identifier('shopware.forbidWeakCryptoKey')
                        ->line($node->getLine())
                        ->build(),
                ];
            }
        }

        return [];
    }
}
