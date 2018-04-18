<?php
/**
 * Created by PhpStorm.
 * User: em
 * Date: 18.04.18
 * Time: 13:23
 */

namespace Marlinc\AdminBundle\Datagrid;

use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Admin\FieldDescriptionCollection;
use Sonata\AdminBundle\Datagrid\Datagrid as BaseDatagrid;
use Sonata\AdminBundle\Datagrid\PagerInterface;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Security\Handler\SecurityHandlerInterface;
use Symfony\Component\Form\FormBuilderInterface;

class Datagrid extends BaseDatagrid
{
    /**
     * @var AdminInterface
     */
    private $admin;

    /**
     * @var SecurityHandlerInterface
     */
    private $securityHandler;

    public function __construct(AdminInterface $admin, SecurityHandlerInterface $securityHandler, ProxyQueryInterface $query, FieldDescriptionCollection $columns, PagerInterface $pager, FormBuilderInterface $formBuilder, array $values = [])
    {
        parent::__construct($query, $columns, $pager, $formBuilder, $values);

        $this->admin = $admin;
        $this->securityHandler = $securityHandler;
    }


    public function getResults()
    {
        parent::getResults();

        // Remove entities from results depending on user authorization.
        foreach ($this->results as $key => $entity) {
            if (!$this->securityHandler->isGranted($this->admin, 'LIST', $entity)) {
                unset($this->results[$key]);
            }
        }

        return $this->results;
    }
}