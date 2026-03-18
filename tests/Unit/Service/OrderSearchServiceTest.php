<?php declare(strict_types=1);

namespace AlengoOrderSearch\Tests\Unit\Service;

use AlengoOrderSearch\Service\OrderSearchService;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;

/**
 * @covers \AlengoOrderSearch\Service\OrderSearchService
 */
class OrderSearchServiceTest extends TestCase
{
    private OrderSearchService $service;

    protected function setUp(): void
    {
        $this->service = new OrderSearchService();
    }

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
}
