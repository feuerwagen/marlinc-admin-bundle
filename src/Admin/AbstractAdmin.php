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
    public function getTemplate($name)
    {
        if ($this->datagridMode == 'trash' && $name == 'batch_confirmation') {
            return '@MarlincAdmin/edit/bath_trash_confirmation.html.twig';
        }

        return parent::getTemplate($name);
    }

    public function getBatchActions()
    {
        if ($this->datagridMode == 'list') {
            return parent::getBatchActions();
        }

        // Override batch actions while in trash mode.
        $actions = [];

        if ($this->hasRoute('realdelete') && $this->hasAccess('delete')) {
            $actions['realdelete'] = [
                'label' => 'action_real_delete',
                'translation_domain' => 'MarlincAdminBundle',
                'ask_confirmation' => true, // by default always true
            ];
        }

        if ($this->hasRoute('untrash') && $this->hasAccess('edit')) {
            $actions['untrash'] = [
                'label' => 'action_restore',
                'translation_domain' => 'PicossSonataExtraAdminBundle',
                'ask_confirmation' => true, // by default always true
            ];
        }

        foreach ($actions  as $name => &$action) {
            if (!array_key_exists('label', $action)) {
                $action['label'] = $this->getTranslationLabel($name, 'batch', 'label');
            }

            if (!array_key_exists('translation_domain', $action)) {
                $action['translation_domain'] = $this->getTranslationDomain();
            }
        }

        return $actions;
    }

    public function getList()
    {
        $this->buildList();

        return $this->list;
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

    protected function buildList()
    {
        if ($this->list) {
            return;
        }

        $this->list = $this->getListBuilder()->getBaseList();

        $mapper = new ListMapper($this->getListBuilder(), $this->list, $this);

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
            $fieldDescription->setTemplate($this->getTemplateRegistry()->getTemplate('batch'));

            $mapper->add($fieldDescription, 'batch');
        }

        if ($this->datagridMode == AbstractAdmin::MODE_TRASH) {
            $this->configureTrashFields($mapper);
        } else {
            $this->configureListFields($mapper);
        }

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
            $fieldDescription->setTemplate($this->getTemplateRegistry()->getTemplate('select'));

            $mapper->add($fieldDescription, 'select');
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

    protected function configureListFields(ListMapper $list)
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