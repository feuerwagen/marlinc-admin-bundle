<?php
declare(strict_types=1);


namespace Marlinc\AdminBundle\Transformer;

/**
 * TODO: Use translatable strings and constants from UserBundle -> move to UserBundle.
 */
class GenderTransformer implements TransformerInterface
{
    private array $genders = [
        'u' => '',
        'm' => 'Herr',
        'f' => 'Frau'
    ];

    /**
     * @inheritdoc
     */
    public function transform(string $name, int $type, array $data): array
    {
        foreach ($data as $key => $value) {
            $data[$key] = $this->genders[$value];
        }

        return [$name => implode('', $data)];
    }
}