<?php
declare(strict_types=1);

namespace Marlinc\AdminBundle\Transformer;

use Marlinc\AdminBundle\Export\ExportColumn;

class MoneyTransformer implements TransformerInterface
{
    private int $divisor;

    private string $symbol;

    public function __construct(string $symbol = '€', int $divisor = 100)
    {
        $this->divisor = $divisor;
        $this->symbol = $symbol;
    }

    /**
     * @inheritdoc
     */
    public function transform(string $name, int $type, array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_numeric($value)) {
                $data[$key] = str_replace('.', ',', strval($value/$this->divisor)).' '.$this->symbol;
            }
        }

        if ($type == ExportColumn::TYPE_SINGLE) {
            return [$name => implode(', ', $data)];
        }

        return $data;
    }
}