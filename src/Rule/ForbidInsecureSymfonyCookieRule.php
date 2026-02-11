<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * @implements Rule<Expr>
 */
class ForbidInsecureSymfonyCookieRule implements Rule
{
    private const SYMFONY_COOKIE_CLASS = Cookie::class;

    private const SECURE_PARAM_INDEX = 5;

    public function getNodeType(): string
    {
        return Expr::class;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node instanceof New_) {
            return $this->processNew($node);
        }

        if ($node instanceof StaticCall) {
            return $this->processStaticCall($node);
        }

        if ($node instanceof MethodCall) {
            return $this->processMethodCall($node, $scope);
        }

        return [];
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function processNew(New_ $node): array
    {
        if (!$node->class instanceof Name) {
            return [];
        }

        if ($node->class->toString() !== self::SYMFONY_COOKIE_CLASS) {
            return [];
        }

        return $this->checkSecureParam($node->getArgs(), $node);
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function processStaticCall(StaticCall $node): array
    {
        if (!$node->class instanceof Name) {
            return [];
        }

        if ($node->class->toString() !== self::SYMFONY_COOKIE_CLASS) {
            return [];
        }

        if (!$node->name instanceof Identifier) {
            return [];
        }

        if ($node->name->name !== 'create') {
            return [];
        }

        return $this->checkSecureParam($node->getArgs(), $node);
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function processMethodCall(MethodCall $node, Scope $scope): array
    {
        if (!$node->name instanceof Identifier) {
            return [];
        }

        if ($node->name->name !== 'withSecure') {
            return [];
        }

        $calledOnType = $scope->getType($node->var);
        $cookieType = new ObjectType(self::SYMFONY_COOKIE_CLASS);
        if (!$cookieType->isSuperTypeOf($calledOnType)->yes()) {
            return [];
        }

        $args = $node->getArgs();

        // withSecure() with no args defaults to true
        if (\count($args) === 0) {
            return [];
        }

        $secureArg = $args[0]->value;
        if ($secureArg instanceof ConstFetch && $secureArg->name->toLowerString() === 'true') {
            return [];
        }

        return $this->buildError($node);
    }

    /**
     * @param array<Arg> $args
     *
     * @return list<IdentifierRuleError>
     */
    private function checkSecureParam(array $args, Node $node): array
    {
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
    private function buildError(Node $node): array
    {
        return [
            RuleErrorBuilder::message('Symfony Cookie created without explicit secure flag. Use the $secure parameter set to true for HTTPS-only transmission.')
                ->identifier('shopware.forbidInsecureSymfonyCookie')
                ->line($node->getLine())
                ->build(),
        ];
    }
}
