<?php
/**
 * Created by PhpStorm.
 * User: em
 * Date: 23.07.18
 * Time: 11:13
 */

namespace Marlinc\AdminBundle\Guesser;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;
use Fresh\DoctrineEnumBundle\Exception\EnumType\EnumTypeIsRegisteredButClassDoesNotExistException;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\FieldDescription\TypeGuesserInterface;
use Symfony\Component\Form\Guess\Guess;
use Symfony\Component\Form\Guess\TypeGuess;

class EnumTypeGuesser implements TypeGuesserInterface
{
    /**
     * @var array Array of registered doctrine types
     */
    protected array $registeredTypes = [];

    public function __construct(array $registeredTypes)
    {
        foreach ($registeredTypes as $type => $details) {
            $this->registeredTypes[$type] = $details['class'];
        }
    }

    public function guess(FieldDescriptionInterface $fieldDescription): ?TypeGuess
    {
        $fieldType = $fieldDescription->getMappingType();

        // This is not one of the registered ENUM types
        if (!isset($this->registeredTypes[$fieldType])) {
            return null;
        }

        $registeredEnumTypeFQCN = $this->registeredTypes[$fieldType];

        if (!\class_exists($registeredEnumTypeFQCN)) {
            throw new EnumTypeIsRegisteredButClassDoesNotExistException(\sprintf(
                'ENUM type "%s" is registered as "%s", but that class does not exist',
                $fieldType,
                $registeredEnumTypeFQCN
            ));
        }

        if (!\is_subclass_of($registeredEnumTypeFQCN, AbstractEnumType::class)) {
            return null;
        }

        // Get the choices from the fully qualified class name
        return new TypeGuess('choice', [
            'choices' => $registeredEnumTypeFQCN::getReadableValues()
        ], Guess::VERY_HIGH_CONFIDENCE);
    }
}