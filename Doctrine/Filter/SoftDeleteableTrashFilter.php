<?php

namespace Marlinc\AdminBundle\Doctrine\Filter;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetaData;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Gedmo\SoftDeleteable\SoftDeleteableListener;

class SoftDeleteableTrashFilter extends SQLFilter
{
    /**
     * @var SoftDeleteableListener|null
     */
    protected $listener;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var array The enabled entity classes for this filter.
     */
    protected $enabled = array();

    /**
     * {@inheritdoc}
     */
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        $class = $targetEntity->getName();

        // Classes need to be explicitly enabled.
        if (!array_key_exists($class, $this->enabled) || $this->enabled[$class] === false) {
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

    /**
     * @return SoftDeleteableListener|null
     */
    protected function getListener()
    {
        if ($this->listener === null) {
            $em = $this->getEntityManager();
            $evm = $em->getEventManager();

            foreach ($evm->getListeners() as $listeners) {
                foreach ($listeners as $listener) {
                    if ($listener instanceof SoftDeleteableListener) {
                        $this->listener = $listener;

                        break 2;
                    }
                }
            }

            if ($this->listener === null) {
                throw new \RuntimeException('Listener "SoftDeleteableListener" was not added to the EventManager!');
            }
        }

        return $this->listener;
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        if ($this->entityManager === null) {
            $refl = new \ReflectionProperty('Doctrine\ORM\Query\Filter\SQLFilter', 'em');
            $refl->setAccessible(true);
            $this->entityManager = $refl->getValue($this);
        }

        return $this->entityManager;
    }
}
