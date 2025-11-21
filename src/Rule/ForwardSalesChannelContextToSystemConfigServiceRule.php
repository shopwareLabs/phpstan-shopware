<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * @implements Rule<MethodCall>
 */
class ForwardSalesChannelContextToSystemConfigServiceRule implements Rule
{
    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @param MethodCall $node
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($this->shouldBeSkipped($node, $scope)) {
            return [];
        }

        $salesChannelContextVarName = null;

        foreach ($scope->getDefinedVariables() as $variableName) {
            $variableType = $scope->getVariableType($variableName);
            if ((new ObjectType(SalesChannelContext::class))->isSuperTypeOf($variableType)->yes()) {
                $salesChannelContextVarName = $variableName;
                break;
            }
        }

        if ($salesChannelContextVarName === null) {
            return [];
        }

        if (!isset($node->getArgs()[1])) {
            return [
                RuleErrorBuilder::message('SystemConfigService methods expects a salesChannelId as second parameter. When a method gets a SalesChannelContext passed and that parameter is not forwarded to SystemConfigService we should throw an phpstan error')
                    ->identifier('shopware.forwardSalesChannelContext')
                    ->build(),
            ];
        }

        $salesChannelId = $node->getArgs()[1]->value;

        if ($salesChannelId instanceof MethodCall
            && $salesChannelId->var instanceof Node\Expr\Variable
            && $salesChannelId->var->name === $salesChannelContextVarName
            && $salesChannelId->name instanceof Identifier
            && $salesChannelId->name->name === 'getSalesChannelId'
        ) {
            return [];
        }

        if ($salesChannelId instanceof MethodCall
            && $salesChannelId->var instanceof MethodCall
            && $salesChannelId->var->var instanceof Node\Expr\Variable
            && $salesChannelId->var->var->name === $salesChannelContextVarName
            && $salesChannelId->var->name instanceof Identifier
            && $salesChannelId->var->name->name === 'getSalesChannel'
            && $salesChannelId->name instanceof Identifier
            && $salesChannelId->name->name === 'getId'
        ) {
            return [];
        }

        if ($salesChannelId instanceof Variable && is_string($salesChannelId->name)) {
            $variableType = $scope->getVariableType($salesChannelId->name);
            if ($variableType->isString()->yes()) {
                return [];
            }
        }

        return [
            RuleErrorBuilder::message('SystemConfigService methods expects a salesChannelId as second parameter. When a method gets a SalesChannelContext passed and that parameter is not forwarded to SystemConfigService we should throw an phpstan error')
                ->identifier('shopware.forwardSalesChannelContext')
                ->build(),
        ];
    }

    private function shouldBeSkipped(MethodCall $node, Scope $scope): bool
    {
        $type = $scope->getType($node->var);

        if (!(new ObjectType(SystemConfigService::class))->isSuperTypeOf($type)->yes()) {
            return true;
        }

        if (!$node->name instanceof Node\Identifier) {
            return true;
        }

        $methodName = $node->name->toString();
        if (!in_array($methodName, ['get', 'getString', 'getInt', 'getFloat', 'getBool'], true)) {
            return true;
        }

        return false;
    }
}
