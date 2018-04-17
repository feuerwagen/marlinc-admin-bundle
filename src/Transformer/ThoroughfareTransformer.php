<?php
/**
 * Created by PhpStorm.
 * User: elias
 * Date: 04.07.17
 * Time: 16:51
 */

namespace Marlinc\AdminBundle\Transformer;


class ThoroughfareTransformer implements TransformerInterface
{
    private $type;

    /**
     * ThoroughfareTransformer constructor.
     * @param $type
     */
    public function __construct($type = 'street')
    {
        $this->type = $type;
    }

    /**
     * @param string $name
     * @param int $type
     * @param array $data
     * @return mixed
     */
    public function transform(string $name, int $type, array $data)
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
                        break;
                }
            } elseif ($this->type == 'street') {
                return [$name => $element];
            }
        }

        return [$name => ''];
    }

}