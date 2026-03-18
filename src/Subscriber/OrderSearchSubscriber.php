<?php declare(strict_types=1);

namespace AlengoOrderSearch\Subscriber;

use AlengoOrderSearch\Service\OrderSearchService;
use Shopware\Storefront\Event\RouteRequest\OrderRouteRequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Hooks into the order list request and injects search filters into the DAL criteria.
 *
 * Listens on {@see OrderRouteRequestEvent}, which fires before Shopware's
 * {@see AbstractOrderRoute} executes its database query. The search term is
 * read from the GET parameter `search` — not from POST — because the
 * storefront pagination submits a POST form whose action URL carries the
 * search term as a query string (appended by the JS plugin).
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
     * Reads the `search` query parameter from the storefront request and
     * delegates criteria modification to {@see OrderSearchService}.
     *
     * Returns early without touching the criteria when the parameter is absent,
     * empty, or reduces to an empty string after sanitization.
     */
    public function onOrderRouteRequest(OrderRouteRequestEvent $event): void
    {
        $request = $event->getStorefrontRequest();
        $rawSearchTerm = $request->query->get('search', '');

        if (!is_string($rawSearchTerm) || $rawSearchTerm === '') {
            return;
        }

        $searchTerm = $this->orderSearchService->extractSearchTerm($rawSearchTerm);

        if ($searchTerm === '') {
            return;
        }

        $this->orderSearchService->addSearchCriteria($searchTerm, $event->getCriteria());
    }
}
