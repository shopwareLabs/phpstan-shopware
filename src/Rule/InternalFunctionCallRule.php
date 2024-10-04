<?php

// Code taken from https://github.com/TemirkhanN/phpstan-internal-rule

declare(strict_types=1);

namespace Shopware\PhpStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use Shopware\PhpStan\Helper\NamespaceChecker;

class InternalFunctionCallRule implements Rule
{
    private ReflectionProvider $reflectionProvider;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
    }

    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        assert($node instanceof FuncCall);

        if (!$node->name instanceof Node\Name) {
            return [];
        }

        $function = $this->reflectionProvider->getFunction($node->name, null);
        if (!$function->isInternal()->yes()) {
            return [];
        }

        $functionNamespace = NamespaceChecker::getNamespace($function->getName());
        if (false === str_starts_with($functionNamespace, 'Shopware\\')) {
            return [];
        }
        if (NamespaceChecker::arePartOfTheSamePackage($functionNamespace, $scope->getNamespace())) {
            return [];
        }

        return [
            sprintf('Call of internal function %s Please refrain from using functions which are annotated with @internal in the Shopware 6 repository.', $function->getName()),
        ];
    }
}
