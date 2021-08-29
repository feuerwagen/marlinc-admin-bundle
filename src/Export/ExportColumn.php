<?php
declare(strict_types=1);


namespace Marlinc\AdminBundle\Export;


use Marlinc\AdminBundle\Transformer\HeaderTransformerInterface;
use Marlinc\AdminBundle\Transformer\TransformerInterface;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class ExportColumn
{
    /**
     * Column is a fixed single value (independent of any fields).
     */
    const TYPE_FIXED = 0;

    /**
     * Column is a single value (depending on the values of the fields).
     */
    const TYPE_SINGLE = 1;

    /**
     * Column is split into multiple sub-columns (with values depending on the values of the fields).
     */
    const TYPE_MULTIPLE = 2;

    /**
     * Name of the column for the header row in the export.
     */
    protected string $name;

    /**
     * Column type
     * @see TYPE_FIXED
     * @see TYPE_SINGLE
     * @see TYPE_MULTIPLE
     */
    protected int $type;

    /**
     * An array of property paths to include into this column.
     * @var string[]
     */
    protected array $fields;

    /**
     * Will be applied to the raw field values, if given. @see TransformerInterface
     */
    protected ?TransformerInterface $transformer;

    /**
     * Defines the style of the header row for this column, if given. If not given, a standard header based on @see $name
     * will be generated.
     */
    protected ?ExportHeader $header;

    protected ?int $format = null;

    protected ?array $effectiveColumns = null;

    public function __construct(string $name, int $type, ?TransformerInterface $transformer = null, array $fields = [], ?ExportHeader $header = null, ?int $format = null)
    {
        $this->name = $name;
        $this->type = $type;
        $this->fields = $fields;
        $this->transformer = $transformer;
        $this->header = $header;
        $this->format = $format;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function getFormat(): ?int
    {
        return $this->format;
    }

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

    public function transform($data, PropertyAccessor $accessor, array $paths): array
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
                    // nonexistent object in path will be ignored
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
                // nonexistent object in path will be ignored
                $result = [$this->name => null];
            }
        } elseif ($this->type == self::TYPE_MULTIPLE) {
            $result = [];
            foreach ($this->fields as $field) {
                try {
                    $result[$field] = $accessor->getValue($data, $paths[$field]);
                } catch (UnexpectedTypeException $e) {
                    // nonexistent object in path will be ignored
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
     * @return array<mixed, int>
     */
    public function getTypes(): array
    {
        $types = [];

        foreach ($this->effectiveColumns as $value) {
            $types[$value] = $this->format;
        }

        return $types;
    }
}