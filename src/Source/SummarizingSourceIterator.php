<?php
/**
 * Created by PhpStorm.
 * User: elias
 * Date: 06.12.17
 * Time: 09:46
 */

namespace Marlinc\AdminBundle\Source;


use Doctrine\ORM\Query;
use Marlinc\AdminBundle\Export\ExportFormat;
use Marlinc\AdminBundle\Export\SummarizingExportFormatInterface;
use Sonata\Exporter\Exception\InvalidMethodCallException;
use Sonata\Exporter\Source\SourceIteratorInterface;

/**
 * Class SummarizingSourceIterator
 * TODO: Find a more efficient way to summarize (modify DQL query!?).
 *
 * @package MarlincUtils\AdminExporterBundle\Source
 */
class SummarizingSourceIterator implements SourceIteratorInterface
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
     * @var array
     */
    protected $result;

    /**
     * @var int
     */
    protected $currentKey;

    public function __construct(Query $query, SummarizingExportFormatInterface $format) {
        $this->query = clone $query;
        $this->query->setParameters($query->getParameters());
        foreach ($query->getHints() as $name => $value) {
            $this->query->setHint($name, $value);
        }

        $this->format = $format;

        $format->createPropertyAccessor();
    }

    /**
     * @inheritDoc
     */
    public function current()
    {
        if (!is_array($this->result)) {
            throw new InvalidMethodCallException('Iterator is not initialized');
        }

        return $this->format->getRow(current($this->result));
    }

    /**
     * @inheritDoc
     */
    public function next()
    {
        if (!is_array($this->result)) {
            throw new InvalidMethodCallException('Iterator is not initialized');
        }

        return next($this->result);
    }

    /**
     * @inheritDoc
     */
    public function key()
    {
        if (!is_array($this->result)) {
            throw new InvalidMethodCallException('Iterator is not initialized');
        }

        return key($this->result);
    }

    /**
     * @inheritDoc
     */
    public function valid()
    {
        if (!is_array($this->result)) {
            throw new InvalidMethodCallException('Iterator is not initialized');
        }

        return current($this->result);
    }

    /**
     * @inheritDoc
     */
    public function rewind()
    {
        if (!is_array($this->result)) {
            $this->result = [];
            $iterator = $this->query->iterate();

            foreach ($iterator as $data) {
                $this->format->summarizeRow($data, $this->result);
            }

            $this->format->completeGrid($this->result);
        }

        reset($this->result);
    }

}