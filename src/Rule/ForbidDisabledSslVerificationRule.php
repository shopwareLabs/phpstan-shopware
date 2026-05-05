<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<FuncCall>
 */
class ForbidDisabledSslVerificationRule implements Rule
{
    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Node\Name) {
            return [];
        }

        $funcName = $node->name->toLowerString();

        if ($funcName === 'stream_context_create') {
            return $this->checkStreamContext($node);
        }

        if ($funcName === 'curl_setopt') {
            return $this->checkCurlSetopt($node);
        }

        return [];
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function checkStreamContext(FuncCall $node): array
    {
        $args = $node->getArgs();
        if (count($args) < 1 || !$args[0]->value instanceof Array_) {
            return [];
        }

        // stream_context_create(['ssl' => ['verify_peer' => false]])
        foreach ($args[0]->value->items as $item) {
            if (!$item->key instanceof String_ || $item->key->value !== 'ssl' || !$item->value instanceof Array_) {
                continue;
            }

            foreach ($item->value->items as $sslItem) {
                if (!$sslItem->key instanceof String_) {
                    continue;
                }

                if (!\in_array($sslItem->key->value, ['verify_peer', 'verify_peer_name'], true)) {
                    continue;
                }

                if ($this->isDisabledVerificationValue($sslItem->value)) {
                    return $this->buildError($node);
                }
            }
        }

        return [];
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function checkCurlSetopt(FuncCall $node): array
    {
        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false)
        // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0)
        $args = $node->getArgs();
        if (count($args) < 3) {
            return [];
        }

        $optionArg = $args[1]->value;
        if (!$optionArg instanceof ConstFetch) {
            return [];
        }

        $optionName = $optionArg->name->toString();
        $valueArg = $args[2]->value;

        if ($optionName === 'CURLOPT_SSL_VERIFYPEER' && $this->isDisabledVerificationValue($valueArg)) {
            return $this->buildError($node);
        }

        if ($optionName === 'CURLOPT_SSL_VERIFYHOST' && $this->isDisabledVerifyHostValue($valueArg)) {
            return $this->buildError($node);
        }

        return [];
    }

    private function isDisabledVerificationValue(Expr $value): bool
    {
        if ($value instanceof ConstFetch && $value->name->toLowerString() === 'false') {
            return true;
        }

        return $value instanceof Int_ && $value->value === 0;
    }

    private function isDisabledVerifyHostValue(Expr $value): bool
    {
        if ($this->isDisabledVerificationValue($value)) {
            return true;
        }

        return $value instanceof Int_ && $value->value < 2;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function buildError(FuncCall $node): array
    {
        return [
            RuleErrorBuilder::message('SSL/TLS certificate verification disabled: this allows man-in-the-middle attacks.', )
                ->identifier('shopware.forbidDisabledSslVerification')
                ->line($node->getLine())
                ->build(),
        ];
    }
}
