<?php
declare(strict_types=1);

namespace Marlinc\AdminBundle\Source;


use Doctrine\ORM\Query;
use Marlinc\AdminBundle\Export\SummarizingExportFormatInterface;

/**
 * TODO: Find a more efficient way to aggregate (modify DQL query!?).
 * @var $format SummarizingExportFormatInterface
 */
class SummarizingSourceIterator extends ComplexStructureSourceIterator
{
    public function __construct(Query $query, SummarizingExportFormatInterface $format, int $batchSize = 100) {
        parent::__construct($query, $format, $batchSize);
    }

    /**
     * @inheritDoc
     */
    public function current()
    {
        return $this->format->getRow(current($this->results));
    }

    /**
     * @inheritDoc
     */
    public function rewind()
    {
        if (!is_array($this->results) && $this->format instanceof SummarizingExportFormatInterface) {
            $aggregated = [];

            foreach ($this->results as $data) {
                $this->format->summarizeRow($data, $aggregated);
            }

            $this->format->completeGrid($aggregated);
            $this->results = $aggregated;
        }

        parent::rewind();
    }

}