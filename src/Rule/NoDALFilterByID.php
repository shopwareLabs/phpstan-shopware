<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;

/**
 * @implements Rule<New_>
 */
class NoDALFilterByID implements Rule
{
    public function getNodeType(): string
    {
        return New_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->class instanceof Node\Name) {
            return [];
        }

        $className = $node->class->toString();

        if (!in_array($className, [EqualsFilter::class, EqualsAnyFilter::class], true)) {
            return [];
        }

        if (empty($node->args)) {
            return [];
        }

        if (!$node->args[0] instanceof Node\Arg) {
            return [];
        }

        $firstArg = $node->args[0]->value;

        if (!$firstArg instanceof Node\Scalar\String_) {
            return [];
        }

        if (strtolower($firstArg->value) === 'id') {
            // Allow when used inside a MultiFilter
            if ($this->isInsideMultiFilter($node, $scope)) {
                return [];
            }

            return [
                RuleErrorBuilder::message('Using "id" directly in EqualsFilter or EqualsAnyFilter is forbidden. Pass the ids directly to the constructor of Criteria or use setIds instead')
                    ->line($node->getLine())
                    ->identifier('shopware.dal.filterById')
                    ->build(),
            ];
        }

        return [];
    }
    
    /**
     * Determine if this node is inside a MultiFilter array argument
     * 
     * This method checks if the filter is being created in a context where
     * it's part of an array passed to a MultiFilter constructor.
     */
    private function isInsideMultiFilter(Node $node, Scope $scope): bool
    {
        $file = $scope->getFile();
        $fileContents = file_get_contents($file);
        
        // Get positions for this node
        $nodeStartPos = $node->getStartFilePos();
        $nodeEndPos = $node->getEndFilePos();
        
        // Context before this node
        $contextBefore = substr($fileContents, 0, $nodeStartPos);
        
        // Find MultiFilter constructor call
        $multiFilterPos = strrpos($contextBefore, 'new MultiFilter');
        if ($multiFilterPos === false) {
            return false;
        }
        
        // Find array opening that contains our filter
        $arrayStartPos = strrpos($contextBefore, '[', $multiFilterPos);
        if ($arrayStartPos === false) {
            return false;
        }
        
        // Context after this node
        $contextAfter = substr($fileContents, $nodeEndPos);
        
        // Find array closing
        $arrayEndPos = strpos($contextAfter, ']');
        if ($arrayEndPos === false) {
            return false;
        }
        
        // Now check if there's a MultiFilter::CONNECTION_ constant between multiFilterPos and arrayStartPos
        $connectionPart = substr($fileContents, $multiFilterPos, $arrayStartPos - $multiFilterPos);
        if (strpos($connectionPart, 'MultiFilter::CONNECTION_') === false) {
            return false;
        }
        
        // All checks passed - we're inside a MultiFilter array argument
        return true;
    }
}