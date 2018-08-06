<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Marlinc\AdminBundle\Admin;

use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelListType;
use Sonata\ClassificationBundle\Admin\CollectionAdmin as BaseCollectionAdmin;
use Sonata\MediaBundle\Model\MediaInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class CollectionAdmin extends BaseCollectionAdmin
{
    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('name')
            ->add('description', TextareaType::class, [
                'required' => false,
            ])
            ->add('context')
            ->add('enabled', CheckboxType::class, [
                'required' => false,
            ])
            ->add('hidden', CheckboxType::class, [
                'required' => false,
            ])
        ;

        if (interface_exists(MediaInterface::class)) {
            $formMapper->add('media', ModelListType::class, [
                'required' => false,
            ], [
                'link_parameters' => [
                    'provider' => 'sonata.media.provider.image',
                    'context' => 'sonata_collection',
                ],
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        parent::configureDatagridFilters($datagridMapper);

        $datagridMapper
            ->add('name')
            ->add('enabled')
            ->add('hidden')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('name')
            ->add('slug')
            ->add('context', null, [
                'sortable' => 'context.name',
            ])
            ->add('enabled', null, [
                'editable' => true,
            ])
            ->add('hidden', null, [
                'editable' => true,
            ])
        ;
    }
}
