<?php
declare(strict_types=1);

namespace Marlinc\AdminBundle\Transformer;

use Marlinc\AdminBundle\Export\ExportColumn;

class DateTransformer implements TransformerInterface
{
    /**
     * @var string date() format string.
     */
    private string $format;

    public function __construct(string $format)
    {
        $this->format = $format;
    }

    /**
     * @inheritdoc
     */
    public function transform(string $name, int $type, array $data): array
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