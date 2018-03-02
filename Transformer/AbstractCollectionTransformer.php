<?php
/**
 * Created by PhpStorm.
 * User: elias
 * Date: 03.07.17
 * Time: 17:24
 */

namespace Marlinc\AdminBundle\Transformer;


use Doctrine\Common\Collections\ArrayCollection;
use Marlinc\AdminBundle\Export\ExportHeader;

abstract class AbstractCollectionTransformer extends AbstractHeaderTransformer
{
    /**
     * @var \Traversable|null
     */
    protected $collection;

    /**
     * AbstractCollectionTransformer constructor.
     * @param $collection
     */
    public function __construct($collection = null)
    {
        if (is_array($collection)) {
            $collection = new ArrayCollection($collection);
        }
        $this->collection = $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeader(string $name)
    {
        $header = new ExportHeader();

        if (($this->collection instanceof \Traversable || is_array($this->collection)) && count($this->collection) > 0) {
            $header->addGroupField($name, $this->style['color'], $this->style['font']);

            foreach ($this->collection as $item) {
                $header->addSimpleField($this->getLabelValue($item), $this->style['color'], $this->style['font']);
            }
        }

        return $header;
    }

    /**
     * @param string $name
     * @param int $type
     * @param array $data
     * @return mixed
     */
    public function transform(string $name, int $type, array $data)
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

            foreach ($data as $key => $value) {
                if ($value instanceof \Traversable) {
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
     * @return \Traversable
     */
    protected function loadCollection(array $data) {
        throw new \RuntimeException();
    }

    /**
     * Get the value with which to fill the data cell, if the collection value is present in the current dataset.
     *
     * @param $entity
     * @return string
     */
    abstract protected function getDataValue($entity);

    /**
     * Get the label for the column representing an item in the collection.
     *
     * @param $collectionItem
     * @return string
     */
    abstract protected function getLabelValue($collectionItem);
}