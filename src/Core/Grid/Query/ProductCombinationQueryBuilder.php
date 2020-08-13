<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\Grid\Query;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use PrestaShop\PrestaShop\Core\Domain\Language\ValueObject\LanguageId;
use PrestaShop\PrestaShop\Core\Domain\Product\Combination\QueryResult\CombinationAttributeInformation;
use PrestaShop\PrestaShop\Core\Grid\Search\SearchCriteriaInterface;

/**
 * Builds query for product combinations list
 */
final class ProductCombinationQueryBuilder extends AbstractDoctrineQueryBuilder
{
    /**
     * @var DoctrineSearchCriteriaApplicatorInterface
     */
    private $doctrineSearchCriteriaApplicator;

    /**
     * @param Connection $connection
     * @param string $dbPrefix
     * @param DoctrineSearchCriteriaApplicatorInterface $doctrineSearchCriteriaApplicator
     */
    public function __construct(
        Connection $connection,
        string $dbPrefix,
        DoctrineSearchCriteriaApplicatorInterface $doctrineSearchCriteriaApplicator
    ) {
        parent::__construct($connection, $dbPrefix);
        $this->doctrineSearchCriteriaApplicator = $doctrineSearchCriteriaApplicator;
    }

    /**
     * {@inheritDoc}
     */
    public function getSearchQueryBuilder(SearchCriteriaInterface $searchCriteria): QueryBuilder
    {
        $qb = $this->getQueryBuilder($searchCriteria);

        $this->doctrineSearchCriteriaApplicator
            ->applyPagination($searchCriteria, $qb)
            ->applySorting($searchCriteria, $qb)
        ;

        //@todo: annnd it fails because Ill need some results from first query and from second to build the combinations
            // but can only return one QueryBuilder.
        $this->getAttributesInformationByCombinationId()
    }

    /**
     * {@inheritDoc}
     */
    public function getCountQueryBuilder(SearchCriteriaInterface $searchCriteria): QueryBuilder
    {
        $qb = $this->getQueryBuilder($searchCriteria);
        $qb->select('COUNT(pa.id_product_attribute)');

        return $qb;
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     *
     * @return QueryBuilder
     */
    private function getQueryBuilder(SearchCriteriaInterface $searchCriteria): QueryBuilder
    {
        $qb = $this->connection->createQueryBuilder();
        //@todo: select all necessary values for combination editing like quantity, price etc. (UpdateCombinations PR)
        //@todo: maybe add an object like `CombinationInformation` for this?
        $qb->select('pa.id_product_attribute')
            ->addSelect('pa.id_product')
            ->from($this->dbPrefix . 'product_attribute', 'pa')
        ;

        $this->applyFilters($qb, $searchCriteria->getFilters());

        return $qb;
    }

    /**
     * @param QueryBuilder $qb
     * @param array $filters
     */
    private function applyFilters(QueryBuilder $qb, array $filters): void
    {
        //@todo: finish up filtering product_attribute values
        $allowedFilters = ['product_id'];

        foreach ($filters as $filterName => $value) {
            if (!in_array($filterName, $allowedFilters, true)) {
                continue;
            }

            if ($filterName === 'id_product') {
                $qb->andWhere('pa.id_product = :productId')
                    ->setParameter('productId', $value)
                ;
            }
        }
    }

    /**
     * @param int[] $combinationIds
     * @param LanguageId $langId
     *
     * @todo: move queries to some dedicated service. Or whole method to Combination|Product obj model?
     *
     * @return CombinationAttributeInformation[]
     */
    private function getAttributesInformationByCombinationId(array $combinationIds, LanguageId $langId): array
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('a.id_attribute')
            ->addSelect('pac.id_product_attribute')
            ->addSelect('ag.id_attribute_group')
            ->addSelect('al.name AS attribute_name')
            ->addSelect('agl.name AS attribute_group_name')
            ->from($this->dbPrefix . 'product_attribute_combination', 'pac')
            ->leftJoin(
                'pac',
                $this->dbPrefix . 'attribute',
                'a',
                'pac.id_attribute = a.id_attribute'
            )->leftJoin(
                'a',
                $this->dbPrefix . 'attribute_lang',
                'al',
                'a.id_attribute = al.id_attribute AND al.id_lang = :langId'
            )->leftJoin(
                'a',
                $this->dbPrefix . 'attribute_group',
                'ag',
                'a.id_attribute_group = ag.id_attribute_group'
            )->leftJoin(
                'ag',
                $this->dbPrefix . 'attribute_group_lang',
                'agl',
                'agl.id_attribute_group = ag.id_attribute_group AND agl.id_lang = :langId'
            )->where($qb->expr()->in('pac.id_product_attribute', ':combinationIds'))
            ->setParameter('combinationIds', $combinationIds, Connection::PARAM_INT_ARRAY)
            ->setParameter('langId', $langId->getValue())
        ;

        $results = $qb->execute()->fetchAll();

        $attributesInformationByCombinationId = [];
        foreach ($results as $result) {
            $attributesInformationByCombinationId[(int) $result['id_product_attribute']][] = new CombinationAttributeInformation(
                (int) $result['id_attribute_group'],
                $result['attribute_group_name'],
                (int) $result['id_attribute'],
                $result['attribute_name']
            );
        }

        return $attributesInformationByCombinationId;
    }
}
