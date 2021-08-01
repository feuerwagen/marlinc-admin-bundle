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
        return [$name => $data.$this->postfix];
    }
}