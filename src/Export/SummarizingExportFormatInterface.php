<?php
namespace Marlinc\AdminBundle\Export;


/**
 * This special export interface defines an export that is not just returning a single entity per row.
 * Instead, all the entity values get aggregated first.
 */
interface SummarizingExportFormatInterface extends ExportFormatInterface
{
    /**
     * @param array|object $row
     * @param array $results
     * @return void
     */
    public function summarizeRow($row, array &$results): void;

    /**
     * @param array $results
     * @return void
     */
    public function completeGrid(array &$results): void;
}