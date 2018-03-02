<?php
/**
 * Created by PhpStorm.
 * User: elias
 * Date: 29.06.17
 * Time: 16:32
 */

namespace MarlincUtils\AdminBundle\Transformer;


interface TransformerInterface
{
    /**
     * @param string $name
     * @param int $type
     * @param array $data
     * @return array
     */
    public function transform(string $name, int $type, array $data);
}