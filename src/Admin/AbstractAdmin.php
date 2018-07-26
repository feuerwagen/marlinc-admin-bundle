<?php
/**
 * Created by PhpStorm.
 * User: elias
 * Date: 21.03.17
 * Time: 15:43
 */

namespace Marlinc\AdminBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin as BaseAdmin;
use Sonata\AdminBundle\Admin\FieldDescriptionCollection;
use Sonata\AdminBundle\Builder\DatagridBuilderInterface;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\Type\ModelHiddenType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Intl\Intl;

abstract class AbstractAdmin extends BaseAdmin implements AdminWithTrash
{
    /**
     * The default number of results to display in the list.
     *
     * @var int
     */
    protected $maxPerPage = 20;

    /**
     * Predefined per page options.
     *
     * @var array
     */
    protected $perPageOptions = [10, 20, 50, 100, 200];

    /**
     * Default values to the datagrid.
     *
     * @var array
     */
    protected $datagridValues = [
        '_sort_order' => 'DESC',
        '_sort_by' => 'updatedAt',
    ];

    /**
     * @var DatagridInterface
     */
    private $trashDatagrid;

    /**
     * @var DatagridBuilderInterface
     */
    private $trashDatagridBuilder;


    private $trashList;

    protected function getFilteredLanguages() {
        $languages = array_flip($this->getConfigurationPool()->getContainer()->getParameter('marlinc_languages'));

        return array_intersect_key(Intl::getLanguageBundle()->getLanguageNames(), $languages);
    }

    protected function getFilteredCountries() {
        $countries = array_flip($this->getConfigurationPool()->getContainer()->getParameter('marlinc_countries'));

        return array_intersect_key(Intl::getRegionBundle()->getCountryNames(), $countries);
    }

    /**
     * @inheritDoc
     */
    public function getTrashList(): FieldDescriptionCollection
    {
        $this->buildTrashList();

        return $this->trashList;
    }

    /**
     * @inheritDoc
     */
    public function getTrashDatagrid(): DatagridInterface
    {
        $this->buildTrashDatagrid();

        return $this->trashDatagrid;
    }

    public function setTrashDatagridBuilder(DatagridBuilderInterface $datagridBuilder)
    {
        $this->trashDatagridBuilder = $datagridBuilder;
    }

    public function getTrashDatagridBuilder()
    {
        return $this->trashDatagridBuilder;
    }

    private function buildTrashDatagrid()
    {
        if ($this->trashDatagrid) {
            return;
        }

        // Override sort.
        $this->datagridValues['_sort_order'] = 'DESC';
        $this->datagridValues['_sort_by'] = 'deletedAt';

        $filterParameters = $this->getFilterParameters();

        // transform _sort_by from a string to a FieldDescriptionInterface for the datagrid.
        if (isset($filterParameters['_sort_by']) && is_string($filterParameters['_sort_by'])) {
            if ($this->hasListFieldDescription($filterParameters['_sort_by'])) {
                $filterParameters['_sort_by'] = $this->getListFieldDescription($filterParameters['_sort_by']);
            } else {
                $filterParameters['_sort_by'] = $this->getModelManager()->getNewFieldDescriptionInstance(
                    $this->getClass(),
                    $filterParameters['_sort_by'],
                    []
                );

                $this->getListBuilder()->buildField(null, $filterParameters['_sort_by'], $this);
            }
        }

        // initialize the datagrid
        $this->datagrid = $this->getTrashDatagridBuilder()->getBaseDatagrid($this, $filterParameters);

        $this->datagrid->getPager()->setMaxPageLinks($this->maxPageLinks);

        $mapper = new DatagridMapper($this->getTrashDatagridBuilder(), $this->datagrid, $this);

        // build the datagrid filter
        $this->configureDatagridFilters($mapper);

        // ok, try to limit to add parent filter
        if ($this->isChild() && $this->getParentAssociationMapping() && !$mapper->has($this->getParentAssociationMapping())) {
            $mapper->add($this->getParentAssociationMapping(), null, [
                'show_filter' => false,
                'label' => false,
                'field_type' => ModelHiddenType::class,
                'field_options' => [
                    'model_manager' => $this->getModelManager(),
                ],
                'operator_type' => HiddenType::class,
            ], null, null, [
                'admin_code' => $this->getParent()->getCode(),
            ]);
        }

        foreach ($this->getExtensions() as $extension) {
            $extension->configureDatagridFilters($mapper);
        }
    }

    private function buildTrashList()
    {
        if ($this->trashList) {
            return;
        }

        $this->trashList = $this->getListBuilder()->getBaseList();

        $mapper = new ListMapper($this->getListBuilder(), $this->trashList, $this);

        if (count($this->getBatchActions()) > 0) {
            $fieldDescription = $this->getModelManager()->getNewFieldDescriptionInstance(
                $this->getClass(),
                'batch',
                [
                    'label' => 'batch',
                    'code' => '_batch',
                    'sortable' => false,
                    'virtual_field' => true,
                ]
            );

            $fieldDescription->setAdmin($this);
            // NEXT_MAJOR: Remove this line and use commented line below it instead
            $fieldDescription->setTemplate($this->getTemplate('batch'));
            // $fieldDescription->setTemplate($this->getTemplateRegistry()->getTemplate('batch'));

            $mapper->add($fieldDescription, 'batch');
        }

        $this->configureTrashFields($mapper);

        if ($this->hasRequest() && $this->getRequest()->isXmlHttpRequest()) {
            $fieldDescription = $this->getModelManager()->getNewFieldDescriptionInstance(
                $this->getClass(),
                'select',
                [
                    'label' => false,
                    'code' => '_select',
                    'sortable' => false,
                    'virtual_field' => false,
                ]
            );

            $fieldDescription->setAdmin($this);
            // NEXT_MAJOR: Remove this line and use commented line below it instead
            $fieldDescription->setTemplate($this->getTemplate('select'));
            // $fieldDescription->setTemplate($this->getTemplateRegistry()->getTemplate('select'));

            $mapper->add($fieldDescription, 'select');
        }
    }

    protected function configureTrashFields(ListMapper $mapper)
    {
    }
}