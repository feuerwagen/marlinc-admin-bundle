<?php
/**
 * Created by PhpStorm.
 * User: elias
 * Date: 03.07.17
 * Time: 15:43
 */

namespace MarlincUtils\AdminBundle\Transformer;

use MarlincUtils\AdminBundle\Export\ExportColumn;

class DateTransformer implements TransformerInterface
{
    /**
     * @var string date() format.
     */
    private $format;

    /**
     * DateTransformer constructor.
     * @param string $format
     */
    public function __construct($format)
    {
        $this->format = $format;
    }

    /**
     * @param string $name
     * @param int $type
     * @param array $data
     * @return array
     */
    public function transform(string $name, int $type, array $data)
    {
        foreach ($data as $key => $value) {
            $data[$key] =  ($value instanceof \DateTime) ? $value->format($this->format) : $value;
        }

        if ($type == ExportColumn::TYPE_SINGLE) {
            return [$name => implode(', ', $data)];
        }

        return $data;
    }
}