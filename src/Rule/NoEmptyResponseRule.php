<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\ObjectType;
use Symfony\Component\HttpFoundation\Response;

/**
 * @implements Rule<New_>
 */
class NoEmptyResponseRule implements Rule
{
    // HTTP status codes that legitimately have no body
    private const ALLOWED_EMPTY_STATUS_CODES = [
        204, // No Content
        301, // Moved Permanently
        302, // Found
        303, // See Other
        304, // Not Modified
        307, // Temporary Redirect
        308, // Permanent Redirect
    ];

    // Constructor parameter names that indicate a response body
    private const BODY_PARAMETER_NAMES = [
        'content',
        'data',
        'body',
        'message',
        'html',
    ];

    // Constructor parameter names that indicate a status code
    private const STATUS_PARAMETER_NAMES = [
        'status',
        'statusCode',
        'code',
    ];

    public function __construct(private readonly ReflectionProvider $reflectionProvider) {}

    public function getNodeType(): string
    {
        return New_::class;
    }

    /**
     * @return array<array-key, RuleError|string>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->class instanceof Node\Name) {
            return [];
        }

        $className = $node->class->toString();
        $responseType = new ObjectType(Response::class);
        $classType = new ObjectType($className);

        if (!$responseType->isSuperTypeOf($classType)->yes()) {
            return [];
        }

        $bodyIndex = $this->resolveBodyParameterIndex($className);
        if ($bodyIndex === null) {
            return [];
        }

        $args = $node->getArgs();

        // new Response() — no arguments at all
        if (count($args) === 0) {
            return $this->buildError($node);
        }

        // Check if the body argument is a blank string
        if (!isset($args[$bodyIndex]) || !$args[$bodyIndex]->value instanceof String_ || $args[$bodyIndex]->value->value !== '') {
            return [];
        }

        // Blank body with a status code that legitimately allows empty responses
        $statusIndex = $this->resolveStatusParameterIndex($className);
        if ($statusIndex !== null && isset($args[$statusIndex]) && $this->isAllowedEmptyStatusCode($args[$statusIndex]->value, $scope)) {
            return [];
        }

        return $this->buildError($node);
    }

    /**
     * Finds the constructor parameter index that represents the response body.
     */
    private function resolveBodyParameterIndex(string $className): ?int
    {
        return $this->findParameterIndexByNames($className, self::BODY_PARAMETER_NAMES);
    }

    /**
     * Finds the constructor parameter index that represents the HTTP status code.
     */
    private function resolveStatusParameterIndex(string $className): ?int
    {
        return $this->findParameterIndexByNames($className, self::STATUS_PARAMETER_NAMES);
    }

    /**
     * @param list<string> $names
     */
    private function findParameterIndexByNames(string $className, array $names): ?int
    {
        if (!$this->reflectionProvider->hasClass($className)) {
            return null;
        }

        $classReflection = $this->reflectionProvider->getClass($className);

        if (!$classReflection->hasConstructor()) {
            return null;
        }

        foreach ($classReflection->getConstructor()->getOnlyVariant()->getParameters() as $index => $parameter) {
            if (in_array($parameter->getName(), $names, true)) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Resolves both integer literals (204) and class constants (Response::HTTP_NO_CONTENT).
     */
    private function isAllowedEmptyStatusCode(Node\Expr $expr, Scope $scope): bool
    {
        $type = $scope->getType($expr);

        if (!$type instanceof ConstantIntegerType) {
            return false;
        }

        return in_array($type->getValue(), self::ALLOWED_EMPTY_STATUS_CODES, true);
    }

    /**
     * @return list<RuleError>
     */
    private function buildError(New_ $node): array
    {
        return [
            RuleErrorBuilder::message('Response with empty body. Return meaningful content or use a status code like 204 (No Content) for intentionally empty responses.')
                ->identifier('shopware.noEmptyResponse')
                ->line($node->getLine())
                ->build(),
        ];
    }
}
