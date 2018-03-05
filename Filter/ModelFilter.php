<?php
/**
 * Created by PhpStorm.
 * User: elias
 * Date: 18.05.17
 * Time: 10:49
 */

namespace AppBundle\Filter;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\QueryBuilder;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\DoctrineORMAdminBundle\Filter\ModelFilter as BaseFilter;

class ModelFilter extends BaseFilter
{
    const TYPE_CONTAINS = 1;
    const TYPE_NOT_CONTAINS = 2;
    const TYPE_EQUAL = 3;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * ModelFilter constructor.
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
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
            $subQuery = $subQueryBuilder
                ->select(['c'])
                ->from('MarlincClientBundle:Client', 'c')
                ->innerJoin('c.flags', 'f')
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

    protected function extraAssociation(ProxyQueryInterface $queryBuilder) {
        $types = array(
            ClassMetadataInfo::ONE_TO_ONE,
            ClassMetadataInfo::ONE_TO_MANY,
            ClassMetadataInfo::MANY_TO_MANY,
            ClassMetadataInfo::MANY_TO_ONE,
        );

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
        $alias = $newAlias;

        return $alias;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOptions()
    {
        return array(
            'mapping_type' => false,
            'field_name' => false,
            'field_type' => method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix')
                ? 'Symfony\Bridge\Doctrine\Form\Type\EntityType'
                : 'entity', // NEXT_MAJOR: Remove ternary (when requirement of Symfony is >= 2.8)
            'field_options' => array(),
            'operator_type' => 'Symfony\Component\Form\Extension\Core\Type\ChoiceType',
            'operator_options' => [
                'choices' => [
                    'label_type_contains' => self::TYPE_CONTAINS,
                    'label_type_not_contains' => self::TYPE_NOT_CONTAINS,
                    'label_type_equals' => self::TYPE_EQUAL,
                ],
                'choice_translation_domain' => 'SonataAdminBundle',
            ],
        );
    }
}