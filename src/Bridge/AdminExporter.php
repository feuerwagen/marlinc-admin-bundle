<?php
declare(strict_types=1);

namespace Marlinc\AdminBundle\Bridge;

use Marlinc\AdminBundle\Export\ExportFormat;
use Marlinc\AdminBundle\Writer\ComplexWriterInterface;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\Exporter\Source\SourceIteratorInterface;
use Sonata\Exporter\Writer\TypedWriterInterface;
use Sonata\Exporter\Writer\WriterInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AdminExporter
{
    /**
     * The available export formats, keyed by the entity class name they apply to and then their name.
     *
     * @var array<string,array<string,ExportFormat>>
     */
    private array $formats;

    /**
     * @var TypedWriterInterface[]
     */
    private array $writers;

    public function __construct(array $writers = [])
    {
        $this->formats = [];
        $this->writers = [];

        foreach ($writers as $writer) {
            $this->addWriter($writer);
        }
    }

    /**
     * Register a new export format.
     *
     * @param ExportFormat $format A possible format for exporting data.
     * @param string $name The name of the format (used as a translation key).
     * @param string $class The fully qualified class name of the entity the format applies to.
     */
    public function addFormat(ExportFormat $format, string $name, string $class)
    {
        $this->formats[$class][$name] = $format;
    }

    /**
     * Register a new writer for exporting data.
     */
    public function addWriter(TypedWriterInterface $writer)
    {
        $this->writers[$writer->getFormat()] = $writer;
    }

    /**
     * Queries an admin for its default export formats, and falls back on global settings.
     *
     * @param AdminInterface $admin the current admin object
     * @return string[] an array of format identifiers
     */
    public function getAvailableFormats(AdminInterface $admin): array
    {
        $class = $admin->getClass();

        if (array_key_exists($class, $this->formats)) {
            return array_keys($this->formats[$class]);
        }

        return [];
    }

    /**
     * Returns an array of available export file types.
     *
     * @return string[] writer formats as returned by the TypedWriterInterface::getFormat() method
     */
    public function getAvailableFileTypes(): array
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
     *
     * @return string The generated filename
     * @throws \RuntimeException If the export file format is invalid
     */
    public function getExportFilename(AdminInterface $admin, ExportFormat $format, string $filetype): string
    {
        return $format->getFilename($admin, $filetype);
    }

    /**
     * Get the real export format from its name.
     *
     * @param AdminInterface $admin The admin class handling the current entity to be exported.
     * @param string $format The name of the export format.
     *
     * @throws \RuntimeException If the export format is invalid
     */
    public function getExportFormat(AdminInterface $admin, string $format): ExportFormat
    {
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
     * Generate the response (streamed file) for the requested export.
     */
    public function getResponse(string $filetype, string $filename, ExportFormat $format, SourceIteratorInterface $source): StreamedResponse
    {
        if (!array_key_exists($filetype, $this->writers)) {
            throw new \RuntimeException(sprintf(
                'Invalid "%s" format, supported formats are : "%s"',
                $filetype,
                implode(', ', array_keys($this->writers))
            ));
        }
        $writer = $this->writers[$filetype];

        $callback = function() use ($source, $writer, $format) {
            $this->export($source, $format, $writer);
        };

        $headers = [
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
            'Content-Type' => $writer->getDefaultMimeType()
        ];

        return new StreamedResponse($callback, 200, $headers);
    }

    /**
     * Generate the export stream.
     *
     * @throws \Sonata\Exporter\Exception\SonataExporterException
     */
    private function export(SourceIteratorInterface $source, ExportFormat $format, WriterInterface $writer): void
    {
        $writer->open();
        $typesWritten = false;

        foreach ($source as $data) {
            if ($writer instanceof ComplexWriterInterface && !$typesWritten) {
                $writer->writeHeaders($format->getHeader());
                $writer->setColumnsType($format->getColumnsType());
                $typesWritten = true;
            }
            $writer->write($data);
        }

        $writer->close();
    }
}