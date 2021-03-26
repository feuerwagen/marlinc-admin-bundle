<?php

namespace Marlinc\AdminBundle\Builder;

use Sonata\AdminBundle\Builder\ListBuilderInterface;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionCollection;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\FieldDescription\TypeGuesserInterface;
use Sonata\DoctrineORMAdminBundle\Builder\ListBuilder as SonataListBuilder;

final class ListBuilder implements ListBuilderInterface
{
    private $decorated;

    /**
     * @var TypeGuesserInterface
     */
    private $guesser;

    public function __construct( SonataListBuilder $decorated,TypeGuesserInterface $guesser)
    {
        $this->guesser = $guesser;
        $this->decorated = $decorated;
    }

    public function getBaseList(array $options = []): FieldDescriptionCollection
    {
        return new FieldDescriptionCollection();
    }

    public function buildField(?string $type, FieldDescriptionInterface $fieldDescription): void
    {
        if (null == $type) {
            $guessType = $guessType = $this->guesser->guess($fieldDescription);
            $fieldDescription->setType($guessType->getType() ? $guessType->getType() : '_action');
            $fieldDescription->setOptions(array_merge($guessType->getOptions(), $fieldDescription->getOptions()));
        } else {
            $fieldDescription->setType($type);
        }

        $this->fixFieldDescription($admin, $fieldDescription);
    }

    public function addField(FieldDescriptionCollection $list, ?string $type, FieldDescriptionInterface $fieldDescription): void {
        $this->decorated->addField($list,$type,$fieldDescription);
    }

    public function fixFieldDescription(FieldDescriptionInterface $fieldDescription): void
    {
        $this->decorated->fixFieldDescription($fieldDescription);
    }
}
