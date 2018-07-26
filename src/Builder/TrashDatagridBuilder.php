<?php
/**
 * Created by PhpStorm.
 * User: em
 * Date: 26.07.18
 * Time: 09:43
 */

namespace Marlinc\AdminBundle\Builder;


use Marlinc\AdminBundle\Admin\AdminWithTrash;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Datagrid\Datagrid;
use Sonata\DoctrineORMAdminBundle\Builder\DatagridBuilder;
use Symfony\Component\Form\Extension\Core\Type\FormType;

class TrashDatagridBuilder extends DatagridBuilder
{
    public function getBaseDatagrid(AdminInterface $admin, array $values = [])
    {
        if ($admin instanceof AdminWithTrash) {
            $pager = $this->getPager($admin->getPagerType());

            $pager->setCountColumn($admin->getModelManager()->getIdentifierFieldNames($admin->getClass()));

            $defaultOptions = [];
            if ($this->csrfTokenEnabled) {
                $defaultOptions['csrf_protection'] = false;
            }

            $formBuilder = $this->formFactory->createNamedBuilder('filter', FormType::class, [], $defaultOptions);

            return new Datagrid($admin->createQuery(), $admin->getTrashList(), $pager, $formBuilder, $values);
        }

        return parent::getBaseDatagrid($admin, $values);
    }
}