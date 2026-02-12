<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Rule;

use PhpParser\Node;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<ArrayItem>
 */
class ForbidHardcodedCredentialsRule implements Rule
{
    private const SENSITIVE_KEYS = ['password', 'api_key', 'secret', 'token', 'apikey'];

    public function getNodeType(): string
    {
        return ArrayItem::class;
    }

    /**
     * @return list<RuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->key instanceof String_) {
            return [];
        }

        $keyName = strtolower($node->key->value);
        $isSensitive = false;
        foreach (self::SENSITIVE_KEYS as $sensitive) {
            if (str_contains($keyName, $sensitive)) {
                $isSensitive = true;
                break;
            }
        }

        if (!$isSensitive) {
            return [];
        }

        if (!$node->value instanceof String_) {
            return [];
        }

        $value = $node->value->value;
        if ($value === '' || $value === '0' || str_starts_with($value, '%') || str_contains($value, 'env(')) {
            return [];
        }

        return [
            RuleErrorBuilder::message('Possible hardcoded credentials detected. Use environment variables.')
                ->identifier('shopware.forbidHardcodedCredentials')
                ->line($node->getLine())
                ->build(),
        ];
    }
}
