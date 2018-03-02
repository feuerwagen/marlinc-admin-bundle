<?php
/**
 * Created by PhpStorm.
 * User: elias
 * Date: 12.07.17
 * Time: 10:10
 */

namespace MarlincUtils\AdminBundle\Transformer;


use MarlincUtils\AdminBundle\Export\ExportHeader;

interface HeaderTransformerInterface extends TransformerInterface
{
    /**
     * @param string $name
     * @return ExportHeader
     */
    public function getHeader(string $name);

    /**
     * @param string $font
     * @param string $color
     * @return HeaderTransformerInterface
     */
    public function setHeaderStyle(string $font, string $color);
}