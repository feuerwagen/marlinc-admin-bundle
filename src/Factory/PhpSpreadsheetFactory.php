<?php

namespace Marlinc\AdminBundle\Factory;

use PhpOffice\PhpSpreadsheet\Helper\Html;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\IWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PhpSpreadsheetFactory
{
    const FROMAT_CSV = 'Csv';
    const FORMAT_XLSX = 'Xlsx';
    const FORMAT_XLS = 'Xls';
    const FORMAT_ODS = 'Ods';
    const FORMAT_PDF = 'Pdf';

    /**
     * Creates an empty spreadsheet object if the filename is empty, otherwise loads the file into the object.
     *
     * @param string $filename
     * @return Spreadsheet
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function createSpreadsheet($filename = null)
    {
        return (null === $filename) ? new Spreadsheet() : IOFactory::load($filename);
    }

    /**
     * Create a worksheet drawing
     * @return Drawing
     */
    public function createWorksheetDrawing()
    {
        return new Drawing();
    }

    /**
     * Create a writer given the spreadsheet object and the type.
     * The type should be one of \PhpOffice\PhpSpreadsheet\IOFactory::$_autoResolveClasses
     *
     * @param Spreadsheet $object
     * @param string $type
     * @return IWriter
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function createWriter(Spreadsheet $object, $type = self::FORMAT_XLS)
    {
        return IOFactory::createWriter($object, $type);
    }

    /**
     * Stream the file as Response.
     *
     * @param IWriter $writer
     * @param int $status
     * @param array $headers
     * @return StreamedResponse
     */
    public function createStreamedResponse(IWriter $writer, $status = 200, $headers = array())
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
     * Create a Helper HTML Object
     *
     * @return Html
     */
    public function createHelperHTML()
    {
        return new Html();
    }
}
