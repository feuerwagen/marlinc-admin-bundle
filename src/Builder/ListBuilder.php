<?php

namespace Marlinc\AdminBundle\Builder;

use Sonata\AdminBundle\Builder\ListBuilderInterface;
use Sonata\AdminBundle\FieldDescription\TypeGuesserInterface;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionCollection;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\DoctrineORMAdminBundle\Builder\ListBuilder as SonataListBuilder;

final class ListBuilder implements ListBuilderInterface
{
    private SonataListBuilder $decorated;

    private TypeGuesserInterface $guesser;

    public function __construct(SonataListBuilder $decorated, TypeGuesserInterface $guesser)
    {
        $this->guesser = $guesser;
        $this->decorated = $decorated;
    }

    public function getBaseList(array $options = []): FieldDescriptionCollection
    {
        return $this->decorated->getBaseList($options);
    }

    public function buildField(?string $type, FieldDescriptionInterface $fieldDescription): void
    {
        if (null === $type) {
            $guessType = $this->guesser->guess($fieldDescription);
            if (null === $guessType) {
                throw new \InvalidArgumentException(sprintf(
                    'Cannot guess a type for the field description "%s", You MUST provide a type.',
                    $fieldDescription->getName()
                ));
            }

            $fieldDescription->setType($guessType->getType());
            $fieldDescription->setOptions(array_merge($guessType->getOptions(), $fieldDescription->getOptions()));
        } else {
            $fieldDescription->setType($type);
        }

        $this->fixFieldDescription($fieldDescription);
    }

    public function addField(FieldDescriptionCollection $list, ?string $type, FieldDescriptionInterface $fieldDescription): void
    {
        $this->decorated->addField($list, $type, $fieldDescription);
    }

    public function fixFieldDescription(FieldDescriptionInterface $fieldDescription): void
    {
        $this->decorated->fixFieldDescription($fieldDescription);
    }
}
