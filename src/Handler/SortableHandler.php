<?php
declare(strict_types=1);


namespace Marlinc\AdminBundle\Handler;

use Doctrine\ORM\EntityManagerInterface;

class SortableHandler
{
    /**
     * Position constants
     */
    const MOVE_TOP = 'top';
    const MOVE_UP = 'up';
    const MOVE_DOWN = 'down';
    const MOVE_BOTTOM = 'bottom';

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Get new position
     */
    public function getPosition($object, int $position, int $lastPosition): int
    {
        switch ($position) {
            case self::MOVE_UP:
                if ($object->getPosition() > 0) {
                    $position = $object->getPosition() - 1;
                }
                break;

            case self::MOVE_DOWN:
                if ($object->getPosition() < $lastPosition) {
                    $position = $object->getPosition() + 1;
                }
                break;

            case self::MOVE_TOP:
                if ($object->getPosition() > 0) {
                    $position = 0;
                }
                break;

            case self::MOVE_BOTTOM:
                if ($object->getPosition() < $lastPosition) {
                    $position = $lastPosition;
                }
                break;
        }

        return is_numeric($position) ? $position : $object->getPosition();
    }

    /**
     * Get entity last position
     */
    public function getLastPosition($object): int
    {
        $repository = $this->em->getRepository(get_class($object));

        return $repository->getMaxPosition($object);
    }
}