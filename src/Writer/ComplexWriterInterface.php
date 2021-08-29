<?php
declare(strict_types=1);

namespace Marlinc\AdminBundle\Writer;

use Sonata\Exporter\Writer\TypedWriterInterface;

interface ComplexWriterInterface extends TypedWriterInterface
{
    const FORMAT_STRING = 0;
    const FORMAT_NUMBER = 1;
    const FORMAT_DATE = 2;
    const FORMAT_CURRENCY = 3;
    const FORMAT_LINK = 4;
    const FORMAT_EMAIL = 5;

    /**
     * Write the header rows to the spreadsheet.
     */
    public function writeHeaders(array $header): void;

    /**
     * Set the column types for the whole spreadsheet.
     *
     * @param string|array<string,string> $types
     */
    public function setColumnsType(array $types): self;

    /**
     * Determine the fitting column type.
     */
    public function getColumnType(string $column): ?string;
}