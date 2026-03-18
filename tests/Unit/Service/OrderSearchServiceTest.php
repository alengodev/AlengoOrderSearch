<?php declare(strict_types=1);

namespace AlengoOrderSearch\Tests\Unit\Service;

use AlengoOrderSearch\Service\OrderSearchService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;

#[CoversClass(OrderSearchService::class)]
class OrderSearchServiceTest extends TestCase
{
    private OrderSearchService $service;

    protected function setUp(): void
    {
        $this->service = new OrderSearchService();
    }

    // -------------------------------------------------------------------------
    // addSearchCriteria – existing tests
    // -------------------------------------------------------------------------

    public function testAddSearchCriteriaWithValidTermAddsOneMultiFilter(): void
    {
        $criteria = new Criteria();
        $result = $this->service->addSearchCriteria('Mueller', $criteria);

        $filters = $result->getFilters();
        static::assertCount(1, $filters);
        static::assertInstanceOf(MultiFilter::class, $filters[0]);
    }

    public function testAddSearchCriteriaUsesOrConnection(): void
    {
        $criteria = new Criteria();
        $this->service->addSearchCriteria('Mueller', $criteria);

        /** @var MultiFilter $multiFilter */
        $multiFilter = $criteria->getFilters()[0];
        static::assertSame(MultiFilter::CONNECTION_OR, $multiFilter->getOperator());
    }

    public function testAddSearchCriteriaAddsAllSevenFilterFields(): void
    {
        $criteria = new Criteria();
        $this->service->addSearchCriteria('Mueller', $criteria);

        /** @var MultiFilter $multiFilter */
        $multiFilter = $criteria->getFilters()[0];
        static::assertCount(7, $multiFilter->getQueries());
    }

    public function testAddSearchCriteriaAddsShippingOrderAddressAssociation(): void
    {
        $criteria = new Criteria();
        $this->service->addSearchCriteria('Wien', $criteria);

        $associations = $criteria->getAssociations();
        static::assertArrayHasKey('deliveries', $associations);

        // The sub-association shippingOrderAddress must be nested inside deliveries
        $deliveriesCriteria = $associations['deliveries'];
        static::assertArrayHasKey('shippingOrderAddress', $deliveriesCriteria->getAssociations());
    }

    public function testAddSearchCriteriaWithEmptyTermDoesNotAddFilters(): void
    {
        $criteria = new Criteria();
        $result = $this->service->addSearchCriteria('', $criteria);

        static::assertCount(0, $result->getFilters());
    }

    public function testAddSearchCriteriaWithWhitespaceOnlyDoesNotAddFilters(): void
    {
        $criteria = new Criteria();
        $result = $this->service->addSearchCriteria('   ', $criteria);

        static::assertCount(0, $result->getFilters());
        static::assertEmpty($result->getAssociations());
    }

    public function testAddSearchCriteriaWithWhitespaceTrimsTermBeforeAdding(): void
    {
        $criteria = new Criteria();
        $result = $this->service->addSearchCriteria('  Mueller  ', $criteria);

        static::assertCount(1, $result->getFilters());
    }

    public function testAddSearchCriteriaReturnsSameCriteriaInstance(): void
    {
        $criteria = new Criteria();
        $result = $this->service->addSearchCriteria('test', $criteria);

        static::assertSame($criteria, $result);
    }

    public function testAddSearchCriteriaReturnsUnmodifiedCriteriaForEmptyTerm(): void
    {
        $criteria = new Criteria();
        $result = $this->service->addSearchCriteria('', $criteria);

        static::assertSame($criteria, $result);
        static::assertCount(0, $result->getFilters());
    }

    public function testExtractSearchTermTrimsWhitespace(): void
    {
        $result = $this->service->extractSearchTerm('  Mueller  ');
        static::assertSame('Mueller', $result);
    }

    public function testExtractSearchTermStripsHtmlTags(): void
    {
        $result = $this->service->extractSearchTerm('<script>alert(1)</script>Mueller');
        static::assertSame('Mueller', $result);
    }

    public function testExtractSearchTermWithEmptyStringReturnsEmpty(): void
    {
        $result = $this->service->extractSearchTerm('');
        static::assertSame('', $result);
    }

    public function testExtractSearchTermPreservesSpecialCharacters(): void
    {
        $result = $this->service->extractSearchTerm('Müller-Straße 5');
        static::assertSame('Müller-Straße 5', $result);
    }

    public function testAddSearchCriteriaIncludesEmailFilter(): void
    {
        $criteria = new Criteria();
        $this->service->addSearchCriteria('test@example.com', $criteria);

        /** @var MultiFilter $multiFilter */
        $multiFilter = $criteria->getFilters()[0];
        $fields = array_map(
            fn ($filter) => $filter->getField(),
            $multiFilter->getQueries()
        );

        static::assertContains('orderCustomer.email', $fields);
    }

    // -------------------------------------------------------------------------
    // addDateRangeCriteria – new tests
    // -------------------------------------------------------------------------

    public function testAddDateRangeCriteriaWithBothDatesAddsTwoRangeFilters(): void
    {
        $criteria = new Criteria();
        $this->service->addDateRangeCriteria('2024-01-01', '2024-12-31', $criteria);

        $filters = $criteria->getFilters();
        static::assertCount(2, $filters);
        static::assertInstanceOf(RangeFilter::class, $filters[0]);
        static::assertInstanceOf(RangeFilter::class, $filters[1]);
    }

    public function testAddDateRangeCriteriaWithDateFromOnlyAddsOneRangeFilter(): void
    {
        $criteria = new Criteria();
        $this->service->addDateRangeCriteria('2024-06-01', null, $criteria);

        $filters = $criteria->getFilters();
        static::assertCount(1, $filters);
        static::assertInstanceOf(RangeFilter::class, $filters[0]);
    }

    public function testAddDateRangeCriteriaWithDateToOnlyAddsOneRangeFilter(): void
    {
        $criteria = new Criteria();
        $this->service->addDateRangeCriteria(null, '2024-06-30', $criteria);

        $filters = $criteria->getFilters();
        static::assertCount(1, $filters);
        static::assertInstanceOf(RangeFilter::class, $filters[0]);
    }

    public function testAddDateRangeCriteriaWithBothNullAddsNoFilters(): void
    {
        $criteria = new Criteria();
        $this->service->addDateRangeCriteria(null, null, $criteria);

        static::assertCount(0, $criteria->getFilters());
    }

    public function testAddDateRangeCriteriaWithBothEmptyStringsAddsNoFilters(): void
    {
        $criteria = new Criteria();
        $this->service->addDateRangeCriteria('', '', $criteria);

        static::assertCount(0, $criteria->getFilters());
    }

    public function testAddDateRangeCriteriaDateFromSetsGteWithDayStart(): void
    {
        $criteria = new Criteria();
        $this->service->addDateRangeCriteria('2024-03-15', null, $criteria);

        /** @var RangeFilter $filter */
        $filter = $criteria->getFilters()[0];
        $parameters = $filter->getParameters();

        static::assertArrayHasKey(RangeFilter::GTE, $parameters);
        static::assertSame('2024-03-15 00:00:00', $parameters[RangeFilter::GTE]);
        static::assertArrayNotHasKey(RangeFilter::LTE, $parameters);
    }

    public function testAddDateRangeCriteriaDateToSetsLteWithDayEnd(): void
    {
        $criteria = new Criteria();
        $this->service->addDateRangeCriteria(null, '2024-03-15', $criteria);

        /** @var RangeFilter $filter */
        $filter = $criteria->getFilters()[0];
        $parameters = $filter->getParameters();

        static::assertArrayHasKey(RangeFilter::LTE, $parameters);
        static::assertSame('2024-03-15 23:59:59', $parameters[RangeFilter::LTE]);
        static::assertArrayNotHasKey(RangeFilter::GTE, $parameters);
    }

    public function testAddDateRangeCriteriaWithInvalidDateFromIsIgnored(): void
    {
        $criteria = new Criteria();
        $this->service->addDateRangeCriteria('not-a-date', null, $criteria);

        static::assertCount(0, $criteria->getFilters());
    }

    public function testAddDateRangeCriteriaWithInvalidDateToIsIgnored(): void
    {
        $criteria = new Criteria();
        $this->service->addDateRangeCriteria(null, '2024-13-99', $criteria);

        static::assertCount(0, $criteria->getFilters());
    }

    public function testAddDateRangeCriteriaWithInvalidDateFromStillAppliesValidDateTo(): void
    {
        $criteria = new Criteria();
        $this->service->addDateRangeCriteria('not-a-date', '2024-12-31', $criteria);

        $filters = $criteria->getFilters();
        static::assertCount(1, $filters);
        static::assertInstanceOf(RangeFilter::class, $filters[0]);

        /** @var RangeFilter $filter */
        $filter = $filters[0];
        static::assertArrayHasKey(RangeFilter::LTE, $filter->getParameters());
    }

    public function testAddDateRangeCriteriaWithInvalidDateToStillAppliesValidDateFrom(): void
    {
        $criteria = new Criteria();
        $this->service->addDateRangeCriteria('2024-01-01', 'not-a-date', $criteria);

        $filters = $criteria->getFilters();
        static::assertCount(1, $filters);
        static::assertInstanceOf(RangeFilter::class, $filters[0]);

        /** @var RangeFilter $filter */
        $filter = $filters[0];
        static::assertArrayHasKey(RangeFilter::GTE, $filter->getParameters());
    }

    public function testAddDateRangeCriteriaReturnsSameCriteriaInstance(): void
    {
        $criteria = new Criteria();
        $result = $this->service->addDateRangeCriteria('2024-01-01', '2024-12-31', $criteria);

        static::assertSame($criteria, $result);
    }

    public function testAddDateRangeCriteriaFilterFieldIsOrderDateTime(): void
    {
        $criteria = new Criteria();
        $this->service->addDateRangeCriteria('2024-06-01', null, $criteria);

        /** @var RangeFilter $filter */
        $filter = $criteria->getFilters()[0];
        static::assertSame('orderDateTime', $filter->getField());
    }

    public function testAddDateRangeCriteriaCanBeCombinedWithTextSearch(): void
    {
        $criteria = new Criteria();
        $this->service->addSearchCriteria('Mueller', $criteria);
        $this->service->addDateRangeCriteria('2024-01-01', '2024-12-31', $criteria);

        // 1 MultiFilter (text search) + 2 RangeFilters (date range)
        static::assertCount(3, $criteria->getFilters());
    }
}
