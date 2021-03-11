<?php

namespace Marlinc\AdminBundle\Builder;

use Doctrine\ORM\Mapping\ClassMetadata;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Admin\FieldDescriptionCollection;
use Sonata\AdminBundle\Admin\FieldDescriptionInterface;
use Sonata\AdminBundle\Guesser\TypeGuesserInterface;
use Sonata\AdminBundle\Builder\ListBuilderInterface;

final class ListBuilder implements ListBuilderInterface
{

    /**
     * @var TypeGuesserInterface
     */
    private $guesser;

    /**
     * @param string[] $templates
     */
    public function __construct(TypeGuesserInterface $guesser)
    {
        $this->guesser = $guesser;
    }

    public function getBaseList(array $options = []): FieldDescriptionCollection
    {
        return new FieldDescriptionCollection();
    }

    public function buildField(?string $type, FieldDescriptionInterface $fieldDescription, AdminInterface $admin): void
    {
        if (null == $type) {
            $guessType = $this->guesser->guessType(
                $admin->getClass(),
                $fieldDescription->getName(),
                $admin->getModelManager()
            );
            $fieldDescription->setType($guessType->getType() ? $guessType->getType() : '_action');
            $fieldDescription->setOptions(array_merge($guessType->getOptions(), $fieldDescription->getOptions()));
        } else {
            $fieldDescription->setType($type);
        }

        $this->fixFieldDescription($admin, $fieldDescription);
    }

    public function addField(FieldDescriptionCollection $list, ?string $type, FieldDescriptionInterface $fieldDescription, AdminInterface $admin): void
    {
        $this->buildField($type, $fieldDescription, $admin);
        $admin->addListFieldDescription($fieldDescription->getName(), $fieldDescription);

        $list->add($fieldDescription);
    }

    public function fixFieldDescription(AdminInterface $admin, FieldDescriptionInterface $fieldDescription): void
    {
        if ('_action' === $fieldDescription->getName() || 'actions' === $fieldDescription->getType()) {
            $this->buildActionFieldDescription($fieldDescription);
        }

        $fieldDescription->setAdmin($admin);

        if ([] !== $fieldDescription->getFieldMapping()) {
            if (false !== $fieldDescription->getOption('sortable')) {
                $fieldDescription->setOption('sortable', $fieldDescription->getOption('sortable', true));
                $fieldDescription->setOption('sort_parent_association_mappings', $fieldDescription->getOption('sort_parent_association_mappings', $fieldDescription->getParentAssociationMappings()));
                $fieldDescription->setOption('sort_field_mapping', $fieldDescription->getOption('sort_field_mapping', $fieldDescription->getFieldMapping()));
            }

            $fieldDescription->setOption('_sort_order', $fieldDescription->getOption('_sort_order', 'ASC'));
        }

        if (!$fieldDescription->getType()) {
            throw new \RuntimeException(sprintf(
                'Please define a type for field `%s` in `%s`',
                $fieldDescription->getName(),
                \get_class($admin)
            ));
        }

        if (!$fieldDescription->getTemplate()) {
            $fieldDescription->setTemplate($this->getTemplate($fieldDescription->getType()));

            if (!$fieldDescription->getTemplate()) {
                switch ($fieldDescription->getMappingType()) {
                    case ClassMetadata::MANY_TO_ONE:
                        $fieldDescription->setTemplate(
                            '@SonataAdmin/CRUD/Association/list_many_to_one.html.twig'
                        );

                        break;
                    case ClassMetadata::ONE_TO_ONE:
                        $fieldDescription->setTemplate(
                            '@SonataAdmin/CRUD/Association/list_one_to_one.html.twig'
                        );

                        break;
                    case ClassMetadata::ONE_TO_MANY:
                        $fieldDescription->setTemplate(
                            '@SonataAdmin/CRUD/Association/list_one_to_many.html.twig'
                        );

                        break;
                    case ClassMetadata::MANY_TO_MANY:
                        $fieldDescription->setTemplate(
                            '@SonataAdmin/CRUD/Association/list_many_to_many.html.twig'
                        );

                        break;
                }
            }
        }

        if (\in_array($fieldDescription->getMappingType(), [
            ClassMetadata::MANY_TO_ONE,
            ClassMetadata::ONE_TO_ONE,
            ClassMetadata::ONE_TO_MANY,
            ClassMetadata::MANY_TO_MANY,
        ], true)) {
            $admin->attachAdminClass($fieldDescription);
        }
    }

    public function buildActionFieldDescription(FieldDescriptionInterface $fieldDescription): FieldDescriptionInterface
    {
        if (null === $fieldDescription->getTemplate()) {
            $fieldDescription->setTemplate('@SonataAdmin/CRUD/list__action.html.twig');
        }

        if (\in_array($fieldDescription->getType(), [null, '_action'], true)) {
            $fieldDescription->setType('actions');
        }

        if (null !== $fieldDescription->getOption('actions')) {
            $actions = $fieldDescription->getOption('actions');
            foreach ($actions as $k => $action) {
                if (!isset($action['template'])) {
                    $actions[$k]['template'] = sprintf('@SonataAdmin/CRUD/list__action_%s.html.twig', $k);
                }
            }

            $fieldDescription->setOption('actions', $actions);
        }

        return $fieldDescription;
    }

    private function getTemplate(string $type): ?string
    {
        if (!isset($this->templates[$type])) {
            return null;
        }

        return $this->templates[$type];
    }

}
