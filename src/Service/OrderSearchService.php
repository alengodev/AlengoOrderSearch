<?php declare(strict_types=1);

namespace AlengoOrderSearch\Service;

use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;

/**
 * Provides search logic for the customer order history.
 *
 * Extends a Shopware DAL {@see Criteria} instance with OR-linked
 * {@see ContainsFilter}s across order customer name, email, line item labels,
 * and shipping address fields. The criteria object is mutated in place;
 * the return value is the same instance for call chaining.
 */
class OrderSearchService
{
    /**
     * Adds a MultiFilter(OR) to the criteria covering all seven searchable order fields.
     *
     * Returns the unmodified criteria immediately when the trimmed term is empty.
     * The `deliveries.shippingOrderAddress` association is added automatically
     * because it is not loaded by default and is required for address field filtering.
     *
     * @param Criteria $criteria Modified in place; returned for chaining.
     */
    public function addSearchCriteria(string $searchTerm, Criteria $criteria): Criteria
    {
        $term = trim($searchTerm);

        if ($term === '') {
            return $criteria;
        }

        $criteria->addAssociation('deliveries.shippingOrderAddress');

        $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_OR, [
            new ContainsFilter('orderCustomer.firstName', $term),
            new ContainsFilter('orderCustomer.lastName', $term),
            new ContainsFilter('orderCustomer.email', $term),
            new ContainsFilter('lineItems.label', $term),
            new ContainsFilter('deliveries.shippingOrderAddress.street', $term),
            new ContainsFilter('deliveries.shippingOrderAddress.city', $term),
            new ContainsFilter('deliveries.shippingOrderAddress.zipcode', $term),
        ]));

        return $criteria;
    }

    /**
     * Sanitizes raw user input before it is passed into a DAL filter.
     *
     * Strips HTML tags and surrounding whitespace. Returns an empty string
     * when the input contains only markup or whitespace, which callers use
     * as the signal to skip filtering entirely.
     */
    public function extractSearchTerm(string $rawInput): string
    {
        return trim(strip_tags($rawInput));
    }
}
