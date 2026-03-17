<?php declare(strict_types=1);

namespace AlengoOrderSearch\Subscriber;

use AlengoOrderSearch\Service\OrderSearchService;
use Shopware\Storefront\Event\RouteRequest\OrderRouteRequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Lauscht auf das OrderRouteRequestEvent und ergänzt die Criteria
 * um Suchfilter, wenn ein Suchbegriff im Request vorhanden ist.
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
     * Liest den search-Parameter aus dem Storefront-Request und
     * ergänzt die Criteria, falls ein nicht-leerer Suchbegriff vorliegt.
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
