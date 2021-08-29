<?php
declare(strict_types=1);

namespace Marlinc\AdminBundle\Export;


use Doctrine\ORM\Query;
use Marlinc\AdminBundle\Source\ComplexStructureSourceIterator;
use Marlinc\AdminBundle\Transformer\TransformerInterface;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\Exporter\Source\SourceIteratorInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyPath;

class ExportFormat implements ExportFormatInterface
{
    protected string $filetype = 'xlsx';

    /**
     * @var ExportColumn[]
     */
    protected array $columns = [];

    /**
     * @var PropertyPath[]
     */
    protected ?array $propertyPaths = null;

    protected ?PropertyAccessor $propertyAccessor = null;

    /**
     * @inheritdoc
     */
    public function addColumn(string $name, int $type, TransformerInterface $transformer = null, $fields = [''], ExportHeader $header = null, int $format = null): self
    {
        $this->columns[] = new ExportColumn($name, $type, $transformer, $fields, $header, $format);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getRow(object $currentObject): array
    {
        $data = [];

        foreach ($this->columns as $column) {
            $colData = $column->transform($currentObject, $this->propertyAccessor, $this->propertyPaths);
            $data = array_merge($data, $colData);
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function getHeader(): array
    {
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

    public function createPropertyAccessor(): self
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

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getFilename(AdminInterface $admin, $filetype): string
    {
        $class = $admin->getClass();

        return sprintf(
            'export_%s_%s.%s',
            strtolower(substr($class, strripos($class, '\\') + 1)),
            date('Y_m_d_H_i_s', strtotime('now')),
            $filetype
        );
    }

    /**
     * @inheritdoc
     */
    public function getColumnsType(): array
    {
        $types = [];

        foreach ($this->columns as $column) {
            $colTypes = $column->getTypes();
            $types = array_merge($types, $colTypes);
        }

        return $types;
    }

    /**
     * @inheritdoc
     */
    public function getSourceIterator(Query $query): SourceIteratorInterface
    {
        return new ComplexStructureSourceIterator($query, $this, 100);
    }

    /**
     * @inheritdoc
     */
    public function getFileType(): string
    {
        return $this->filetype;
    }
}