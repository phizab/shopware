<?php declare(strict_types=1);

namespace Shopware\Core\System\CustomEntity\Schema;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

/**
 * @internal Used for custom entities
 */
class DynamicMappingEntityDefinition extends EntityDefinition
{
    protected string $name;

    protected array $fieldDefinitions;

    protected string $source;

    protected string $reference;

    public static function create(string $source, string $reference): DynamicMappingEntityDefinition
    {
        $self = new self();

        $parts = [$source, $reference];
        sort($parts);

        $self->name = implode('_', $parts);
        $self->source = $source;
        $self->reference = $reference;

        return $self;
    }

    public function getEntityName(): string
    {
        return $this->name;
    }

    protected function defineFields(): FieldCollection
    {
        $fields = new FieldCollection([
            (new FkField($this->source . '_id', self::kebabCaseToCamelCase($this->source) . 'Id', DynamicEntityDefinition::class, 'id', $this->source))
                ->addFlags(new Required(), new PrimaryKey()),

            (new FkField($this->reference . '_id', self::kebabCaseToCamelCase($this->reference) . 'Id', DynamicEntityDefinition::class, 'id', $this->reference))
                ->addFlags(new Required(), new PrimaryKey()),

            (new ManyToOneAssociationField(self::kebabCaseToCamelCase($this->reference), $this->reference . '_id', DynamicEntityDefinition::class, 'id', false, $this->reference)),
            (new ManyToOneAssociationField(self::kebabCaseToCamelCase($this->source), $this->source . '_id', DynamicEntityDefinition::class, 'id', false, $this->source)),
        ]);

        $definition = $this->registry->getByEntityName($this->source);
        if ($definition->isVersionAware()) {
            $fields->add(
                (new ReferenceVersionField($definition->getClass(), null, $definition->getEntityName()))->addFlags(new PrimaryKey(), new Required()),
            );
        }

        $definition = $this->registry->getByEntityName($this->reference);
        if ($definition->isVersionAware()) {
            $fields->add(
                (new ReferenceVersionField($definition->getClass(), null, $definition->getEntityName()))->addFlags(new PrimaryKey(), new Required()),
            );
        }

        return $fields;
    }

    protected static function kebabCaseToCamelCase(string $string): string
    {
        return (new CamelCaseToSnakeCaseNameConverter())->denormalize(str_replace('-', '_', $string));
    }
}
