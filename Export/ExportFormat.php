<?php
/**
 * Created by PhpStorm.
 * User: elias
 * Date: 29.06.17
 * Time: 14:59
 */

namespace MarlincUtils\AdminBundle\Export;


use Doctrine\ORM\Query;
use Exporter\Source\SourceIteratorInterface;
use MarlincUtils\AdminBundle\Source\ComplexStructureSourceIterator;
use MarlincUtils\AdminBundle\Transformer\TransformerInterface;
use Sonata\AdminBundle\Admin\AdminInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyPath;

class ExportFormat implements ExportFormatInterface
{
    /**
     * @var array
     */
    protected $filetypes = ['xlsx'];

    /**
     * @var ExportColumn[]
     */
    protected $columns;

    /**
     * @var PropertyPath[]
     */
    protected $propertyPaths;

    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

    /**
     * @param string $name
     * @param int $type
     * @param TransformerInterface|null $transformer
     * @param array $fields
     * @param ExportHeader|null $header
     * @param int|null $format
     * @return ExportFormat
     */
    public function addColumn(string $name, int $type, TransformerInterface $transformer = null, $fields = [''], ExportHeader $header = null, int $format = null) {
        $this->columns[] = new ExportColumn($name, $type, $transformer, $fields, $header, $format);

        return $this;
    }

    /**
     * @return array
     */
    public function getFiletypes(): array
    {
        return $this->filetypes;
    }

    /**
     * @param object $currentObject
     * @return array
     */
    public function getRow($currentObject) {
        $data = [];

        foreach ($this->columns as $column) {
            $colData = $column->transform($currentObject, $this->propertyAccessor, $this->propertyPaths);
            $data = array_merge($data, $colData);
        }

        return $data;
    }

    /**
     * @return array
     */
    public function getHeader() {
        $hasGroupHeaders = false;
        /** @var ExportHeader[] $headers */
        $headers = [];

        foreach ($this->columns as $column) {
            $header = $column->getHeader();

            if ($header instanceof ExportHeader) {
                $headers[] = $header;

                if ($header->hasGroupField()) {
                    $hasGroupHeaders = true;
                }
            }
        }

        $data = [];

        if ($hasGroupHeaders) {

            foreach ($headers as $header) {
                foreach ($header->getGroupFields() as $field) {
                    $data['group'][] = $field;
                }
            }
        }

        foreach ($headers as $header) {
            foreach ($header->getSimpleFields() as $field) {
                $data['simple'][] = $field;
            }
        }

        return $data;
    }

    public function createPropertyAccessor()
    {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->propertyPaths = [];

        foreach ($this->columns as $column) {
            foreach ($column->getFields() as $field) {
                if (!array_key_exists($field, $this->propertyPaths) && $column->getType() != ExportColumn::TYPE_FIXED) {
                    $this->propertyPaths[$field] = new PropertyPath($field);
                }
            }
        }
    }

    /**
     * @param AdminInterface $admin
     * @param string $filetype
     * @return string
     */
    public function getFilename(AdminInterface $admin, $filetype) {
        $class = $admin->getClass();

        return sprintf(
            'export_%s_%s.%s',
            strtolower(substr($class, strripos($class, '\\') + 1)),
            date('Y_m_d_H_i_s', strtotime('now')),
            $filetype
        );
    }

    /**
     * @return array
     */
    public function getColumnsType()
    {
        $types = [];

        foreach ($this->columns as $column) {
            $colTypes = $column->getTypes();
            $types = array_merge($types, $colTypes);
        }

        return $types;
    }

    /**
     * Let the ExportFormat decide about the SourceIterator to use.
     * This is also the place to modify the query, if needed.
     *
     * @param Query $query
     * @return SourceIteratorInterface
     */
    public function getSourceIterator(Query $query) {
        return new ComplexStructureSourceIterator($query, $this);
    }
}