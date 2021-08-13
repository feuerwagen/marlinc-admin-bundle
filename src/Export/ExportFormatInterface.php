<?php
/**
 * Created by PhpStorm.
 * User: elias
 * Date: 06.12.17
 * Time: 14:23
 */

namespace Marlinc\AdminBundle\Export;

use Doctrine\ORM\Query;
use Marlinc\AdminBundle\Transformer\TransformerInterface;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\Exporter\Source\SourceIteratorInterface;

interface ExportFormatInterface
{
    public function addColumn(string $name, int $type, TransformerInterface $transformer = null, array $fields = [''], ExportHeader $header = null, int $format = null): self;

    public function getFiletype(): string;

    public function getRow(object $currentObject): array;

    public function getHeader(): array;

    public function createPropertyAccessor(): self;

    public function getFilename(AdminInterface $admin, $filetype): string;

    public function getColumnsType(): array;

    /**
     * Let the ExportFormat decide about the SourceIterator to use.
     * This is also the place to modify the query, if needed.
     */
    public function getSourceIterator(Query $query): SourceIteratorInterface;
}