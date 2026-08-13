<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Rule\FutureCompatibility;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\Reflection\Adapter\FakeReflectionAttribute;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionAttribute;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Type\VerbosityLevel;

/**
 * Reports extension call sites incompatible with a BC-change attribute announced by Core.
 *
 * @implements Rule<CallLike>
 * @internal
 */
final class FutureCallSiteRule implements Rule
{
    private const ATTRIBUTE_NAMESPACE = 'Shopware\\Core\\Framework\\Deprecation\\BCChange\\';

    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
        private readonly AnnouncedTypeResolver $typeResolver,
    ) {}

    public function getNodeType(): string
    {
        return CallLike::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $methodName = $this->methodName($node);
        if ($methodName === null) {
            return [];
        }

        $class = $this->classReflection($node, $scope, $methodName);
        if ($class === null) {
            return [];
        }

        $native = $class->getNativeReflection();
        $errors = $this->classInternalError($native->getAttributes(), $class);
        if (!$native->hasMethod($methodName)) {
            return $errors;
        }

        $method = $native->getMethod($methodName);
        $symbol = sprintf('%s::%s()', $class->getDisplayName(), $methodName);

        foreach ($method->getAttributes() as $attribute) {
            $name = $attribute->getName();
            $arguments = $attribute->getArguments();
            $version = $this->stringArgument($arguments, 'version', 0);

            if ($name === self::ATTRIBUTE_NAMESPACE . 'BecomesInternal') {
                $errors[] = $this->error(sprintf('"%s" will become internal in %s. Stop calling it to stay compatible.', $symbol, $version));
            } elseif ($name === self::ATTRIBUTE_NAMESPACE . 'VisibilityChange') {
                $visibility = $arguments['newVisibility'] ?? $arguments[1] ?? null;
                if (!$this->canAccess($visibility, $class, $scope)) {
                    $errors[] = $this->error(sprintf('"%s" will become %s in %s. This call will break; stop calling it from outside that scope.', $symbol, is_string($visibility) ? $visibility : '?', $version));
                }
            } elseif ($name === self::ATTRIBUTE_NAMESPACE . 'ParameterRemoval') {
                $parameter = $arguments['parameterName'] ?? $arguments[1] ?? null;
                if (is_string($parameter) && $this->argument($node, $method, $parameter) !== null) {
                    $errors[] = $this->error(sprintf('Parameter $%s of "%s" will be removed in %s. Stop passing it to stay compatible with both versions.', $parameter, $symbol, $version));
                }
            } elseif ($name === self::ATTRIBUTE_NAMESPACE . 'ParameterNameChange') {
                $parameter = $arguments['parameterName'] ?? $arguments[1] ?? null;
                foreach ($node->getArgs() as $argument) {
                    if ($argument->name !== null && $argument->name->toString() === $parameter) {
                        $newName = $this->stringArgument($arguments, 'newName', 2);
                        $errors[] = $this->error(sprintf('Parameter $%s of "%s" will be renamed to $%s in %s. A named argument cannot be compatible with both versions; pass it positionally.', $parameter, $symbol, $newName, $version));
                    }
                }
            } elseif ($name === self::ATTRIBUTE_NAMESPACE . 'NewRequiredParameter') {
                if (!$this->hasUnpack($node) && count($node->getArgs()) <= count($method->getParameters())) {
                    $parameter = $this->stringArgument($arguments, 'parameterName', 1);
                    $errors[] = $this->error(sprintf('"%s" will require a new parameter $%s in %s. Pass it positionally now to stay compatible with both versions.', $symbol, $parameter, $version));
                }
            } elseif ($name === self::ATTRIBUTE_NAMESPACE . 'ParameterDefaultValueChange') {
                $parameter = $arguments['parameterName'] ?? $arguments[1] ?? null;
                if (is_string($parameter) && $this->argument($node, $method, $parameter) === null) {
                    $errors[] = $this->error(sprintf('The default value of parameter $%s of "%s" will change in %s. Pass the current default explicitly to retain current behavior.', $parameter, $symbol, $version));
                }
            } elseif ($name === self::ATTRIBUTE_NAMESPACE . 'ParameterTypeNarrowing') {
                $parameter = $arguments['parameterName'] ?? $arguments[1] ?? null;
                $newType = $arguments['newType'] ?? $arguments[2] ?? null;
                $argument = is_string($parameter) ? $this->argument($node, $method, $parameter) : null;
                $announced = is_string($newType) ? $this->typeResolver->resolve($newType, $method->getDeclaringClass()->getName()) : null;
                if ($argument !== null && $announced !== null) {
                    $actual = $scope->getType($argument->value);
                    if ($announced->isSuperTypeOf($actual)->no()) {
                        $errors[] = $this->error(sprintf('Parameter $%s of "%s" will be narrowed to %s in %s, but %s is passed. Pass %s to stay compatible with both versions.', $parameter, $symbol, $newType, $version, $actual->describe(VerbosityLevel::typeOnly()), $newType));
                    }
                }
            }
        }

        return $errors;
    }

    private function methodName(CallLike $node): ?string
    {
        if (($node instanceof MethodCall || $node instanceof StaticCall) && $node->name instanceof Identifier) {
            return $node->name->toString();
        }

        return $node instanceof New_ ? '__construct' : null;
    }

    private function classReflection(CallLike $node, Scope $scope, string $method): ?ClassReflection
    {
        if ($node instanceof MethodCall) {
            foreach ($scope->getType($node->var)->getObjectClassReflections() as $class) {
                if ($class->hasNativeMethod($method)) {
                    return $class;
                }
            }

            return null;
        }
        if (($node instanceof StaticCall || $node instanceof New_) && $node->class instanceof Name) {
            $className = $scope->resolveName($node->class);

            return $this->reflectionProvider->hasClass($className) ? $this->reflectionProvider->getClass($className) : null;
        }

        return null;
    }

    /**
     * @param list<ReflectionAttribute|FakeReflectionAttribute> $attributes
     * @return list<IdentifierRuleError>
     */
    private function classInternalError(array $attributes, ClassReflection $class): array
    {
        foreach ($attributes as $attribute) {
            if ($attribute->getName() === self::ATTRIBUTE_NAMESPACE . 'BecomesInternal') {
                $arguments = $attribute->getArguments();

                return [$this->error(sprintf('Class "%s" will become internal in %s. Stop using it to stay compatible.', $class->getDisplayName(), $this->stringArgument($arguments, 'version', 0)))];
            }
        }

        return [];
    }

    private function canAccess(mixed $visibility, ClassReflection $class, Scope $scope): bool
    {
        $caller = $scope->isInClass() ? $scope->getClassReflection() : null;

        return match ($visibility) {
            'protected' => $caller !== null && ($caller->getName() === $class->getName() || $caller->isSubclassOfClass($class)),
            'private' => $caller !== null && $caller->getName() === $class->getName(),
            default => true,
        };
    }

    private function argument(CallLike $node, \ReflectionMethod $method, string $parameter): ?Arg
    {
        $position = null;
        foreach ($method->getParameters() as $candidate) {
            if ($candidate->getName() === $parameter) {
                $position = $candidate->getPosition();
                break;
            }
        }
        foreach ($node->getArgs() as $index => $argument) {
            if ($argument->unpack) {
                return null;
            }
            if ($argument->name !== null ? $argument->name->toString() === $parameter : $index === $position) {
                return $argument;
            }
        }

        return null;
    }

    private function hasUnpack(CallLike $node): bool
    {
        foreach ($node->getArgs() as $argument) {
            if ($argument->unpack) {
                return true;
            }
        }

        return false;
    }

    /** @param array<int|string, mixed> $arguments */
    private function stringArgument(array $arguments, string $name, int $position): string
    {
        $value = $arguments[$name] ?? $arguments[$position] ?? '?';

        return is_string($value) ? $value : '?';
    }

    private function error(string $message): IdentifierRuleError
    {
        return RuleErrorBuilder::message($message)->identifier('shopware.futureIncompatibility')->build();
    }
}
