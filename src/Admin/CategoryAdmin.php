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
use Sonata\ClassificationBundle\Admin\CategoryAdmin as BaseCategoryAdmin;
use Sonata\ClassificationBundle\Form\Type\CategorySelectorType;
use Sonata\MediaBundle\Model\MediaInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class CategoryAdmin extends BaseCategoryAdmin
{
    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->with('General', ['class' => 'col-md-6'])
                ->add('name')
                ->add('description', TextareaType::class, [
                    'required' => false,
                ])
        ;

        if ($this->hasSubject()) {
            if (null !== $this->getSubject()->getParent() || null === $this->getSubject()->getId()) { // root category cannot have a parent
                $formMapper
                    ->add('parent', CategorySelectorType::class, [
                        'category' => $this->getSubject() ?: null,
                        'model_manager' => $this->getModelManager(),
                        'class' => $this->getClass(),
                        'required' => true,
                        'context' => $this->getSubject()->getContext(),
                    ])
                ;
            }
        }

        $position = $this->hasSubject() && null !== $this->getSubject()->getPosition() ? $this->getSubject()->getPosition() : 0;

        $formMapper
            ->end()
            ->with('Options', ['class' => 'col-md-6'])
                ->add('enabled', CheckboxType::class, [
                    'required' => false,
                ])
                ->add('hidden', CheckboxType::class, [
                    'required' => false,
                ])
                ->add('position', IntegerType::class, [
                    'required' => false,
                    'data' => $position,
                ])
            ->end()
        ;

        if (interface_exists(MediaInterface::class)) {
            $formMapper
                ->with('General')
                    ->add('media', ModelListType::class, [
                        'required' => false,
                    ], [
                        'link_parameters' => [
                            'provider' => 'sonata.media.provider.image',
                            'context' => 'sonata_category',
                        ],
                    ])
                ->end();
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
            ->add('context', null, [
                'sortable' => 'context.name',
            ])
            ->add('slug')
            ->add('description')
            ->add('enabled', null, ['editable' => true])
            ->add('hidden', null, ['editable' => true])
            ->add('position')
            ->add('parent', null, [
                'sortable' => 'parent.name',
            ])
        ;
    }
}
