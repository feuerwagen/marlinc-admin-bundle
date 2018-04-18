<?php
/**
 * Created by PhpStorm.
 * User: em
 * Date: 18.04.18
 * Time: 13:20
 */

namespace Marlinc\AdminBundle\Datagrid;

use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Filter\FilterFactoryInterface;
use Sonata\AdminBundle\Guesser\TypeGuesserInterface;
use Sonata\AdminBundle\Security\Handler\SecurityHandlerInterface;
use Sonata\DoctrineORMAdminBundle\Builder\DatagridBuilder as BaseBuilder;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;

class DatagridBuilder extends BaseBuilder
{
    /**
     * @var SecurityHandlerInterface
     */
    private $securityHandler;

    public function __construct(FormFactoryInterface $formFactory, FilterFactoryInterface $filterFactory, TypeGuesserInterface $guesser, bool $csrfTokenEnabled = true, SecurityHandlerInterface $securityHandler)
    {
        parent::__construct($formFactory, $filterFactory, $guesser, $csrfTokenEnabled);

        $this->securityHandler = $securityHandler;
    }


    public function getBaseDatagrid(AdminInterface $admin, array $values = [])
    {
        $pager = $this->getPager($admin->getPagerType());

        $pager->setCountColumn($admin->getModelManager()->getIdentifierFieldNames($admin->getClass()));

        $defaultOptions = [];
        if ($this->csrfTokenEnabled) {
            $defaultOptions['csrf_protection'] = false;
        }

        $formBuilder = $this->formFactory->createNamedBuilder('filter', FormType::class, [], $defaultOptions);

        return new Datagrid($admin, $this->securityHandler, $admin->createQuery(), $admin->getList(), $pager, $formBuilder, $values);
    }
}