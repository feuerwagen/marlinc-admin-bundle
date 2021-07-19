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
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\Type\ModelHiddenType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Intl\Languages;
use Symfony\Component\Intl\Countries;

abstract class AbstractAdmin extends BaseAdmin
{
    const MODE_TRASH = 'trash';

    const MODE_LIST = 'list';

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
     * The list collection.
     *
     * @var FieldDescriptionCollection
     */
    private $list;

    /**
     * @var string
     */
    private $datagridMode = AbstractAdmin::MODE_LIST;

    protected function getFilteredLanguages() {
        \Locale::setDefault('en');
        return $languages = Languages::getNames();
    }

    protected function getFilteredCountries() {
        \Locale::setDefault('en');
        return Countries::getNames();;
    }

    /**
     * @inheritDoc
     */
    public function getTemplate($name)
    {
        if ($this->datagridMode == 'trash' && $name == 'batch_confirmation') {
            return '@MarlincAdmin/edit/batch_trash_confirmation.html.twig';
        }

        return $this->getTemplateRegistry()->getTemplate($name);

    }

    public function getDatagridMode()
    {
        return $this->datagridMode;
    }

    public function setDatagridMode(string $mode)
    {
        $this->datagridMode = $mode;
    }

    public function buildDatagrid()
    {
        if ($this->datagrid) {
            return;
        }

        // Override sort.
        if ($this->datagridMode == AbstractAdmin::MODE_TRASH) {
            $this->datagridValues['_sort_order'] = 'DESC';
            $this->datagridValues['_sort_by'] = 'deletedAt';
        }

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
        $this->datagrid = $this->getDatagridBuilder()->getBaseDatagrid($this, $filterParameters);

        $this->datagrid->getPager()->setMaxPageLinks($this->maxPageLinks);

        $mapper = new DatagridMapper($this->getDatagridBuilder(), $this->datagrid, $this);

        // Build the datagrid filter
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

    protected function configureTrashFields(ListMapper $mapper)
    {
        $mapper
            ->add('deletedAt')
            ->add('deletedBy')
            ->add('_action', null, [
                'actions' => [
                    'untrash' => [],
                ]
            ])
        ;
    }

    protected function configureListFields(ListMapper $list):void
    {
        $list
            ->add('_action', null, [
                'actions' => [
                    'show' => [],
                    'edit' => [],
                    'delete' => [],
                ]
            ])
        ;
    }
}