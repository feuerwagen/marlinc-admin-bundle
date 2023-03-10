<?php
declare(strict_types=1);


namespace Marlinc\AdminBundle\Filter;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\QueryBuilder;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\DoctrineORMAdminBundle\Filter\ModelFilter as BaseFilter;
use Sonata\DoctrineORMAdminBundle\Filter\Filter;
use Sonata\AdminBundle\Filter\Model\FilterData;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class ModelFilter extends Filter
{
    const TYPE_CONTAINS = 1;
    const TYPE_NOT_CONTAINS = 2;
    const TYPE_EQUAL = 3;

    private BaseFilter $decorated;

    private EntityManagerInterface $em;

    public function __construct(BaseFilter $decorated, EntityManager $em)
    {
        $this->em = $em;
        $this->decorated = $decorated;
    }

    public function filter(ProxyQueryInterface $query, string $alias, string $field, FilterData $data): void
    {
        $this->decorated->filter($query,$alias,$field,$data);
    }

    public function getRenderSettings(): array
    {
        return $this->decorated->getRenderSettings();
    }

    /**
     * For the record, the $alias value is provided by the association method (and the entity join method)
     *  so the field value is not used here.
     *
     * @param ProxyQueryInterface|QueryBuilder $queryBuilder
     * @param string                           $alias
     * @param mixed                            $data
     *
     * @return mixed
     */
    protected function handleMultiple(ProxyQueryInterface $queryBuilder, $alias, $data)
    {
        if (count($data['value']) == 0) {
            return;
        }

        $parameterName = $this->getNewParameterName($queryBuilder);

        if (isset($data['type']) && $data['type'] == self::TYPE_NOT_CONTAINS) {
            $subQueryBuilder = $this->em->createQueryBuilder();
            $subQuery = $subQueryBuilder // TODO: Remove Client-specific code
            ->select(['c'])
                ->from('MarlincClientBundle:Client', 'c')
                ->innerJoin('c.tags', 'f')
                ->where('f.id = :flagid')
                ->setParameter('flagid', $data['value'])
                ->getQuery()
                ->getArrayResult()
            ;

            $or = $queryBuilder->expr()->orX();

            $or->add($queryBuilder->expr()->notIn(current(($queryBuilder->getRootAliases())), ':subquery'));
            $queryBuilder->setParameter('subquery', $subQuery);

            $this->applyWhere($queryBuilder, $or);
        } elseif (isset($data['type']) && $data['type'] == self::TYPE_CONTAINS) {
            $this->applyWhere($queryBuilder, $queryBuilder->expr()->in($alias, ':'.$parameterName));
            $queryBuilder->setParameter($parameterName, $data['value']);
        } else {
            // Check that all relations required by the filter values exist.
            foreach ($data['value'] as $value) {
                $this->applyWhere($queryBuilder, $queryBuilder->expr()->in($alias, ':'.$parameterName));
                $queryBuilder->setParameter($parameterName, [$value]);

                // Create new parameters and association joins for each value.
                $parameterName = $this->getNewParameterName($queryBuilder);
                $alias = $this->extraAssociation($queryBuilder);
            }
        }
    }

    protected function extraAssociation(ProxyQueryInterface $queryBuilder): string
    {
        $types = [
            ClassMetadataInfo::ONE_TO_ONE,
            ClassMetadataInfo::ONE_TO_MANY,
            ClassMetadataInfo::MANY_TO_MANY,
            ClassMetadataInfo::MANY_TO_ONE,
        ];

        if (!in_array($this->getOption('mapping_type'), $types)) {
            throw new \RuntimeException('Invalid mapping type');
        }

        $associationMapping = $this->getAssociationMapping();
        $alias = $queryBuilder->getRootAlias();
        $baseAlias = 's_'.$associationMapping['fieldName'];
        $n = 1;
        $newAlias = $baseAlias;

        while (in_array($newAlias, $queryBuilder->getAllAliases())) {
            $newAlias = $baseAlias.'_'.$n;
            $n++;
        }

        $queryBuilder->leftJoin(sprintf('%s.%s', $alias, $associationMapping['fieldName']), $newAlias);

        return $newAlias;
    }

    /**
     * @inheritdoc
     */
    public function getDefaultOptions(): array
    {
        return [
            'mapping_type' => false,
            'field_name' => false,
            'field_type' => EntityType::class,
            'field_options' => [],
            'operator_type' => ChoiceType::class,
            'operator_options' => [
                'choices' => [
                    'label_type_contains' => self::TYPE_CONTAINS,
                    'label_type_not_contains' => self::TYPE_NOT_CONTAINS,
                    'label_type_equals' => self::TYPE_EQUAL,
                ],
                'choice_translation_domain' => 'SonataAdminBundle',
            ],
        ];
    }
}
