<?php
/**
 * Created by PhpStorm.
 * User: elias
 * Date: 12.07.17
 * Time: 11:38
 */

namespace Marlinc\AdminBundle\Transformer;


abstract class AbstractHeaderTransformer implements HeaderTransformerInterface
{
    protected $style = [
        'color' => null,
        'font' => 'b'
    ];

    /**
     * {@inheritdoc}
     */
    public function setHeaderStyle(string $font, string $color)
    {
        $this->style = [
            'font' => $font,
            'color' => $color
        ];

        return $this;
    }
}