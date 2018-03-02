<?php
/**
 * Created by PhpStorm.
 * User: elias
 * Date: 06.12.17
 * Time: 14:16
 */

namespace MarlincUtils\AdminBundle\Export;


interface SummarizingExportFormatInterface extends ExportFormatInterface
{
    /**
     * @param array|object $row
     * @param array $results
     * @return void
     */
    public function summarizeRow($row, array &$results);

    /**
     * @param array $results
     * @return void
     */
    public function completeGrid(array &$results);
}