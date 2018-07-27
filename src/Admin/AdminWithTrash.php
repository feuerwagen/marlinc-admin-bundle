<?php
/**
 * Created by PhpStorm.
 * User: em
 * Date: 26.07.18
 * Time: 09:29
 */

namespace Marlinc\AdminBundle\Admin;


use Sonata\AdminBundle\Admin\FieldDescriptionCollection;
use Sonata\AdminBundle\Builder\DatagridBuilderInterface;
use Sonata\AdminBundle\Datagrid\DatagridInterface;

interface AdminWithTrash
{
    /**
     * Returns a list depend on the given $object.
     *
     * @return FieldDescriptionCollection
     */
    public function getTrashList(): FieldDescriptionCollection;

    /**
     * @return DatagridInterface
     */
    public function getTrashDatagrid(): DatagridInterface;

    public function setTrashDatagridBuilder(DatagridBuilderInterface $datagridBuilder);

    public function getTrashDatagridBuilder();

    public function hasTrashFieldDescription(string $name): bool;
}