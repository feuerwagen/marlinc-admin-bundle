<?php
declare(strict_types=1);

namespace Marlinc\AdminBundle\Transformer;

use Marlinc\AdminBundle\Export\ExportColumn;

/**
 * TODO: Rename to ReadableLabelTransformer
 */
class EnumTransformer implements TransformerInterface
{
    /**
     * @var array A map of value/name pairs.
     */
    private array $choices;

    public function __construct(array $choices)
    {
        $this->choices = $choices;
    }

    /**
     * @inheritdoc
     */
    public function transform(string $name, int $type, array $data): array
    {
        foreach ($data as $key => $value) {
            $data[$key] = (array_key_exists($value, $this->choices)) ? $this->choices[$value] : $value;
        }

        if ($type == ExportColumn::TYPE_SINGLE) {
            return [$name => implode(', ', $data)];
        }

        return $data;
    }
}