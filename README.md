# AlengoOrderSearch

Shopware 6 Plugin, das der Bestellhistorie im Kundenbereich (Account → Bestellungen)
eine Suchfunktion hinzufügt.

## Funktionalität

Ein Suchfeld und ein Datumsbereich-Filter ermöglichen die Einschränkung der Bestellliste:

### Textsuche

Gleichzeitige Suche über alle folgenden Felder (OR-Logik):

- **Bestellername** – Vor- und Nachname des Bestellers
- **E-Mail-Adresse** – E-Mail des Bestellers (`orderCustomer.email`)
- **Produktname** – Bezeichnung der Bestellpositionen (lineItems.label)
- **Lieferadresse** – Straße, Stadt und PLZ der Lieferadresse

Eine Bestellung erscheint in den Ergebnissen, wenn der Suchbegriff in mindestens
einem der Felder vorkommt.

### Datumsbereich-Filter

Zwei optionale Datumsfelder ("Von" / "Bis") filtern nach dem Bestelldatum
(`order.orderDateTime`):

- Nur "Von" → Bestellungen ab diesem Tag (00:00:00)
- Nur "Bis" → Bestellungen bis zu diesem Tag (23:59:59)
- Beide Felder → geschlossenes Intervall
- Beide leer → kein Datumsfilter aktiv

Textsuche und Datumsfilter können unabhängig voneinander oder kombiniert
verwendet werden. Der Reset-Link erscheint, sobald mindestens ein Filter aktiv ist.

## Technische Details

| Eigenschaft   | Wert                                                |
|---------------|-----------------------------------------------------|
| Shopware      | 6.5.8+                                              |
| PHP           | 8.3+                                                |
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
        │  liest query-Parameter 'search', 'dateFrom', 'dateTo'
        ├──► OrderSearchService::extractSearchTerm()
        │            bereinigt Eingabe (trim, strip_tags)
        │    OrderSearchService::addSearchCriteria()
        │            ergänzt Criteria um MultiFilter(OR) [wenn search gesetzt]
        │
        └──► OrderSearchService::addDateRangeCriteria()
                     ergänzt Criteria um RangeFilter(GTE) und/oder RangeFilter(LTE)
                     auf orderDateTime [wenn dateFrom und/oder dateTo gesetzt]
        ▼
AbstractOrderRoute (Shopware Core)
        │  führt gefilterte Datenbankabfrage aus
        ▼
AccountOrderPage mit gefilterten Bestellungen
```

### Clientseitige Pagination-Korrektur

Die Shopware-Pagination in der Bestellhistorie ist kein Link-basiertes Markup,
sondern ein POST-Formular (`.account-orders-pagination-form`) mit Radio-Inputs
für die Seitennummer, das via `data-form-ajax-submit` abgeschickt wird.
Ein Link-Interceptor auf `.pagination-nav a` funktioniert hier daher nicht —
es gibt keine `<a>`-Elemente in der Pagination.

Das JS-Plugin setzt stattdessen auf Form-Action-Patching: Der `action`-URL des
Formulars wird der `search`-Parameter als Query-String angehängt. Da POST-Formulare
Query-Parameter in der Action-URL als GET-Parameter an den Server übermitteln,
liest `OrderSearchSubscriber` den Wert korrekt via `$request->query->get('search')`.

Ein `MutationObserver` auf `.account-orders-main` erkennt, wenn Shopwares AJAX-Layer
den Bestelllisten-Container nach einem Pagination-Submit ersetzt, und patcht das
neu eingefügte Formular erneut.

```
Seitenaufruf mit ?search=…&dateFrom=…&dateTo=…
        │
        ▼
AlengoOrderSearchPlugin.init()
        │  liest search, dateFrom, dateTo aus window.location.search
        │  kein aktiver Filter → Plugin beendet sich sofort
        ▼
_patchPaginationFormAction()
        │  findet .account-orders-pagination-form
        │  setzt search, dateFrom, dateTo in action-URL (aktive Parameter)
        │  entfernt inaktive Parameter aus der URL
        ▼
_watchForAjaxUpdates()
        │  MutationObserver auf .account-orders-main (childList)
        │  bei DOM-Änderung → _patchPaginationFormAction() erneut
        ▼
Nutzer klickt Seite → Formular-Submit → Server liest alle Query-Parameter
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
