<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Expr>
 */
class ForbidEmptyDatabasePasswordRule implements Rule
{
    public function getNodeType(): string
    {
        return Expr::class;
    }

    /**
     * @return array<array-key, RuleError|string>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node instanceof New_) {
            return $this->checkNewExpression($node);
        }

        if ($node instanceof FuncCall) {
            return $this->checkFuncCall($node);
        }

        return [];
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function checkNewExpression(New_ $new): array
    {
        if (!$new->class instanceof Node\Name) {
            return [];
        }

        // new PDO($dsn, $user, $password) or new mysqli($host, $user, $password)
        $className = $new->class->toString();
        if ($className !== 'PDO' && $className !== 'mysqli') {
            return [];
        }

        return $this->checkPasswordArg($new->getArgs(), $new->getLine());
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function checkFuncCall(FuncCall $funcCall): array
    {
        if (!$funcCall->name instanceof Node\Name) {
            return [];
        }

        // mysqli_connect($host, $user, $password)
        if ($funcCall->name->toLowerString() !== 'mysqli_connect') {
            return [];
        }

        return $this->checkPasswordArg($funcCall->getArgs(), $funcCall->getLine());
    }

    /**
     * @param array<Arg> $args
     * @return list<IdentifierRuleError>
     */
    private function checkPasswordArg(array $args, int $line): array
    {
        // 3rd argument (index 2) is the password
        if (count($args) < 3) {
            return [];
        }

        $passwordArg = $args[2]->value;
        if ($passwordArg instanceof String_ && $passwordArg->value === '') {
            return [
                RuleErrorBuilder::message('Database connection with empty password. Use secure credentials.')
                    ->identifier('shopware.forbidEmptyDatabasePassword')
                    ->line($line)
                    ->build(),
            ];
        }

        return [];
    }
}
