<?php
/**
 * Created by PhpStorm.
 * User: elias
 * Date: 17.03.17
 * Time: 16:51
 */

namespace Marlinc\AdminBundle\Writer;

use Liuggio\ExcelBundle\Factory;
use PHPExcel_Cell;
use PHPExcel_Cell_DataType;
use PHPExcel_Exception;
use PHPExcel_Style_Color;
use PHPExcel_Style_Fill;
use PHPExcel_Worksheet;

class XmlExcelWriter implements ComplexWriterInterface
{
    static $typeMap = [
        ComplexWriterInterface::FORMAT_STRING => PHPExcel_Cell_DataType::TYPE_STRING,
        ComplexWriterInterface::FORMAT_NUMBER => PHPExcel_Cell_DataType::TYPE_NUMERIC,
        ComplexWriterInterface::FORMAT_DATE => null,
        ComplexWriterInterface::FORMAT_CURRENCY => null,
        ComplexWriterInterface::FORMAT_LINK => PHPExcel_Cell_DataType::TYPE_STRING,
        ComplexWriterInterface::FORMAT_EMAIL => PHPExcel_Cell_DataType::TYPE_STRING
    ];

    /**
     * @var string|null
     */
    protected $filename = null;

    /**
     * @var resource|null
     */
    protected $file = null;

    /**
     * @var bool
     */
    protected $showHeaders;

    /**
     * @var mixed|null
     */
    protected $columnsType = null;

    /**
     * @var int
     */
    protected $position = 1;

    /**
     * @var string
     */
    protected $singleHeaders = 'A1';

    /**
     * @var \PHPExcel
     */
    private $phpExcel;

    /**
     * @var Factory
     */
    private $phpExcelFactory;

    /**
     * @param Factory $phpexcel
     * @param string $filename
     * @param bool $showHeaders
     * @param mixed $columnsType Define cells type to use
     *                            If string: force all cells to the given type. e.g: 'Number'
     *                            If array: force only given cells. e.g: array('ean'=>'String', 'price'=>'Number')
     *                            If null: will guess the type. 'Number' if value is numeric, 'String' otherwise
     */
    public function __construct($phpexcel, $filename, $showHeaders = true, $columnsType = null)
    {
        $this->filename = $filename;
        $this->showHeaders = $showHeaders;
        $this->columnsType = $columnsType;
        $this->phpExcelFactory = $phpexcel;
    }

    public function setColumnsType(array $formats)
    {
        $this->columnsType = $formats;
    }

    public function getColumnType($column)
    {
        if (is_string($this->columnsType)) {
            return self::$typeMap[$this->columnsType];
        } elseif (is_array($this->columnsType)
            && array_key_exists($column, $this->columnsType)
            && array_key_exists($this->columnsType[$column], self::$typeMap)) {
            return self::$typeMap[$this->columnsType[$column]];
        }
        return null;
    }

    public function open()
    {
        $this->phpExcel = $this->phpExcelFactory->createPHPExcelObject();
        $this->phpExcel->setActiveSheetIndex(0);
        $this->phpExcel->getActiveSheet()->setTitle('Export');
    }

    public function writeHeaders(array $header)
    {
        $sheet = $this->phpExcel->getActiveSheet();

        foreach ($header as $row) {
            $col = 0; // Col start with 0, Rows with 1

            foreach ($row as $cell) {
                // Write header cell.
                $sheet->setCellValueByColumnAndRow($col, $this->position, $cell['name']);

                // Cell styling.
                $style = $sheet->getStyleByColumnAndRow($col, $this->position);

                switch ($cell['font']) {
                    case 'b':
                        $style->getFont()->setBold(true);
                        break;
                    case 'i':
                        $style->getFont()->setItalic(true);
                        break;
                    case 'bi':
                        $style->getFont()->setItalic(true)->setBold(true);
                    default:
                }

                if ($cell['color'] != null) {
                    $style->getFill()
                        ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
                        ->getStartColor()->setRGB(str_replace('#', '', $cell['color']));

                    $hsl = $this->HTMLToHSL($cell['color']);
                    if ($hsl->lightness < 200) {
                        $style->getFont()->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_WHITE));
                    }
                }

                // Cell comments.
                if ($cell['comment'] != '') {
                    $sheet->getCommentByColumnAndRow($col, $this->position)->setAuthor('Marlinc');
                    $sheet->getCommentByColumnAndRow($col, $this->position)->getText()->createTextRun($cell['comment']);
                }

                // Merge cells and progress counter if necessary.
                if (array_key_exists('colspan', $cell) && $cell['colspan'] > 1) {
                    $sheet->mergeCellsByColumnAndRow($col, $this->position, $col + $cell['colspan'] - 1, $this->position);
                    $col += $cell['colspan'];
                } else {
                    $col++;
                }
            }

            // Advance row pointer.
            $this->position++;
        }

        // Set freeze plane below header rows.
        $sheet->freezePaneByColumnAndRow(0, $this->position);
        $this->singleHeaders = 'A' . ($this->position - 1);

        // Prevent the default simple header generating code from activating.
        // That code might still be needed for some exports though.
        $this->showHeaders = false;
    }

    /**
     * @param array $data
     */
    public function write(array $data)
    {
        $sheet = $this->phpExcel->getActiveSheet();

        // Write headers, if on first data row and no headers yet.
        if ($this->position == 1 && $this->showHeaders) {
            $header = array_keys($data);
            foreach ($header as $key => $value) {
                $sheet->setCellValueByColumnAndRow($key, $this->position, $value);
                $sheet->getStyleByColumnAndRow($key, $this->position)->getFont()->setBold(true);
            }
            $sheet->freezePane('A2');
            $this->position++;
        }

        $this->fromArray($sheet, $data, null, 'A' . $this->position);

        // Did we have inserted multiple rows at once?
        if ($this->countdim($data) == 2) {
            $this->position += count($data);
        } else {
            $this->position++;
        }
    }

    private function countdim($array)
    {
        if (is_array(reset($array)))
        {
            $return = $this->countdim(reset($array)) + 1;
        }

        else
        {
            $return = 1;
        }

        return $return;
    }

    public function close()
    {
        $sheet = $this->phpExcel->getActiveSheet();

        // Autofilter, one row above freeze pane
        $dim = str_replace('A1', $this->singleHeaders, $sheet->calculateWorksheetDimension());
        $sheet->setAutoFilter($dim);

        // Set column width to match content.
        $cellIterator = $sheet->getRowIterator()->current()->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(true);
        foreach ($cellIterator as $cell) {
            $sheet->getColumnDimension($cell->getColumn())->setAutoSize(true);
        }

        $writer = $this->phpExcelFactory->createWriter($this->phpExcel, 'Excel2007');
        $writer->save($this->filename);
    }

    /**
     * @inheritdoc
     */
    public function getDefaultMimeType()
    {
        return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    }

    /**
     * @inheritdoc
     */
    public function getFormat()
    {
        return 'xlsx';
    }

    /**
     * @param PHPExcel_Worksheet $sheet
     * @param null $source
     * @param null $nullValue
     * @param string $startCell
     * @param bool $strictNullComparison
     * @throws PHPExcel_Exception
     */
    private function fromArray(PHPExcel_Worksheet $sheet, $source = null, $nullValue = null, $startCell = 'A1', $strictNullComparison = false)
    {
        if (is_array($source)) {
            // Convert a 1-D array to 2-D (for ease of looping)
            if (!is_array(end($source))) {
                $source = array($source);
            }

            // start coordinate
            list ($startColumn, $startRow) = PHPExcel_Cell::coordinateFromString($startCell);

            // Loop through $source
            foreach ($source as $rowData) {
                $currentColumn = $startColumn;
                foreach ($rowData as $key => $cellValue) {
                    if (($strictNullComparison && $cellValue !== $nullValue) || $cellValue != $nullValue) {
                        // Set cell value
                        $type = $this->getColumnType($key);
                        if ($type != null) {
                            $sheet->getCell($currentColumn . $startRow)->setValueExplicit($cellValue, $type);
                        } else {
                            $sheet->getCell($currentColumn . $startRow)->setValue($cellValue);
                        }
                    }
                    ++$currentColumn;
                }
                ++$startRow;
            }
        } else {
            throw new PHPExcel_Exception("Parameter \$source should be an array.");
        }
    }

    private function HTMLToHSL($htmlCode) {
        return $this->RGBToHSL($this->HTMLToRGB($htmlCode));
    }

    private function HTMLToRGB($htmlCode)
    {
        if($htmlCode[0] == '#')
            $htmlCode = substr($htmlCode, 1);

        if (strlen($htmlCode) == 3)
        {
            $htmlCode = $htmlCode[0] . $htmlCode[0] . $htmlCode[1] . $htmlCode[1] . $htmlCode[2] . $htmlCode[2];
        }

        $r = hexdec($htmlCode[0] . $htmlCode[1]);
        $g = hexdec($htmlCode[2] . $htmlCode[3]);
        $b = hexdec($htmlCode[4] . $htmlCode[5]);

        return $b + ($g << 0x8) + ($r << 0x10);
    }

    private function RGBToHSL($RGB) {
        $r = 0xFF & ($RGB >> 0x10);
        $g = 0xFF & ($RGB >> 0x8);
        $b = 0xFF & $RGB;

        $r = ((float)$r) / 255.0;
        $g = ((float)$g) / 255.0;
        $b = ((float)$b) / 255.0;

        $maxC = max($r, $g, $b);
        $minC = min($r, $g, $b);

        $l = ($maxC + $minC) / 2.0;

        if($maxC == $minC)
        {
            $s = 0;
            $h = 0;
        }
        else
        {
            if($l < .5)
            {
                $s = ($maxC - $minC) / ($maxC + $minC);
            }
            else
            {
                $s = ($maxC - $minC) / (2.0 - $maxC - $minC);
            }
            if($r == $maxC)
                $h = ($g - $b) / ($maxC - $minC);
            if($g == $maxC)
                $h = 2.0 + ($b - $r) / ($maxC - $minC);
            if($b == $maxC)
                $h = 4.0 + ($r - $g) / ($maxC - $minC);

            $h = $h / 6.0;
        }

        $h = (int)round(255.0 * $h);
        $s = (int)round(255.0 * $s);
        $l = (int)round(255.0 * $l);

        return (object) Array('hue' => $h, 'saturation' => $s, 'lightness' => $l);
    }
}