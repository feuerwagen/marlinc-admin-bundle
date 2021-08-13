<?php


namespace Marlinc\AdminBundle\Transformer;


class PostfixTransformer implements TransformerInterface
{
    private string $postfix;

    public function __construct(string $postfix)
    {
        $this->postfix = $postfix;
    }

    /**
     * @inheritDoc
     */
    public function transform(string $name, int $type, array $data)
    {
        foreach ($data as $key => $value) {
            $data[$key] = $value.$this->postfix;
        }

        return [$name => implode('', $data)];
    }
}