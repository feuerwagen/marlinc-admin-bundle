<?php
declare(strict_types=1);

namespace Marlinc\AdminBundle\Export;

use Doctrine\ORM\Query;
use Marlinc\AdminBundle\Transformer\TransformerInterface;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\Exporter\Source\SourceIteratorInterface;

interface ExportFormatInterface
{
    /**
     * Add a column to the export format. Each column must have
     * - a name (used as a header)
     * - a type (defining how the column will be rendered)
     */
    public function addColumn(string $name, int $type, ?TransformerInterface $transformer = null, array $fields = [''], ?ExportHeader $header = null, ?int $format = null): self;

    public function getFiletype(): string;

    public function getRow(object $currentObject): array;

    public function getHeader(): array;

    public function getFilename(AdminInterface $admin, $filetype): string;

    public function getColumnsType(): array;

    /**
     * Let the ExportFormat decide about the SourceIterator to use.
     * This is also the place to modify the query, if needed.
     */
    public function getSourceIterator(Query $query): SourceIteratorInterface;
}