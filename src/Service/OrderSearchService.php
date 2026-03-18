<?php declare(strict_types=1);

namespace AlengoOrderSearch\Service;

use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;

/**
 * Provides search and filter logic for the customer order history.
 *
 * Extends a Shopware DAL {@see Criteria} instance with OR-linked
 * {@see ContainsFilter}s across order customer name, email, line item labels,
 * and shipping address fields, as well as optional {@see RangeFilter}s on
 * the order date. The criteria object is mutated in place; the return value
 * is the same instance for call chaining.
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
     * Adds RangeFilter(s) on `orderDateTime` to restrict results to the given date range.
     *
     * Both parameters are optional. Passing only `$dateFrom` filters orders from that
     * date onwards; passing only `$dateTo` filters up to the end of that date; passing
     * both restricts to the closed interval [dateFrom 00:00:00, dateTo 23:59:59].
     *
     * Invalid or non-parseable date strings (anything that is not a valid YYYY-MM-DD
     * value) are silently ignored — no exception is thrown. This keeps the filter
     * lenient against malformed user input.
     *
     * @param string|null $dateFrom Start of the date range in YYYY-MM-DD format (inclusive).
     *                              Pass null or an invalid string to skip the lower bound.
     * @param string|null $dateTo   End of the date range in YYYY-MM-DD format (inclusive).
     *                              Pass null or an invalid string to skip the upper bound.
     * @param Criteria    $criteria Modified in place; returned for chaining.
     */
    public function addDateRangeCriteria(?string $dateFrom, ?string $dateTo, Criteria $criteria): Criteria
    {
        if ($dateFrom !== null && $dateFrom !== '') {
            $parsedFrom = $this->parseStrictDate($dateFrom);
            if ($parsedFrom !== null) {
                $criteria->addFilter(new RangeFilter('orderDateTime', [
                    RangeFilter::GTE => $parsedFrom->format('Y-m-d 00:00:00'),
                ]));
            }
        }

        if ($dateTo !== null && $dateTo !== '') {
            $parsedTo = $this->parseStrictDate($dateTo);
            if ($parsedTo !== null) {
                $criteria->addFilter(new RangeFilter('orderDateTime', [
                    RangeFilter::LTE => $parsedTo->format('Y-m-d 23:59:59'),
                ]));
            }
        }

        return $criteria;
    }

    /**
     * Parses a date string strictly as YYYY-MM-DD.
     *
     * Returns null for any input that is not a syntactically valid and
     * calendar-correct date (e.g. month 13 or day 99 are rejected).
     * PHP's createFromFormat() alone is not sufficient because it silently
     * overflows invalid values (e.g. 2024-13-01 → 2025-01-01).
     */
    private function parseStrictDate(string $date): ?\DateTimeImmutable
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return null;
        }

        [$y, $m, $d] = array_map('intval', explode('-', $date));

        if (!checkdate($m, $d, $y)) {
            return null;
        }

        return \DateTimeImmutable::createFromFormat('Y-m-d', $date) ?: null;
    }

    /**
     * Sanitizes raw user input before it is passed into a DAL filter.
     *
     * Strips HTML tags and surrounding whitespace. Returns an empty string
     * when the input contains only markup or whitespace, which callers use
     * as the signal to skip filtering entirely.
     */
    /**
     * Sanitizes raw user input before it is passed into a DAL filter.
     *
     * Strips script/style tag content (including the text inside them) and all
     * remaining HTML tags, then trims surrounding whitespace. Returns an empty
     * string when the result is blank, which callers use as the signal to skip
     * filtering entirely.
     *
     * Note: PHP's strip_tags() removes tags but keeps inner text, so
     * "<script>alert(1)</script>foo" would become "alert(1)foo". The regex
     * pre-pass removes the content of script and style blocks before strip_tags
     * runs.
     */
    public function extractSearchTerm(string $rawInput): string
    {
        $stripped = preg_replace('/<(script|style)[^>]*>.*?<\/\1>/si', '', $rawInput);

        return trim(strip_tags($stripped ?? $rawInput));
    }
}
