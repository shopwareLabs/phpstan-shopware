<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\New_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
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
     * @return list<RuleError>
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

        $parameters = $this->getConstructorParameters($className);
        if ($parameters === null) {
            return [];
        }

        $bodyParameter = $this->findParameterByNames($parameters, self::BODY_PARAMETER_NAMES);
        if ($bodyParameter === null) {
            return [];
        }

        $bodyArg = $this->findArgumentForParameter($node, $bodyParameter['index'], $bodyParameter['name']);
        if (!$this->isEmptyBody($bodyArg, $bodyParameter['parameter'], $scope)) {
            return [];
        }

        $statusParameter = $this->findParameterByNames($parameters, self::STATUS_PARAMETER_NAMES);
        $statusArg = $statusParameter === null ? null : $this->findArgumentForParameter($node, $statusParameter['index'], $statusParameter['name']);

        if ($statusArg !== null && $this->isAllowedEmptyStatusCode($statusArg->value, $scope)) {
            return [];
        }

        return $this->buildError($node);
    }

    /**
     * @return list<ParameterReflection>|null
     */
    private function getConstructorParameters(string $className): ?array
    {
        if (!$this->reflectionProvider->hasClass($className)) {
            return null;
        }

        $classReflection = $this->reflectionProvider->getClass($className);

        if (!$classReflection->hasConstructor()) {
            return null;
        }

        return $classReflection->getConstructor()->getOnlyVariant()->getParameters();
    }

    /**
     * @param list<ParameterReflection> $parameters
     * @param list<string> $names
     *
     * @return array{index: int, name: string, parameter: ParameterReflection}|null
     */
    private function findParameterByNames(array $parameters, array $names): ?array
    {
        foreach ($parameters as $index => $parameter) {
            if (in_array($parameter->getName(), $names, true)) {
                return [
                    'index' => $index,
                    'name' => $parameter->getName(),
                    'parameter' => $parameter,
                ];
            }
        }

        return null;
    }

    private function isEmptyBody(?Arg $bodyArg, ParameterReflection $bodyParameter, Scope $scope): bool
    {
        if ($bodyArg === null) {
            $defaultValue = $bodyParameter->getDefaultValue();

            return $defaultValue !== null && $this->isEmptyStringType($defaultValue);
        }

        $type = $scope->getType($bodyArg->value);

        if ($this->isEmptyStringType($type)) {
            return true;
        }

        return $type->isNull()->yes();
    }

    private function findArgumentForParameter(New_ $node, int $parameterIndex, string $parameterName): ?Arg
    {
        $positionalIndex = 0;

        foreach ($node->getArgs() as $arg) {
            if ($arg->name !== null) {
                if ($arg->name->toString() === $parameterName) {
                    return $arg;
                }

                continue;
            }

            if ($arg->unpack) {
                return null;
            }

            if ($positionalIndex === $parameterIndex) {
                return $arg;
            }

            ++$positionalIndex;
        }

        return null;
    }

    private function isEmptyStringType(Type $type): bool
    {
        $constantStrings = $type->getConstantStrings();

        return \count($constantStrings) === 1 && $constantStrings[0]->getValue() === '';
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
