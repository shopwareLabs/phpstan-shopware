<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Type\ObjectType;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<MethodCall>
 */
class NoDALFilterByID implements Rule
{
    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Node\Identifier || $node->name->toString() !== 'addFilter') {
            return [];
        }

        if (!(new ObjectType(Criteria::class))->isSuperTypeOf($scope->getType($node->var))->yes()) {
            return [];
        }

        if (!isset($node->args[0]) || !$node->args[0] instanceof Node\Arg || !$node->args[0]->value instanceof Node\Expr\New_) {
            return [];
        }

        return $this->checkFilterNode($node->args[0]->value);
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    private function checkFilterNode(Node\Expr\New_ $node): array
    {
        if (!$node->class instanceof Node\Name) {
            return [];
        }

        if (!in_array($node->class->toString(), [EqualsFilter::class, EqualsAnyFilter::class], true) || $node->args === []) {
            return [];
        }

        if (!isset($node->args[0]) || !$node->args[0] instanceof Node\Arg) {
            return [];
        }

        $firstArgValue = $node->args[0]->value;

        if (!$firstArgValue instanceof Node\Scalar\String_) {
            return [];
        }

        if (strtolower($firstArgValue->value) === 'id') {
            return [
                RuleErrorBuilder::message('Using "id" directly in EqualsFilter or EqualsAnyFilter is forbidden. Pass the ids directly to the constructor of Criteria or use setIds instead')
                    ->line($node->getLine())
                    ->identifier('shopware.dal.filterById')
                    ->build(),
            ];
        }

        return [];
    }
}
