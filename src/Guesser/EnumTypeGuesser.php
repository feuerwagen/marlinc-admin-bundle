<?php
declare(strict_types=1);

namespace Marlinc\AdminBundle\Guesser;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;
use Fresh\DoctrineEnumBundle\Exception\EnumType\EnumTypeIsRegisteredButClassDoesNotExistException;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\FieldDescription\TypeGuesserInterface;
use Symfony\Component\Form\Guess\Guess;
use Symfony\Component\Form\Guess\TypeGuess;

/**
 * Guess proper list field type for fields based on @see AbstractEnumType.
 * TODO Make dependency on DoctrineEnumBundle optional.
 */
class EnumTypeGuesser implements TypeGuesserInterface
{
    /**
     * Registered doctrine types
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

        $registeredTypeFQCN = $this->registeredTypes[$fieldType];

        if (!\class_exists($registeredTypeFQCN)) {
            throw new EnumTypeIsRegisteredButClassDoesNotExistException(\sprintf(
                'Doctrine type "%s" is registered as "%s", but that class does not exist',
                $fieldType,
                $registeredTypeFQCN
            ));
        }

        if (!\is_subclass_of($registeredTypeFQCN, AbstractEnumType::class)) {
            return null;
        }

        // Get the choices from the fully qualified class name
        return new TypeGuess('choice', [
            'choices' => $registeredTypeFQCN::getReadableValues()
        ], Guess::VERY_HIGH_CONFIDENCE);
    }
}