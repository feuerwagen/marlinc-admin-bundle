<?php
declare(strict_types=1);

namespace Marlinc\AdminBundle\Transformer;

use Marlinc\AdminBundle\Export\ExportColumn;

class CallbackTransformer implements TransformerInterface
{
    private $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }


    /**
     * @inheritdoc
     */
    public function transform(string $name, int $type, array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_numeric($value)) {
                $data[$key] = ($this->callback)($value);
            }
        }

        if ($type == ExportColumn::TYPE_SINGLE) {
            return [$name => implode(', ', $data)];
        }

        return $data;
    }
}