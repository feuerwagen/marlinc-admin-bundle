<?php
declare(strict_types=1);

namespace Marlinc\AdminBundle\Export;


use Doctrine\ORM\Query;
use Marlinc\AdminBundle\Source\SummarizingSourceIterator;
use Sonata\Exporter\Source\SourceIteratorInterface;


abstract class SummarizingExportFormat extends ExportFormat implements SummarizingExportFormatInterface
{
    /**
     * @inheritDoc
     */
    public function getSourceIterator(Query $query): SourceIteratorInterface
    {
        return new SummarizingSourceIterator($query, $this);
    }

    /**
     * @inheritDoc
     */
    public function completeGrid(array &$results): void
    {
        // Do nothing per default.
    }
}