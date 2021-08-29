<?php
declare(strict_types=1);

namespace Marlinc\AdminBundle\Transformer;


abstract class AbstractHeaderTransformer implements HeaderTransformerInterface
{
    protected array $style = [
        'color' => null,
        'font' => 'b'
    ];

    /**
     * {@inheritdoc}
     */
    public function setHeaderStyle(string $font, string $color): self
    {
        $this->style = [
            'font' => $font,
            'color' => $color
        ];

        return $this;
    }
}