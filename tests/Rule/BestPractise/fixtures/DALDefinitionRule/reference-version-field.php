<?php

declare(strict_types=1);

namespace Shopware\Tests\Rule\BestPractise\fixtures\DALDefinitionRule;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ParentFkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\VersionField;

class FooDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 'foo';
    }

    public function getEntityClass(): string
    {
        return FooEntity::class;
    }

    public function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            new VersionField(),

            new ParentFkField(self::class),
            new ReferenceVersionField(self::class, 'parent_version_id'),

            new FkField('foo_id', 'fooId', self::class),
            new ReferenceVersionField(self::class),
        ]);
    }
}

class FooEntity extends Entity
{
    use EntityIdTrait;

    public ?string $parentId = null;

    public ?string $fooId = null;
}
