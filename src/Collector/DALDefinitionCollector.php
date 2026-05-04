<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Collector;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\Cast\String_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ObjectType;
use PHPStan\Type\TypeTraverser;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\AutoIncrementField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BreadcrumbField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ChildCountField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ChildrenAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedByField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Computed;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Runtime;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LockedField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ParentAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ParentFkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedByField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\VersionField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\CustomEntity\Schema\DynamicMappingEntityDefinition;

/**
 * @phpstan-type EntityFields array<string, array{required: bool, computed: bool, runtime: bool}>
 * @implements Collector<InClassNode, array{name: string, entity: string|null, fields: EntityFields}>
 */
class DALDefinitionCollector implements Collector
{
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {}

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * @param InClassNode $node
     * @return array{name: string, entity: string|null, fields: EntityFields}|null
     */
    public function processNode(Node $node, Scope $scope): ?array
    {
        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return null;
        }

        if (!$classReflection->isSubclassOf(EntityDefinition::class)) {
            return null;
        }

        if ($classReflection->is(DynamicMappingEntityDefinition::class)) {
            return null;
        }

        $entityName = $this->getEntityName($node, $scope);
        if ($entityName === '') {
            return null;
        }

        return [
            'name' => $entityName,
            'entity' => $this->getEntityClass($node, $scope),
            'fields' => $this->getFields($node, $scope),
        ];
    }

    private function getFieldName(New_ $newInstance, Scope $scope): string
    {
        if (!$newInstance->class instanceof Name) {
            throw new \RuntimeException('Expected class name');
        }

        $className = $newInstance->class->toString();
        $class = $this->reflectionProvider->getClass($className);

        if ($class->is(ChildCountField::class)) {
            return 'childCount';
        }
        if ($class->is(AutoIncrementField::class)) {
            return 'autoIncrement';
        }
        if ($class->is(VersionField::class)) {
            return 'versionId';
        }
        if ($class->is(ParentFkField::class)) {
            return 'parentId';
        }
        if ($class->is(ParentAssociationField::class)) {
            return 'parent';
        }
        if ($class->is(TranslationsAssociationField::class)) {
            return 'translations';
        }
        if ($class->is(LockedField::class)) {
            return 'locked';
        }
        if ($class->is(CustomFields::class)) {
            return 'customFields';
        }
        if ($class->is(CreatedAtField::class)) {
            return 'createdAt';
        }
        if ($class->is(UpdatedAtField::class)) {
            return 'updatedAt';
        }
        if ($class->is(CreatedByField::class)) {
            return 'createdById';
        }
        if ($class->is(UpdatedByField::class)) {
            return 'updatedById';
        }
        if ($class->is(BreadcrumbField::class)) {
            return 'breadcrumb';
        }

        $args = $newInstance->getArgs();
        if (empty($args)) {
            throw new \RuntimeException(sprintf("Cannot handle field %s without arguments", $class->getName()));
        }

        if ($class->is(ReferenceVersionField::class) && isset($args[0])) {
            $storageName = null;

            if (isset($args[1]) && $args[1]->value instanceof \PhpParser\Node\Scalar\String_) {
                $storageName = $args[1]->value->value;
            } else {
                $firstArg = $args[0];
                $firstValue = $scope->getType($firstArg->value);
                $constantStrings = $firstValue->getConstantStrings();

                if (!empty($constantStrings)) {
                    $entityName = $constantStrings[0]->getValue();

                    try {
                        /** @var class-string<EntityDefinition> */
                        $className = $entityName;

                        $entityInstance = new $className();
                        $entityName = $entityInstance->getEntityName();
                    } catch (\Throwable) {
                    }

                    $storageName = $entityName . '_version_id';
                }
            }

            if ($storageName !== null) {
                $propertyName = explode('_', $storageName);
                $propertyName = array_map('ucfirst', $propertyName);

                return lcfirst(implode('', $propertyName));
            }

            return 'versionId';
        }

        if (
            ($class->is(TranslatedField::class) ||
                $class->is(OneToManyAssociationField::class) ||
                $class->is(ManyToOneAssociationField::class) ||
                $class->is(OneToOneAssociationField::class) ||
                $class->is(ManyToManyAssociationField::class)) &&
            isset($args[0]) &&
            property_exists($args[0]->value, 'value')
        ) {

            if ($class->is(ChildrenAssociationField::class)) {
                return 'children';
            }

            if ($args[0]->value instanceof \PhpParser\Node\Scalar\String_) {
                return $args[0]->value->value;
            }

            throw new \RuntimeException(sprintf("Cannot handle field %s with argument of type %s and value %s", $class->getName(), get_class($args[0]->value), json_encode($args[0]->value->value)));
        }

        if (isset($args[1]) && $args[1]->value instanceof \PhpParser\Node\Scalar\String_) {
            return (string) $args[1]->value->value;
        }

        throw new \RuntimeException(sprintf("Cannot handle field %s with arguments %s", $class->getName(), json_encode($args)));
    }

    /**
     * @param EntityFields $fields
     */
    private function handleField(array &$fields, MethodCall|New_ $methodCall, Scope $scope): void
    {
        if ($methodCall instanceof MethodCall) {
            $flags = ['required' => false, 'computed' => false, 'runtime' => false];
            $currentMethodCall = $methodCall;

            while ($currentMethodCall->var instanceof MethodCall) {
                if ($currentMethodCall->name instanceof Identifier && (string) $currentMethodCall->name === 'addFlag') {
                    foreach ($currentMethodCall->getArgs() as $arg) {
                        if ($arg->value instanceof New_ && $arg->value->class instanceof Name) {
                            $className = $arg->value->class->toString();
                            if ($className === Required::class) {
                                $flags['required'] = true;
                            }
                            if ($className === Runtime::class) {
                                $flags['runtime'] = true;
                            }
                            if ($className === Computed::class) {
                                $flags['computed'] = true;
                            }
                        }
                    }
                }
                $currentMethodCall = $currentMethodCall->var;
            }

            if ($currentMethodCall->var instanceof New_) {
                $fieldName = $this->getFieldName($currentMethodCall->var, $scope);
                $fields[$fieldName] = $flags;
            }
        }

        if ($methodCall instanceof New_) {
            $fieldName = $this->getFieldName($methodCall, $scope);
            $fields[$fieldName] = ['required' => false, 'computed' => false, 'runtime' => false];
        }
    }

    /**
     * @return EntityFields
     */
    private function getFields(InClassNode $node, Scope $scope): array
    {
        $fields = [];
        $nodeFinder = new NodeFinder();

        foreach ($node->getOriginalNode()->stmts as $method) {
            if ($method instanceof ClassMethod && $method->name->name === 'defineFields') {
                if ($method->stmts === null) {
                    continue;
                }

                /** @var New_|null $fieldCollection */
                $fieldCollection = $nodeFinder->findFirst($method->stmts, function (Node $node): bool {
                    return $node instanceof New_ &&
                        $node->class instanceof FullyQualified &&
                        $node->class->toString() === FieldCollection::class;
                });

                if ($fieldCollection instanceof New_ && count($fieldCollection->getArgs())) {
                    $arrayArgument = $fieldCollection->getArgs()[0];
                    if ($arrayArgument->value instanceof Array_) {
                        foreach ($arrayArgument->value->items as $item) {
                            if (($item->value instanceof MethodCall || $item->value instanceof New_)) {
                                $this->handleField($fields, $item->value, $scope);
                            }
                        }
                    }
                }

                /** @var array<MethodCall> $calls */
                $calls = $nodeFinder->find($method->stmts, function (Node $node): bool {
                    return $node instanceof MethodCall &&
                        $node->name instanceof Identifier &&
                        $node->name->name === 'add';
                });

                foreach ($calls as $call) {
                    foreach ($call->getArgs() as $arg) {
                        if ($arg->value instanceof MethodCall || $arg->value instanceof New_) {
                            $this->handleField($fields, $arg->value, $scope);
                        }
                    }
                }
            }
        }

        return $fields;
    }

    private function getEntityName(InClassNode $node, Scope $scope): string
    {
        $nodeFinder = new NodeFinder();

        foreach ($node->getOriginalNode()->stmts as $method) {
            if (
                $method instanceof ClassMethod &&
                $method->name->name === 'getEntityName' &&
                $method->stmts !== null
            ) {
                /** @var Return_|null $returnNode */
                $returnNode = $nodeFinder->findFirst($method->stmts, function (Node $node): bool {
                    return $node instanceof Return_ && $node->expr !== null;
                });

                if ($returnNode instanceof Return_ && $returnNode->expr instanceof Expr) {
                    $entityName = $scope->getType($returnNode->expr);
                    $constantStrings = $entityName->getConstantStrings();
                    if (!empty($constantStrings)) {
                        return $constantStrings[0]->getValue();
                    }
                }
            }
        }

        return '';
    }

    private function getEntityClass(InClassNode $node, Scope $scope): string
    {
        $nodeFinder = new NodeFinder();

        foreach ($node->getOriginalNode()->stmts as $method) {
            if (
                $method instanceof ClassMethod &&
                $method->name->name === 'getEntityClass' &&
                $method->stmts !== null
            ) {

                /** @var Return_|null $returnNode */
                $returnNode = $nodeFinder->findFirst($method->stmts, function (Node $node): bool {
                    return $node instanceof Return_ && $node->expr !== null;
                });

                if ($returnNode instanceof Return_ && $returnNode->expr instanceof Expr) {
                    $entityName = $scope->getType($returnNode->expr);
                    $constantStrings = $entityName->getConstantStrings();
                    if (!empty($constantStrings)) {
                        return $constantStrings[0]->getValue();
                    }
                }
            }
        }

        return '';
    }
}
