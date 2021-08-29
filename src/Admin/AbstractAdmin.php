<?php
declare(strict_types=1);

namespace Marlinc\AdminBundle\Admin;


use Sonata\AdminBundle\Admin\AbstractAdmin as BaseAdmin;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Symfony\Component\Intl\Languages;
use Symfony\Component\Intl\Countries;

abstract class AbstractAdmin extends BaseAdmin
{
    const MODE_TRASH = 'trash';
    const MODE_LIST = 'list';

    private string $datagridMode = AbstractAdmin::MODE_LIST;

    protected function getFilteredLanguages(): array
    {
        \Locale::setDefault('en');
        // TODO: Filter depending on app/admin config
        return Languages::getNames();
    }

    protected function getFilteredCountries(): array
    {
        \Locale::setDefault('en');
        // TODO: Filter depending on app/admin config
        return Countries::getNames();
    }

    /**
     * @inheritdoc
     */
    protected function configureDefaultSortValues(array &$sortValues): void
    {
        $sortValues[DatagridInterface::SORT_BY] = 'updatedAt';
        $sortValues[DatagridInterface::SORT_ORDER] = 'DESC';

        // Override sort for trash mode.
        if ($this->datagridMode == AbstractAdmin::MODE_TRASH) {
            $sortValues[DatagridInterface::SORT_BY] = 'deletedAt';
        }
    }

    public function getDatagridMode(): string
    {
        return $this->datagridMode;
    }

    public function setDatagridMode(string $mode): void
    {
        $this->datagridMode = $mode;
    }

    protected function configureTrashFields(ListMapper $mapper)
    {
        $mapper
            ->add('deletedAt')
            ->add('deletedBy')
            ->add(ListMapper::NAME_ACTIONS, null, [
                ListMapper::TYPE_ACTIONS => [
                    'untrash' => [],
                ]
            ])
        ;
    }

    protected function configureListFields(ListMapper $list):void
    {
        $list
            ->add(ListMapper::NAME_ACTIONS, null, [
                ListMapper::TYPE_ACTIONS => [
                    'show' => [],
                    'edit' => [],
                    'delete' => [],
                ]
            ])
        ;
    }
}