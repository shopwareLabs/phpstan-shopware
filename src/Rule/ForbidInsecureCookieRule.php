<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<FuncCall>
 */
class ForbidInsecureCookieRule implements Rule
{
    // setcookie($name, $value, $expires, $path, $domain, $secure): legacy signature
    private const SECURE_PARAM_INDEX = 5;

    // setcookie($name, $value, $options): array options signature
    private const OPTIONS_PARAM_INDEX = 2;

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

        $functionName = $node->name->toLowerString();
        if ($functionName !== 'setcookie' && $functionName !== 'setrawcookie') {
            return [];
        }

        $args = $node->getArgs();

        // Array options signature: setcookie($name, $value, ['secure' => true, ...])
        if (isset($args[self::OPTIONS_PARAM_INDEX]) && $args[self::OPTIONS_PARAM_INDEX]->value instanceof Array_) {
            return $this->checkArrayOptions($node, $args[self::OPTIONS_PARAM_INDEX]->value);
        }

        // Legacy signature: secure flag is the 6th argument (index 5)
        if (!isset($args[self::SECURE_PARAM_INDEX])) {
            return $this->buildError($node);
        }

        $secureArg = $args[self::SECURE_PARAM_INDEX]->value;
        if ($secureArg instanceof ConstFetch && $secureArg->name->toLowerString() === 'true') {
            return [];
        }

        return $this->buildError($node);
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function checkArrayOptions(FuncCall $funcCall, Array_ $array): array
    {
        foreach ($array->items as $item) {
            if (!$item->key instanceof String_) {
                continue;
            }

            if ($item->key->value === 'secure' && $item->value instanceof ConstFetch && $item->value->name->toLowerString() === 'true') {
                return [];
            }
        }

        return $this->buildError($funcCall);
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function buildError(FuncCall $node): array
    {
        return [
            RuleErrorBuilder::message('Cookie set without secure flag. Use secure=true for HTTPS-only transmission.')
                ->identifier('shopware.forbidInsecureCookie')
                ->line($node->getLine())
                ->build(),
        ];
    }
}
