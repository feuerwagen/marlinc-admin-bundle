<?php
declare(strict_types=1);


namespace Marlinc\AdminBundle\Entity\Repository;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Gedmo\Sortable\Entity\Repository\SortableRepository as BaseSortableRepository;

class SortableRepository extends BaseSortableRepository
{
    /**
     * Get max position of all entities
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getMaxPosition(?object $object = null): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select('MAX(n.' . $this->config['position'] . ')')
            ->from($this->config['useObjectClass'], 'n');

        if (isset($this->config['groups']) && count($this->config['groups']) > 0) {
            $i = 1;
            $qb->where('1 = 1');
            foreach ($this->config['groups'] as $group) {
                $value = $this->meta->getReflectionProperty($group)->getValue($object);
                if (is_null($value)) {
                    $qb->andWhere($qb->expr()->isNull('n.' . $group));
                } else {
                    $qb->andWhere('n.' . $group . ' = :group__' . $i);
                    $qb->setParameter('group__' . $i, $value);
                }
                $i++;
            }
        }

        $query = $qb->getQuery()
            ->useQueryCache(false)
            ->disableResultCache()
        ;

        return $query->getSingleScalarResult();
    }
}