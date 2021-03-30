<?php

namespace Marlinc\AdminBundle\Builder;

use Sonata\AdminBundle\Builder\ListBuilderInterface;
use Sonata\AdminBundle\Guesser\TypeGuesserInterface;
use Sonata\DoctrineORMAdminBundle\Builder\ListBuilder as SonataListBuilder;
use Sonata\AdminBundle\Admin\FieldDescriptionCollection;
use Sonata\AdminBundle\Admin\FieldDescriptionInterface;
use Sonata\AdminBundle\Admin\AdminInterface;

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

    public function getBaseList(array $options = []):FieldDescriptionCollection
    {
        return $this->decorated->getBaseList($options);
    }

    public function buildField(?string $type,FieldDescriptionInterface $fieldDescription, AdminInterface $admin): void
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

    public function addField(
        FieldDescriptionCollection $list,
        ?string $type,
        FieldDescriptionInterface $fieldDescription,
        AdminInterface $admin
    ): void {
        $this->decorated->addField($list,$type,$fieldDescription,$admin);
    }

    public function fixFieldDescription(AdminInterface $admin, FieldDescriptionInterface $fieldDescription): void
    {
        $this->decorated->fixFieldDescription( $admin , $fieldDescription);
    }
}
