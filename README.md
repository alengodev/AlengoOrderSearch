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

| Eigenschaft   | Wert                                                |
|---------------|-----------------------------------------------------|
| Shopware      | 6.5.8+                                              |
| PHP           | 8.1+                                                |
| JavaScript    | Ja – Build via `bin/build-storefront.sh` erforderlich |
| Hook-Punkt    | `OrderRouteRequestEvent`                            |
| Seitenladung  | Serverseitig (Page-Reload); Pagination clientseitig gepatcht |

## Installation

```bash
bin/console plugin:refresh
bin/console plugin:install AlengoOrderSearch
bin/console plugin:activate AlengoOrderSearch
bin/console cache:clear

# Storefront neu bauen, damit das JS-Plugin eingebunden wird
bin/build-storefront.sh
```

## Verwendung

Nach der Aktivierung erscheint oberhalb der Bestellliste ein Suchfeld.
Der Kunde gibt einen Suchbegriff ein und bestätigt mit Enter oder dem Suchen-Button.
Ein Reset-Link erscheint solange eine aktive Suche vorhanden ist.

## Architektur

### Serverseitige Suchverarbeitung

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

### Clientseitige Pagination-Korrektur

Shopware generiert Pagination-Links ohne Kenntnis des `search`-Parameters.
Das JS-Plugin liest den Parameter aus der aktuellen URL und hängt ihn an alle
`.pagination-nav a`-Links an, sodass bei Seitenwechsel der Suchbegriff erhalten bleibt.
Ein `MutationObserver` reagiert auf DOM-Änderungen und patcht nachträglich eingefügte
Pagination-Elemente (z.B. bei AJAX-Seitennavigation).

```
Seitenaufruf mit ?search=…
        │
        ▼
AlengoOrderSearchPlugin.init()
        │  liest search-Parameter aus window.location.search
        ▼
_applyToLinks()
        │  setzt search-Parameter auf alle .pagination-nav a
        ▼
_watchForUpdates() – MutationObserver
        │  beobachtet DOM-Änderungen
        ▼
_applyToLinks() (erneut bei neuen DOM-Elementen)
```

## Dateistruktur

```
AlengoOrderSearch/
├── composer.json
├── README.md
├── CHANGELOG.md
├── src/
│   ├── AlengoOrderSearch.php
│   ├── Service/
│   │   └── OrderSearchService.php
│   ├── Subscriber/
│   │   └── OrderSearchSubscriber.php
│   └── Resources/
│       ├── app/
│       │   └── storefront/
│       │       └── src/
│       │           ├── main.js                          ← Plugin-Registrierung
│       │           └── alengo-order-search/
│       │               └── alengo-order-search.plugin.js ← Pagination-Patch
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

See [CHANGELOG.md](CHANGELOG.md).
