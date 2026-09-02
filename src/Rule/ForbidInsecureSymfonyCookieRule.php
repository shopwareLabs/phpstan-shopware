<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * @implements Rule<InClassMethodNode>
 */
class ForbidInsecureSymfonyCookieRule implements Rule
{
    private const SYMFONY_COOKIE_CLASS = Cookie::class;

    private const SECURE_PARAM_INDEX = 5;

    public function getNodeType(): string
    {
        return InClassMethodNode::class;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $finder = new NodeFinder();
        $methodCalls = $finder->findInstanceOf($node->getOriginalNode(), MethodCall::class);
        $secureFluentCookies = [];

        foreach ($methodCalls as $methodCall) {
            if (($methodCall->var instanceof New_ || $methodCall->var instanceof StaticCall) && $this->isEnabledWithSecureCall($methodCall)) {
                $secureFluentCookies[spl_object_id($methodCall->var)] = true;
            }
        }

        $errors = [];
        foreach ($finder->findInstanceOf($node->getOriginalNode(), Expr::class) as $expression) {
            if ($expression instanceof New_ && !isset($secureFluentCookies[spl_object_id($expression)])) {
                $errors = [...$errors, ...$this->processNew($expression, $scope)];
            }

            if ($expression instanceof StaticCall && !isset($secureFluentCookies[spl_object_id($expression)])) {
                $errors = [...$errors, ...$this->processStaticCall($expression, $scope)];
            }

            if ($expression instanceof MethodCall) {
                $errors = [...$errors, ...$this->processMethodCall($expression, $scope, $node)];
            }
        }

        return $errors;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function processNew(New_ $node, Scope $scope): array
    {
        if (!$node->class instanceof Name) {
            return [];
        }

        if (!$this->isSymfonyCookie($node, $scope)) {
            return [];
        }

        return $this->checkSecureParam($node->getArgs(), $node);
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function processStaticCall(StaticCall $node, Scope $scope): array
    {
        if (!$node->class instanceof Name) {
            return [];
        }

        if (!$this->isSymfonyCookie($node, $scope)) {
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
    private function processMethodCall(MethodCall $node, Scope $scope, InClassMethodNode $method): array
    {
        if (!$node->name instanceof Identifier) {
            return [];
        }

        if ($node->name->name !== 'withSecure') {
            return [];
        }

        if (!$this->isSymfonyCookieMethodCall($node, $scope, $method)) {
            return [];
        }

        $args = $node->getArgs();

        if (($node->var instanceof New_ || $node->var instanceof StaticCall) && !$this->hasEnabledSecureParam($node->var->getArgs())) {
            return [];
        }

        // withSecure() with no args defaults to true
        if (!isset($args[0])) {
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
        $secureArg = $this->findSecureArg($args);

        if ($secureArg !== null && $secureArg->value instanceof ConstFetch && $secureArg->value->name->toLowerString() === 'true') {
            return [];
        }

        return $this->buildError($node);
    }

    /**
     * @param array<Arg> $args
     */
    private function findSecureArg(array $args): ?Arg
    {
        $hasNamedArgs = false;

        foreach ($args as $arg) {
            if ($arg->name !== null) {
                $hasNamedArgs = true;

                if ($arg->name->name === 'secure') {
                    return $arg;
                }
            }
        }

        if ($hasNamedArgs) {
            // Named args are used but 'secure' was not among them
            return null;
        }

        // No named args at all – use positional lookup
        if (!isset($args[self::SECURE_PARAM_INDEX])) {
            return null;
        }

        return $args[self::SECURE_PARAM_INDEX];
    }

    /**
     * @param array<Arg> $args
     */
    private function hasEnabledSecureParam(array $args): bool
    {
        $secureArg = $this->findSecureArg($args);

        return $secureArg !== null
            && $secureArg->value instanceof ConstFetch
            && $secureArg->value->name->toLowerString() === 'true';
    }

    private function isEnabledWithSecureCall(MethodCall $node): bool
    {
        if (!$node->name instanceof Identifier || $node->name->name !== 'withSecure') {
            return false;
        }

        $args = $node->getArgs();
        if ($args === []) {
            return true;
        }

        $secureArg = $args[0];

        return $secureArg->value instanceof ConstFetch && $secureArg->value->name->toLowerString() === 'true';
    }

    private function isSymfonyCookieMethodCall(MethodCall $node, Scope $scope, InClassMethodNode $method): bool
    {
        $cookieType = new ObjectType(self::SYMFONY_COOKIE_CLASS);
        if ($cookieType->isSuperTypeOf($scope->getType($node->var))->yes()) {
            return true;
        }

        if (!$node->var instanceof Variable || !is_string($node->var->name)) {
            return false;
        }

        $assignments = (new NodeFinder())->findInstanceOf($method->getOriginalNode(), Assign::class);
        foreach (array_reverse($assignments) as $assignment) {
            if ($assignment->getStartFilePos() >= $node->getStartFilePos()
                || !$assignment->var instanceof Variable
                || $assignment->var->name !== $node->var->name
            ) {
                continue;
            }

            return ($assignment->expr instanceof New_ || $assignment->expr instanceof StaticCall)
                && $this->isSymfonyCookie($assignment->expr, $scope);
        }

        return false;
    }

    private function isSymfonyCookie(Expr $node, Scope $scope): bool
    {
        return (new ObjectType(self::SYMFONY_COOKIE_CLASS))->isSuperTypeOf($scope->getType($node))->yes();
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
