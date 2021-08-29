<?php
declare(strict_types=1);

namespace Marlinc\AdminBundle\Source;


use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Marlinc\AdminBundle\Export\ExportFormatInterface;
use Sonata\Exporter\Source\SourceIteratorInterface;

class ComplexStructureSourceIterator implements SourceIteratorInterface
{
    protected EntityManagerInterface $em;

    protected ExportFormatInterface $format;

    protected iterable $results;

    private int $batchSize;

    public function __construct(Query $query, ExportFormatInterface $format, int $batchSize = 100) {
        // We need to clone the query and reset its parameters and hints because we don't want to add the
        //  iterable hint to the original query.
        $exportQuery = clone $query;
        $exportQuery->setParameters($query->getParameters());
        foreach ($query->getHints() as $name => $value) {
            $exportQuery->setHint($name, $value);
        }

        $this->results = $exportQuery->toIterable();
        $this->em = $query->getEntityManager();
        $this->format = $format;
        $this->batchSize = $batchSize;
    }

    /**
     * @inheritdoc
     */
    public function current()
    {
        $data = $this->format->getRow($this->results->current());

        // Make sure to unload the entities after a certain batch has been read.
        if (0 === ($this->key() % $this->batchSize)) {
            $this->em->clear();
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function next()
    {
        $this->results->next();
    }

    /**
     * @inheritdoc
     */
    public function key()
    {
        return $this->results->key();
    }

    /**
     * @inheritdoc
     */
    public function valid(): bool
    {
        return $this->results->valid();
    }

    /**
     * @inheritdoc
     */
    public function rewind()
    {
        $this->results->rewind();
    }
}