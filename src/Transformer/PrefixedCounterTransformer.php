<?php
declare(strict_types=1);


namespace Marlinc\AdminBundle\Transformer;


class PrefixedCounterTransformer implements TransformerInterface
{
    private string $prefix;
    private int $row;

    public function __construct(string $prefix)
    {
        $this->prefix = $prefix;
        $this->row = 1;
    }

    /**
     * @inheritdoc
     */
    public function transform(string $name, int $type, array $data): array
    {
        $result[$name] = $this->prefix.$this->row;
        $this->row++;

        return $result;
    }
}