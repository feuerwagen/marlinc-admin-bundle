<?php
/**
 * Created by PhpStorm.
 * User: elias
 * Date: 03.07.17
 * Time: 17:14
 */

namespace MarlincUtils\AdminBundle\Transformer;


class GenderTransformer implements TransformerInterface
{
    private $genders = [
        'u' => '',
        'm' => 'Herr',
        'f' => 'Frau'
    ];

    /**
     * @param string $name
     * @param int $type
     * @param array $data
     * @return mixed
     */
    public function transform(string $name, int $type, array $data)
    {
        foreach ($data as $key => $value) {
            $data[$key] = $this->genders[$value];
        }

        return [$name => implode('', $data)];
    }
}