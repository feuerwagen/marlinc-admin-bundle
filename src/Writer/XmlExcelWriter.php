<?php
declare(strict_types=1);


namespace Marlinc\AdminBundle\Writer;

use Marlinc\AdminBundle\Color\HslColor;
use Marlinc\AdminBundle\Factory\PhpSpreadsheetFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class XmlExcelWriter implements ComplexWriterInterface
{
    static array $typeMap = [
        ComplexWriterInterface::FORMAT_STRING => DataType::TYPE_STRING,
        ComplexWriterInterface::FORMAT_NUMBER => DataType::TYPE_NUMERIC,
        ComplexWriterInterface::FORMAT_DATE => null,
        ComplexWriterInterface::FORMAT_CURRENCY => null,
        ComplexWriterInterface::FORMAT_LINK => DataType::TYPE_STRING,
        ComplexWriterInterface::FORMAT_EMAIL => DataType::TYPE_STRING
    ];

    protected string $filename;

    /**
     * @var resource|null
     */
    protected $file = null;

    /**
     * @var bool
     */
    protected bool $showHeaders;

    /**
     * @var string|array<string,string>|null Define cells type to use
     *           If string: force all cells to the given type. e.g: 'Number'
     *           If array: force only given cells. e.g: array('ean'=>'String', 'price'=>'Number')
     *           If null: will guess the type. 'Number' if value is numeric, 'String' otherwise
     */
    protected $columnsType = null;

    protected int $position = 1;

    protected string $singleHeaders = 'A1';

    private ?Spreadsheet $spreadsheet = null;

    private PhpSpreadsheetFactory $factory;

    public function __construct(PhpSpreadsheetFactory $factory, string $filename, bool $showHeaders = true, $columnsType = null)
    {
        $this->filename = $filename;
        $this->showHeaders = $showHeaders;
        $this->columnsType = $columnsType;
        $this->factory = $factory;
    }

    /**
     * @inheritdoc
     */
    public function setColumnsType($types): self
    {
        $this->columnsType = $types;
    }

    /**
     * @inheritdoc
     */
    public function getColumnType(string $column): ?string
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

    /**
     * Create a new spreadsheet and prepare the active sheet.
     *
     * @throws Exception
     */
    public function open(): void
    {
        $this->spreadsheet = $this->factory->createSpreadsheet();
        $this->spreadsheet
            ->setActiveSheetIndex(0)
            ->setTitle('Export');
    }

    /**
     * @inheritdoc
     * @throws Exception
     */
    public function writeHeaders(array $header): void
    {
        $sheet = $this->spreadsheet->getActiveSheet();

        foreach ($header as $row) {
            $col = 1; // Cols & Rows start with 1

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
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB(str_replace('#', '', $cell['color']));

                    // Change text color for dark backgrounds.
                    $hsl = HslColor::fromHex($cell['color']);
                    if ($hsl->lightness < 200) {
                        $style->getFont()->setColor(new Color(Color::COLOR_WHITE));
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
        $sheet->freezePane('A'.$this->position);
        $this->singleHeaders = 'A'.($this->position - 1);

        // Prevent the default simple header generating code from activating.
        // That code might still be needed for some exports though.
        $this->showHeaders = false;
    }

    /**
     * Write the data rows to the spreadsheet.
     *
     * @throws Exception
     */
    public function write(array $data): void
    {
        $sheet = $this->spreadsheet->getActiveSheet();

        // Write headers, if on first data row and no headers yet.
        if ($this->position == 1 && $this->showHeaders) {
            $header = array_keys($data);
            foreach ($header as $key => $value) {
                $sheet->setCellValueByColumnAndRow($key+1, $this->position, $value);
                $sheet->getStyleByColumnAndRow($key+1, $this->position)->getFont()->setBold(true);
            }
            $sheet->freezePane('A2');
            $this->position++;
        }

        // Write data set (normally one, maybe multiple rows).
        $this->fromArray($sheet, $data,  'A' . $this->position);

        // Did we have inserted multiple rows at once? True, if the array is two-dimensional.
        $this->position += ($this->countArrayDimensions($data) == 2) ? count($data) : 1;
    }

    /**
     * Finish generation of the spreadsheet.
     *
     * @throws Exception
     */
    public function close(): void
    {
        $sheet = $this->spreadsheet->getActiveSheet();

        // Autofilter, one row above freeze pane
        $dim = str_replace('A1', $this->singleHeaders, $sheet->calculateWorksheetDimension());
        $sheet->setAutoFilter($dim);

        // Set column width to match content.
        $cellIterator = $sheet->getRowIterator()->current()->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(true);
        foreach ($cellIterator as $cell) {
            $sheet->getColumnDimension($cell->getColumn())->setAutoSize(true);
        }

        $writer = $this->factory->createWriter($this->spreadsheet, $this->factory::FORMAT_XLSX);
        $writer->save($this->filename);
    }

    /**
     * @inheritdoc
     */
    public function getDefaultMimeType(): string
    {
        return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    }

    /**
     * @inheritdoc
     */
    public function getFormat(): string
    {
        return 'xlsx';
    }

    /**
     * Write the data in a given array to spreadsheet cells.
     *
     * @param Worksheet $sheet The spreadsheet.
     * @param array $source The data array.
     * @param string $startCell The cell to start at.
     * @throws Exception
     */
    private function fromArray(Worksheet $sheet, array $source, string $startCell = 'A1'): void
    {
        // Convert a 1-D array to 2-D (for ease of looping)
        if (!is_array(end($source))) {
            $source = [$source];
        }

        // start coordinate
        list ($startColumn, $startRow) = Coordinate::coordinateFromString($startCell);

        // Loop through $source
        foreach ($source as $rowData) {
            $currentColumn = $startColumn;
            foreach ($rowData as $key => $cellValue) {
                if ($cellValue != null) {
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
    }

    private function countArrayDimensions(array $array): int
    {
        return is_array(reset($array)) ? $this->countArrayDimensions(reset($array)) + 1 : 1;
    }
}