<?php
/**
 * Created by PhpStorm.
 * User: elias
 * Date: 12.09.17
 * Time: 11:02
 */

namespace Marlinc\AdminBundle\Transformer;


class PrefixedCounterTransformer implements TransformerInterface
{
    private $prefix;
    private $row;

    /**
     * PrefixedCounterTransformer constructor.
     * @param $prefix
     */
    public function __construct($prefix)
    {
        $this->prefix = $prefix;
        $this->row = 1;
    }

    /**
     * @inheritDoc
     */
    public function transform(string $name, int $type, array $data)
    {
        $result[$name] = $this->prefix.$this->row;
        $this->row++;

        return $result;
    }
}