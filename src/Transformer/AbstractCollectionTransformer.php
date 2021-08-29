<?php
declare(strict_types=1);

namespace Marlinc\AdminBundle\Transformer;


use Doctrine\Common\Collections\ArrayCollection;
use Marlinc\AdminBundle\Export\ExportHeader;

abstract class AbstractCollectionTransformer extends AbstractHeaderTransformer
{
    protected ?iterable $collection = null;

    public function __construct(iterable $collection = null)
    {
        $this->collection = (is_array($collection)) ? new ArrayCollection($collection) : $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeader(string $name): ExportHeader
    {
        $header = new ExportHeader();

        if ($this->collection !== null && count($this->collection) > 0) {
            $header->addGroupField($name, $this->style['color'], $this->style['font']);

            foreach ($this->collection as $item) {
                $header->addSimpleField($this->getLabelValue($item), $this->style['color'], $this->style['font']);
            }
        }

        return $header;
    }

    /**
     * @inheritdoc
     */
    public function transform(string $name, int $type, array $data): array
    {
        $values = [];

        if ($this->collection == null) {
            $this->collection = $this->loadCollection($data);
        }

        foreach ($this->collection as $item) {
            $label = $this->getLabelValue($item);

            if (!array_key_exists($label, $values)) {
                $values[$label] = '';
            }

            foreach ($data as $value) {
                if (is_iterable($value)) {
                    foreach ($value as $entity) {
                        if ($entity == $item) {
                            $values[$label] = $this->getDataValue($entity);
                        }
                    }
                }
            }
        }

        return $values;
    }

    /**
     * Load the collection/array which defines the columns returned by this export transformer.
     * This method needs to be implemented, if the collection is not already given in the constructor.
     *
     * @param array $data The available object properties
     */
    protected function loadCollection(array $data): iterable
    {
        throw new \RuntimeException("Transformer needs to implement loadCollection().");
    }

    /**
     * Get the value with which to fill the data cell, if the collection value is present in the current dataset.
     */
    abstract protected function getDataValue(object $entity): string;

    /**
     * Get the label for the column representing an item in the collection.
     */
    abstract protected function getLabelValue($collectionItem): string;
}