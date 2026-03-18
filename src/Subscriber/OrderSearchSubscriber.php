<?php declare(strict_types=1);

namespace AlengoOrderSearch\Subscriber;

use AlengoOrderSearch\Service\OrderSearchService;
use Shopware\Storefront\Event\RouteRequest\OrderRouteRequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Hooks into the order list request and injects search and date-range filters
 * into the DAL criteria.
 *
 * Listens on {@see OrderRouteRequestEvent}, which fires before Shopware's
 * {@see AbstractOrderRoute} executes its database query. The search term is
 * read from the GET parameter `search`, the optional date boundaries from the
 * GET parameters `dateFrom` and `dateTo` (both YYYY-MM-DD format) — not from
 * POST — because the storefront pagination submits a POST form whose action URL
 * carries these parameters as a query string (appended by the JS plugin).
 */
class OrderSearchSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly OrderSearchService $orderSearchService
    ) {
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            OrderRouteRequestEvent::class => 'onOrderRouteRequest',
        ];
    }

    /**
     * Reads the `search`, `dateFrom`, and `dateTo` query parameters from the
     * storefront request and delegates criteria modification to
     * {@see OrderSearchService}.
     *
     * The text search returns early without touching the criteria when the
     * `search` parameter is absent, empty, or reduces to an empty string after
     * sanitization. The date-range filter is applied independently and tolerates
     * null values for either bound — invalid date strings are silently ignored
     * inside {@see OrderSearchService::addDateRangeCriteria()}.
     */
    public function onOrderRouteRequest(OrderRouteRequestEvent $event): void
    {
        $request = $event->getStorefrontRequest();

        // Text search
        $rawSearchTerm = $request->query->get('search', '');

        if (is_string($rawSearchTerm) && $rawSearchTerm !== '') {
            $searchTerm = $this->orderSearchService->extractSearchTerm($rawSearchTerm);

            if ($searchTerm !== '') {
                $this->orderSearchService->addSearchCriteria($searchTerm, $event->getCriteria());
            }
        }

        // Date range filter
        $rawDateFrom = $request->query->get('dateFrom');
        $rawDateTo   = $request->query->get('dateTo');

        $dateFrom = is_string($rawDateFrom) ? $rawDateFrom : null;
        $dateTo   = is_string($rawDateTo)   ? $rawDateTo   : null;

        $this->orderSearchService->addDateRangeCriteria($dateFrom, $dateTo, $event->getCriteria());
    }
}
