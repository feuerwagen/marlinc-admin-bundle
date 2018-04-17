<?php
/**
 * Created by PhpStorm.
 * User: elias
 * Date: 07.12.17
 * Time: 14:18
 */

namespace Marlinc\AdminBundle\Export;


use Doctrine\ORM\Query;
use Marlinc\AdminBundle\Source\SummarizingSourceIterator;

abstract class SummarizingExportFormat extends ExportFormat implements SummarizingExportFormatInterface
{
    /**
     * @inheritDoc
     */
    public function getSourceIterator(Query $query)
    {
        return new SummarizingSourceIterator($query, $this);
    }

    /**
     * @inheritDoc
     */
    public function completeGrid(array &$results)
    {
        // Do nothing per default.
    }
}