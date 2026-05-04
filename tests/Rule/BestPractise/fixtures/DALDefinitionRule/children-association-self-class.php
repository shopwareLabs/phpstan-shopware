<?php

declare(strict_types=1);

namespace Shopware\Tests\Rule\BestPractise\fixtures\DALDefinitionRule;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ChildrenAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class TreeDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 'tree';
    }

    public function getEntityClass(): string
    {
        return TreeEntity::class;
    }

    public function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            new ChildrenAssociationField(self::class),
        ]);
    }
}

class TreeEntity extends Entity
{
    use EntityIdTrait;

    public ?TreeCollection $children = null;
}
