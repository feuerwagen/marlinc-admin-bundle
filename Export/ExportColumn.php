<?php
/**
 * Created by PhpStorm.
 * User: elias
 * Date: 29.06.17
 * Time: 14:59
 */

namespace MarlincUtils\AdminBundle\Export;


use MarlincUtils\AdminBundle\Transformer\HeaderTransformerInterface;
use MarlincUtils\AdminBundle\Transformer\TransformerInterface;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class ExportColumn
{
    const TYPE_FIXED = 0;
    const TYPE_SINGLE = 1;
    const TYPE_MULTIPLE = 2;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var int
     */
    protected $type;

    /**
     * @var array
     */
    protected $fields;

    /**
     * @var TransformerInterface|null
     */
    protected $transformer;

    /**
     * @var ExportHeader|null
     */
    protected $header;

    /**
     * @var int
     */
    protected $format;

    /**
     * @var array
     */
    protected $effectiveColumns = null;

    /**
     * Column constructor.
     * @param string $name
     * @param int $type
     * @param TransformerInterface|null $transformer
     * @param array $fields
     * @param ExportHeader|null $header
     * @param int $format
     */
    public function __construct(string $name, int $type, TransformerInterface $transformer = null, $fields = [], ExportHeader $header = null, $format = null)
    {
        $this->name = $name;
        $this->type = $type;
        $this->fields = $fields;
        $this->transformer = $transformer;
        $this->header = $header;
        $this->format = $format;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @return int|null
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * @return ExportHeader
     */
    public function getHeader(): ExportHeader
    {
        if ($this->header instanceof ExportHeader) {
            return $this->header;
        } elseif ($this->transformer instanceof HeaderTransformerInterface) {
            return $this->transformer->getHeader($this->name);
        } elseif ($this->type == self::TYPE_MULTIPLE) {
            $header = new ExportHeader();
            $header->addGroupField($this->name);

            foreach ($this->fields as $field) {
                $header->addSimpleField($field);
            }

            return $header;
        }

        return ExportHeader::createSimpleHeader($this->name);
    }

    /**
     * @param $data
     * @param PropertyAccessor $accessor
     * @param array $paths
     * @return array
     */
    public function transform($data, PropertyAccessor $accessor, array $paths)
    {
        $values = [];
        $result = [$this->name => $this->fields[0]];


        if ($this->type == self::TYPE_FIXED && $this->transformer instanceof TransformerInterface) {
            $result = $this->transformer->transform($this->name, $this->type, $values);
        } elseif ($this->type !== self::TYPE_FIXED && $this->transformer instanceof TransformerInterface) {
            foreach ($this->fields as $field) {
                try {
                    $values[$field] = $accessor->getValue($data, $paths[$field]);
                } catch (UnexpectedTypeException $e) {
                    // non existent object in path will be ignored
                    $values[$field] = null;
                }
            }

            $result = $this->transformer->transform($this->name, $this->type, $values);
        } elseif ($this->type == self::TYPE_SINGLE && count($this->fields) == 1) {
            // Default to simple string conversion for single value columns.
            try {
                $value = $accessor->getValue($data, $paths[$this->fields[0]]);

                if (is_array($value)) {
                    $result = [$this->name => implode(', ', $value)];
                } elseif (is_bool($value)) {
                    $result = [$this->name => (($value === true) ? 'x' : '')];
                } else {
                    $result = [$this->name => (string) $value];
                }
            } catch (UnexpectedTypeException $e) {
                // non existent object in path will be ignored
                $result = [$this->name => null];
            }
        } elseif ($this->type == self::TYPE_MULTIPLE) {
            $result = [];
            foreach ($this->fields as $field) {
                try {
                    $result[$field] = $accessor->getValue($data, $paths[$field]);
                } catch (UnexpectedTypeException $e) {
                    // non existent object in path will be ignored
                    $result[$field] = null;
                }
            }
        }

        if ($this->effectiveColumns == null) {
            $this->effectiveColumns = array_keys($result);
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getTypes()
    {
        $types = [];

        foreach ($this->effectiveColumns as $value) {
            $types[$value] = $this->format;
        }

        return $types;
    }
}