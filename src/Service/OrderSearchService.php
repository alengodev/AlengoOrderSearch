<?php declare(strict_types=1);

namespace AlengoOrderSearch\Service;

use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;

/**
 * Stellt Such-Logik für die Bestellhistorie bereit.
 *
 * Erweitert eine Shopware DAL Criteria um OR-verknüpfte ContainsFilter
 * auf Bestellername, E-Mail-Adresse, Produktname und Lieferadresse.
 */
class OrderSearchService
{
    /**
     * Ergänzt die Criteria um Such-Filter für Bestellername, E-Mail-Adresse, Produktname und Lieferadresse.
     *
     * Alle Filter werden mit OR-Logik verknüpft: Ein Treffer in einem der Felder genügt.
     * Die Association deliveries.shippingOrderAddress wird automatisch hinzugefügt,
     * da sie für die Adresssuche benötigt wird.
     *
     * @param string   $searchTerm Suchbegriff (wird intern getrimmt)
     * @param Criteria $criteria   Zu erweiternde Criteria-Instanz (wird direkt modifiziert)
     *
     * @return Criteria Die modifizierte Criteria-Instanz
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
     * Extrahiert und bereinigt den Suchbegriff aus einer rohen Eingabe.
     *
     * Entfernt HTML-Tags und führendes/nachfolgendes Whitespace.
     *
     * @param string $rawInput Rohe Eingabe aus dem Request
     *
     * @return string Bereinigter Suchbegriff
     */
    public function extractSearchTerm(string $rawInput): string
    {
        return trim(strip_tags($rawInput));
    }
}
