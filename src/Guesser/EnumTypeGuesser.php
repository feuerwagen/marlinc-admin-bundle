<?php
/**
 * Created by PhpStorm.
 * User: em
 * Date: 23.07.18
 * Time: 11:13
 */

namespace Marlinc\AdminBundle\Guesser;


use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;
use Fresh\DoctrineEnumBundle\Exception\EnumTypeIsRegisteredButClassDoesNotExistException;
use Sonata\AdminBundle\Model\ModelManagerInterface;
use Sonata\DoctrineORMAdminBundle\Guesser\AbstractTypeGuesser;
use Symfony\Component\Form\Guess\Guess;
use Symfony\Component\Form\Guess\TypeGuess;

class EnumTypeGuesser extends AbstractTypeGuesser
{
    /**
     * @var AbstractEnumType[] Array of registered ENUM types
     */
    protected $registeredEnumTypes = [];

    /**
     * Constructor.
     *
     * @param array $registeredTypes Array of registered ENUM types
     */
    public function __construct(array $registeredTypes)
    {
        foreach ($registeredTypes as $type => $details) {
            $this->registeredEnumTypes[$type] = $details['class'];
        }
    }

    /**
     * @inheritDoc
     */
    public function guessType($class, $property, ModelManagerInterface $modelManager)
    {
        if (!$ret = $this->getParentMetadataForProperty($class, $property, $modelManager)) {
            return new TypeGuess('text', [], Guess::LOW_CONFIDENCE);
        }

        /** @var \Doctrine\ORM\Mapping\ClassMetadataInfo $metadata */
        list($metadata, $propertyName) = $ret;
        $fieldType = $metadata->getTypeOfField($property);

        // This is not one of the registered ENUM types
        if (!isset($this->registeredEnumTypes[$fieldType])) {
            return;
        }

        $registeredEnumTypeFQCN = $this->registeredEnumTypes[$fieldType];

        if (!\class_exists($registeredEnumTypeFQCN)) {
            throw new EnumTypeIsRegisteredButClassDoesNotExistException(\sprintf(
                'ENUM type "%s" is registered as "%s", but that class does not exist',
                $fieldType,
                $registeredEnumTypeFQCN
            ));
        }

        if (!\is_subclass_of($registeredEnumTypeFQCN, AbstractEnumType::class)) {
            return;
        }

        // Get the choices from the fully qualified class name
        return new TypeGuess('choice', [
            'choices' => $registeredEnumTypeFQCN::getReadableValues()
        ], Guess::VERY_HIGH_CONFIDENCE);
    }
}