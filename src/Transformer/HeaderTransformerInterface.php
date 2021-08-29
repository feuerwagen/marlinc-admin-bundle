<?php
declare(strict_types=1);

namespace Marlinc\AdminBundle\Transformer;


use Marlinc\AdminBundle\Export\ExportHeader;

interface HeaderTransformerInterface extends TransformerInterface
{
    public function getHeader(string $name): ExportHeader;

    public function setHeaderStyle(string $font, string $color): HeaderTransformerInterface;
}