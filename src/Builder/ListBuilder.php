<?php

namespace Marlinc\AdminBundle\Builder;


use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Admin\FieldDescriptionInterface;
use Sonata\DoctrineORMAdminBundle\Builder\ListBuilder as BaseListBuilder;

class ListBuilder extends BaseListBuilder
{
    public function buildField($type, FieldDescriptionInterface $fieldDescription, AdminInterface $admin)
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
}
