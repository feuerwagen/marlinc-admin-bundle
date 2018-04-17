<?php
/**
 * Created by PhpStorm.
 * User: elias
 * Date: 03.07.17
 * Time: 17:14
 */

namespace Marlinc\AdminBundle\Transformer;

/**
 * Class GenderTransformer
 * TODO: Use translatable strings and contstants from UserBundle -> move to UserBundle.
 *
 * @package Marlinc\AdminBundle\Transformer
 */
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