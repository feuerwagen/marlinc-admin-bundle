<?php
/**
 * Created by PhpStorm.
 * User: elias
 * Date: 30.06.17
 * Time: 15:34
 */

namespace Marlinc\AdminBundle\Bridge;

use Marlinc\AdminBundle\Export\ExportFormat;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\Exporter\Exporter;
use Sonata\Exporter\Source\SourceIteratorInterface;
use Sonata\Exporter\Writer\TypedWriterInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AdminExporter
{
    /**
     * @var Exporter service from the exporter bundle
     */
    private $exporter;

    /**
     * @var ExportFormat[]
     */
    private $formats;

    /**
     * @var TypedWriterInterface[]
     */
    private $writers;

    /**
     * @param Exporter $exporter will be used to get global settings
     * @param array $writers
     */
    public function __construct(Exporter $exporter, array $writers = [])
    {
        $this->exporter = $exporter;
        $this->formats = [];
        $this->writers = [];

        foreach ($writers as $writer) {
            $this->addWriter($writer);
        }
    }

    /**
     * The main benefit of this method is the type hinting.
     *
     * @param ExportFormat $format a possible format for exporting data
     */
    public function addFormat(ExportFormat $format, $name, $class)
    {
        $this->formats[$class][$name] = $format;
    }

    /**
     * The main benefit of this method is the type hinting.
     *
     * @param TypedWriterInterface $writer a possible writer for exporting data
     */
    public function addWriter(TypedWriterInterface $writer)
    {
        $this->writers[$writer->getFormat()] = $writer;
    }

    /**
     * Queries an admin for its default export formats, and falls back on global settings.
     *
     * @param AdminInterface $admin the current admin object
     * @return ExportFormat[] an array of formats
     */
    public function getAvailableFormats(AdminInterface $admin)
    {
        $class = $admin->getClass();

        if (array_key_exists($class, $this->formats)) {
            return array_keys($this->writers);
        }

        return [];
    }

    /**
     * Returns a simple array of export formats.
     *
     * @return string[] writer formats as returned by the TypedWriterInterface::getFormat() method
     */
    public function getAvailableFileTypes()
    {
        return array_keys($this->writers);
    }

    /**
     * Builds an export filename from the class associated with the provided admin,
     * the current date, and the provided format.
     *
     * @param AdminInterface $admin the current admin object
     * @param ExportFormat $format the format of the export file
     * @param string $filetype the requested file type
     * @return string
     * @throws \RuntimeException If the export file format is invalid
     */
    public function getExportFilename(AdminInterface $admin, ExportFormat $format, $filetype)
    {
        return $format->getFilename($admin, $filetype);
    }

    /**
     * @param AdminInterface $admin
     * @param string $format
     * @return ExportFormat|null
     * @throws \RuntimeException If the export format is invalid
     */
    public function getExportFormat(AdminInterface $admin, $format) {
        $class = $admin->getClass();

        if (array_key_exists($class, $this->formats) && array_key_exists($format, $this->formats[$class])) {
            return $this->formats[$class][$format];
        } else {
            throw new \RuntimeException(
                sprintf(
                    'Export in format `%s` is not allowed for class: `%s`.',
                    $format,
                    $class
                )
            );
        }
    }

    /**
     * @throws \RuntimeException
     *
     * @param string $filetype
     * @param string $filename
     * @param ExportFormat $format
     * @param SourceIteratorInterface $source
     *
     * @return StreamedResponse
     */
    public function getResponse($filetype, $filename, ExportFormat $format, SourceIteratorInterface $source)
    {
        if (!array_key_exists($filetype, $this->writers)) {
            throw new \RuntimeException(sprintf(
                'Invalid "%s" format, supported formats are : "%s"',
                $filetype,
                implode(', ', array_keys($this->writers))
            ));
        }
        $writer = $this->writers[$filetype];

        $callback = function () use ($source, $writer, $format) {
            $handler = ExportHandler::create($source, $format, $writer);
            $handler->export();
        };

        $headers = array(
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        );

        $headers['Content-Type'] = $writer->getDefaultMimeType();

        return new StreamedResponse($callback, 200, $headers);
    }
}