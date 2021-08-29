<?php
declare(strict_types=1);


namespace Marlinc\AdminBundle\Doctrine\Filter;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetaData;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Gedmo\SoftDeleteable\SoftDeleteableListener;

/**
 * Filter entity queries to only display soft deleted entities if enabled for the entity class.
 */
class SoftDeleteableTrashFilter extends SQLFilter
{
    protected ?SoftDeleteableListener $listener = null;

    protected ?EntityManagerInterface $entityManager = null;

    /**
     * @var array<string,bool> The enabled entity classes for this filter.
     */
    protected $enabled = [];

    /**
     * {@inheritdoc}
     */
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
    {
        $class = $targetEntity->getName();

        // Classes can be explicitly enabled.
        if (
            !array_key_exists($class, $this->enabled) || $this->enabled[$class] === false
            || array_key_exists($targetEntity->rootEntityName, $this->enabled) && $this->enabled[$targetEntity->rootEntityName] === false
        ) {
            return '';
        }

        $config = $this->getListener()->getConfiguration($this->getEntityManager(), $targetEntity->name);

        // Only applicable for entities with SoftDeletable behaviour.
        if (!isset($config['softDeleteable']) || !$config['softDeleteable']) {
            return '';
        }

        $conn = $this->getEntityManager()->getConnection();
        $platform = $conn->getDatabasePlatform();
        $quoteStrategy = $this->getEntityManager()->getConfiguration()->getQuoteStrategy();

        $column = $quoteStrategy->getColumnName($config['fieldName'], $targetEntity, $platform);

        $addCondSql = $platform->getIsNotNullExpression($targetTableAlias.'.'.$column);
        if (isset($config['timeAware']) && $config['timeAware']) {
            $now = $conn->quote(date('Y-m-d H:i:s')); // should use UTC in database and PHP
            $addCondSql = "({$addCondSql} OR {$targetTableAlias}.{$column} > {$now})";
        }

        return $addCondSql;
    }

    public function disableForEntity($class)
    {
        $this->enabled[$class] = false;
    }

    public function enableForEntity($class)
    {
        $this->enabled[$class] = true;
    }

    protected function getListener(): SoftDeleteableListener
    {
        if ($this->listener === null) {
            $em = $this->getEntityManager();
            $evm = $em->getEventManager();

            foreach ($evm->getListeners() as $listeners) {
                foreach ($listeners as $listener) {
                    if ($listener instanceof SoftDeleteableListener) {
                        $this->listener = $listener;

                        return $this->listener;
                    }
                }
            }

            if ($this->listener === null) {
                throw new \RuntimeException('Listener "SoftDeleteableListener" was not added to the EventManager!');
            }
        }

        return $this->listener;
    }

    protected function getEntityManager(): EntityManager
    {
        if ($this->entityManager === null) {
            $refl = new \ReflectionProperty('Doctrine\ORM\Query\Filter\SQLFilter', 'em');
            $refl->setAccessible(true);
            $this->entityManager = $refl->getValue($this);
        }

        return $this->entityManager;
    }
}
