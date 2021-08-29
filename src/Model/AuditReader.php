<?php
declare(strict_types=1);

namespace Marlinc\AdminBundle\Model;

use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Loggable\Entity\LogEntry;
use Gedmo\Loggable\LoggableListener;
use Sonata\AdminBundle\Model\AuditReaderInterface;

class AuditReader implements AuditReaderInterface
{
    private EntityManagerInterface $em;

    private LoggableListener $loggable;

    public function __construct(EntityManagerInterface $em, LoggableListener $loggable)
    {
        $this->em = $em;
        $this->loggable = $loggable;
    }

    private function getLogEntryClassName(string $className): string
    {
        $configuration = $this->loggable->getConfiguration($this->em, $className);

        return $configuration['logEntryClass'] ?? LogEntry::class;
    }

    /**
     * @inheritdoc
     */
    public function find(string $className, $id, $revision): ?object
    {

        $configuration = $this->loggable->getConfiguration($this->em, $className);

        $object = $this->em->find($className, $id);

        if ($configuration['loggable'] == true) {
            $repo = $this->em->getRepository($this->getLogEntryClassName($className));
            $repo->revert($object, $revision);
        }

        return clone $object;
    }

    /**
     * @inheritdoc
     */
    public function findRevisionHistory(string $className,  int $limit = 20, int $offset = 0): array
    {
        // TODO implement
        return [];
    }

    /**
     * @inheritdoc
     */
    public function findRevision(string $className, $revisionId): ?object
    {
        // TODO implement
        return null;
    }

    /**
     * @inheritdoc
     */
    public function findRevisions(string $className, $id): array
    {
        $repo = $this->em->getRepository($this->getLogEntryClassName($className));
        $object = $this->em->find($className, $id);

        return $repo->getLogEntries($object);
    }

    public function revert($object, int $revision)
    {
        // TODO: Method not used?
        $repo = $this->em->getRepository($this->getLogEntryClassName(get_class($object)));
        $repo->revert($object, $revision);
        $this->em->flush();
    }

    /**
     * @inheritdoc
     */
    public function diff($className, $id, $oldRevisionId, $newRevisionId): array
    {
        // TODO: Implement diff() method.
        return [];
    }

}