<?php
/**
 * Created by PhpStorm.
 * User: elias
 * Date: 29.06.17
 * Time: 13:57
 */

namespace MarlincUtils\AdminBundle\Source;


use Doctrine\ORM\Internal\Hydration\IterableResult;
use Doctrine\ORM\Query;
use Exporter\Exception\InvalidMethodCallException;
use Exporter\Source\SourceIteratorInterface;
use MarlincUtils\AdminBundle\Export\ExportFormat;

class ComplexStructureSourceIterator implements SourceIteratorInterface
{
    /**
     * @var Query
     */
    protected $query;

    /**
     * @var ExportFormat
     */
    protected $format;

    /**
     * @var IterableResult
     */
    protected $iterator;

    public function __construct(Query $query, ExportFormat $format) {
        $this->query = clone $query;
        $this->query->setParameters($query->getParameters());
        foreach ($query->getHints() as $name => $value) {
            $this->query->setHint($name, $value);
        }
        
        $this->format = $format;

        $format->createPropertyAccessor();
    }

    /**
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     */
    public function current()
    {
        if (!$this->iterator) {
            throw new InvalidMethodCallException('Iterator is not initialized');
        }

        $current = $this->iterator->current();
        $data = $this->format->getRow($current[0]);

        $this->query->getEntityManager()->getUnitOfWork()->detach($current[0]);
        return $data;
    }

    /**
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next()
    {
        if (!$this->iterator) {
            throw new InvalidMethodCallException('Iterator is not initialized');
        }

        $this->iterator->next();
    }

    /**
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     */
    public function key()
    {
        if (!$this->iterator) {
            throw new InvalidMethodCallException('Iterator is not initialized');
        }

        return $this->iterator->key();
    }

    /**
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid()
    {
        if (!$this->iterator) {
            throw new InvalidMethodCallException('Iterator is not initialized');
        }

        return $this->iterator->valid();
    }

    /**
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        if ($this->iterator) {
            throw new InvalidMethodCallException('Cannot rewind a Doctrine\ORM\Query');
        }

        $this->iterator = $this->query->iterate();
        $this->iterator->rewind();
    }

}