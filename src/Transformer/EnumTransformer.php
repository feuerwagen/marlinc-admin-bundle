<?php
/**
 * Created by PhpStorm.
 * User: elias
 * Date: 03.07.17
 * Time: 17:14
 */

namespace Marlinc\AdminBundle\Transformer;

class EnumTransformer implements TransformerInterface
{
    private $choices;

    /**
     * EnumTransformer constructor.
     * @param $choices
     */
    public function __construct($choices)
    {
        $this->choices = $choices;
    }

    /**
     * @param string $name
     * @param int $type
     * @param array $data
     * @return mixed
     */
    public function transform(string $name, int $type, array $data)
    {
        foreach ($data as $key => $value) {
            $data[$key] = $this->choices[$value];
        }

        return [$name => implode('', $data)];
    }
}