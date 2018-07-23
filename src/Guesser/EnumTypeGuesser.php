<?php
/**
 * Created by PhpStorm.
 * User: em
 * Date: 23.07.18
 * Time: 11:13
 */

namespace Marlinc\AdminBundle\Guesser;


use Sonata\AdminBundle\Model\ModelManagerInterface;
use Sonata\DoctrineORMAdminBundle\Guesser\AbstractTypeGuesser;
use Symfony\Component\Form\Guess\Guess;
use Symfony\Component\Form\Guess\TypeGuess;

class EnumTypeGuesser extends AbstractTypeGuesser
{
    /**
     * @inheritDoc
     */
    public function guessType($class, $property, ModelManagerInterface $modelManager)
    {
        if (!$ret = $this->getParentMetadataForProperty($class, $property, $modelManager)) {
            return new TypeGuess('text', [], Guess::LOW_CONFIDENCE);
        }

        dump($ret);
        // TODO: Implement guessType() method.
    }
}