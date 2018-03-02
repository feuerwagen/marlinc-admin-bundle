<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MarlincUtils\AdminBundle\Bridge;

use Exporter\Source\SourceIteratorInterface;
use Exporter\Writer\WriterInterface;
use MarlincUtils\AdminBundle\Export\ExportFormat;
use MarlincUtils\AdminBundle\Writer\ComplexWriterInterface;

class ExportHandler
{
    /**
     * @var SourceIteratorInterface
     */
    protected $source;

    /**
     * @var WriterInterface
     */
    protected $writer;

    /**
     * @var ExportFormat
     */
    protected $format;

    /**
     * @param SourceIteratorInterface $source
     * @param ExportFormat $format
     * @param WriterInterface $writer
     */
    public function __construct(SourceIteratorInterface $source, ExportFormat $format, WriterInterface $writer)
    {
        $this->source = $source;
        $this->writer = $writer;
        $this->format = $format;
    }

    public function export()
    {
        $this->writer->open();
        $typesWritten = false;

        foreach ($this->source as $data) {
            if ($this->writer instanceof ComplexWriterInterface && !$typesWritten) {
                $this->writer->writeHeaders($this->format->getHeader());
                $this->writer->setColumnsType($this->format->getColumnsType());
                $typesWritten = true;
            }
            $this->writer->write($data);
        }

        $this->writer->close();
    }

    /**
     * @param SourceIteratorInterface $source
     * @param ExportFormat $format
     * @param WriterInterface $writer
     *
     * @return ExportHandler
     */
    public static function create(SourceIteratorInterface $source, ExportFormat $format, WriterInterface $writer)
    {
        return new self($source, $format, $writer);
    }
}
