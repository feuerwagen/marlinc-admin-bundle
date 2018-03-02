<?php
/**
 * Created by PhpStorm.
 * User: elias
 * Date: 29.06.17
 * Time: 14:30
 */

namespace MarlincUtils\AdminBundle\Writer;

use Exporter\Writer\TypedWriterInterface;

interface ComplexWriterInterface extends TypedWriterInterface
{
    const FORMAT_STRING = 0;
    const FORMAT_NUMBER = 1;
    const FORMAT_DATE = 2;
    const FORMAT_CURRENCY = 3;
    const FORMAT_LINK = 4;
    const FORMAT_EMAIL = 5;

    public function writeHeaders(array $header);

    public function setColumnsType(array $formats);

    public function getColumnType($column);
}