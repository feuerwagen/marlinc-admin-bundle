<?php
declare(strict_types=1);

namespace Marlinc\AdminBundle\Factory;

use PhpOffice\PhpSpreadsheet\Helper\Html;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use PhpOffice\PhpSpreadsheet\Writer\IWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PhpSpreadsheetFactory
{
    const FORMAT_CSV = 'Csv';
    const FORMAT_XLSX = 'Xlsx';
    const FORMAT_XLS = 'Xls';
    const FORMAT_ODS = 'Ods';
    const FORMAT_PDF = 'Pdf'; // TODO Needs to be Tcpdf, Dompdf or Mpdf

    /**
     * Creates an empty spreadsheet object if the filename is empty, otherwise loads the file into the object.
     */
    public function createSpreadsheet(?string $filename = null): Spreadsheet
    {
        return (null === $filename) ? new Spreadsheet() : IOFactory::load($filename);
    }

    /**
     * Create a worksheet drawing.
     */
    public function createWorksheetDrawing(): Drawing
    {
        return new Drawing();
    }

    /**
     * Create a writer given the spreadsheet object and the type.
     * The type should be one of \PhpOffice\PhpSpreadsheet\IOFactory::$_autoResolveClasses
     *
     * @throws Exception
     */
    public function createWriter(Spreadsheet $object, string $type = self::FORMAT_XLS): IWriter
    {
        return IOFactory::createWriter($object, $type);
    }

    /**
     * Stream the file as Response.
     */
    public function createStreamedResponse(IWriter $writer, int $status = 200, array $headers = []): StreamedResponse
    {
        return new StreamedResponse(
            function () use ($writer) {
                $writer->save('php://output');
            },
            $status,
            $headers
        );
    }

    /**
     * Create a Helper HTML Object.
     */
    public function createHelperHTML(): Html
    {
        return new Html();
    }
}
