# AlengoOrderSearch

Shopware 6 Plugin, das der Bestellhistorie im Kundenbereich (Account → Bestellungen)
eine Suchfunktion hinzufügt.

## Funktionalität

Ein einzelnes Suchfeld ermöglicht die gleichzeitige Suche über:

- **Bestellername** – Vor- und Nachname des Bestellers
- **E-Mail-Adresse** – E-Mail des Bestellers (`orderCustomer.email`)
- **Produktname** – Bezeichnung der Bestellpositionen (lineItems.label)
- **Lieferadresse** – Straße, Stadt und PLZ der Lieferadresse

Die Suche arbeitet mit **OR-Logik**: Eine Bestellung erscheint in den Ergebnissen,
wenn der Suchbegriff in mindestens einem der Felder vorkommt.

## Technische Details

| Eigenschaft   | Wert                                    |
|---------------|-----------------------------------------|
| Shopware      | 6.5.8+                                  |
| PHP           | 8.1+                                    |
| JavaScript    | Keins – kein Build-Schritt erforderlich |
| Hook-Punkt    | `OrderRouteRequestEvent`                |
| Seitenladung  | Vollständig serverseitig (Page-Reload)  |

## Installation

```bash
bin/console plugin:refresh
bin/console plugin:install AlengoOrderSearch
bin/console plugin:activate AlengoOrderSearch
bin/console cache:clear
```

## Verwendung

Nach der Aktivierung erscheint oberhalb der Bestellliste ein Suchfeld.
Der Kunde gibt einen Suchbegriff ein und bestätigt mit Enter oder dem Suchen-Button.
Ein Reset-Link erscheint solange eine aktive Suche vorhanden ist.

## Architektur

```
OrderRouteRequestEvent
        │
        ▼
OrderSearchSubscriber::onOrderRouteRequest()
        │  liest query-Parameter 'search'
        ▼
OrderSearchService::extractSearchTerm()
        │  bereinigt Eingabe (trim, strip_tags)
        ▼
OrderSearchService::addSearchCriteria()
        │  ergänzt Criteria um MultiFilter(OR)
        ▼
AbstractOrderRoute (Shopware Core)
        │  führt gefilterte Datenbankabfrage aus
        ▼
AccountOrderPage mit gefilterten Bestellungen
```

## Dateistruktur

```
AlengoOrderSearch/
├── composer.json
├── README.md
├── src/
│   ├── AlengoOrderSearch.php
│   ├── Service/
│   │   └── OrderSearchService.php
│   ├── Subscriber/
│   │   └── OrderSearchSubscriber.php
│   └── Resources/
│       ├── config/
│       │   └── services.xml
│       ├── snippet/
│       │   ├── de_DE/storefront.de-DE.json
│       │   └── en_GB/storefront.en-GB.json
│       └── views/
│           └── storefront/page/account/order-history/
│               └── index.html.twig
└── tests/
    └── Unit/
        └── Service/
            └── OrderSearchServiceTest.php
```

## Changelog

### [1.1.0] – 2026-03-17

#### Added
- Suche nach E-Mail-Adresse des Bestellers (`orderCustomer.email`)

### [1.0.0] – 2026-03-16

#### Added
- Suchfeld in der Bestellhistorie (Account → Bestellungen)
- Suche nach Bestellername (Vor- und Nachname)
- Suche nach Produktname (Bestellposition)
- Suche nach Lieferadresse (Straße, Stadt, PLZ)
- Reset-Link zum Aufheben der aktiven Suche
- Übersetzungen Deutsch (`de_DE`) und Englisch (`en_GB`)
