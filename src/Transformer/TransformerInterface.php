<?php
declare(strict_types=1);


namespace Marlinc\AdminBundle\Transformer;


interface TransformerInterface
{
    public function transform(string $name, int $type, array $data): array;
}