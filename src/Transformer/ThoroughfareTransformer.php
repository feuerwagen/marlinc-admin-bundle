<?php
declare(strict_types=1);

namespace Marlinc\AdminBundle\Transformer;


class ThoroughfareTransformer implements TransformerInterface
{
    /**
     * TODO: Replace with class constants.
     * @var string|mixed
     */
    private string $type;

    /**
     * ThoroughfareTransformer constructor.
     * @param $type
     */
    public function __construct(string $type = 'street')
    {
        $this->type = $type;
    }

    /**
     * @inheritdoc
     */
    public function transform(string $name, int $type, array $data): array
    {
        foreach ($data as $element) {
            if (preg_match('/([^\d]+)\s?(.+)/i', $element, $result)) {
                switch ($this->type) {
                    case 'nr':
                        return [$name => $result[2]];
                        break;
                    case 'street':
                    default:
                        return [$name => $result[1]];
                }
            } elseif ($this->type == 'street') {
                return [$name => $element];
            }
        }

        return [$name => ''];
    }

}