<?php
/**
 * Created by PhpStorm.
 * User: elias
 * Date: 06.12.17
 * Time: 14:23
 */

namespace MarlincUtils\AdminBundle\Export;

use Doctrine\ORM\Query;
use Exporter\Source\SourceIteratorInterface;
use MarlincUtils\AdminBundle\Transformer\TransformerInterface;
use Sonata\AdminBundle\Admin\AdminInterface;

interface ExportFormatInterface
{
    /**
     * @param string $name
     * @param int $type
     * @param TransformerInterface|null $transformer
     * @param array $fields
     * @param ExportHeader|null $header
     * @param int|null $format
     * @return ExportFormat
     */
    public function addColumn(string $name, int $type, TransformerInterface $transformer = null, $fields = [''], ExportHeader $header = null, int $format = null);

    /**
     * @return array
     */
    public function getFiletypes(): array;

    /**
     * @param object $currentObject
     * @return array
     */
    public function getRow($currentObject);

    /**
     * @return array
     */
    public function getHeader();

    public function createPropertyAccessor();

    /**
     * @param AdminInterface $admin
     * @param string $filetype
     * @return string
     */
    public function getFilename(AdminInterface $admin, $filetype);

    /**
     * @return array
     */
    public function getColumnsType();

    /**
     * Let the ExportFormat decide about the SourceIterator to use.
     * This is also the place to modify the query, if needed.
     *
     * @param Query $query
     * @return SourceIteratorInterface
     */
    public function getSourceIterator(Query $query);
}