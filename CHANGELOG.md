# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [1.3.0] – 2026-03-18

### Added
- Date range filter (from / to) for the order date (`order.orderDateTime`).
  Two date picker fields (powered by Shopware's native `DatePickerPlugin` / Flatpickr) appear below the text search field — with locale-aware calendar popup and keyboard input support.
- GET parameters `dateFrom` and `dateTo` (YYYY-MM-DD) are read by
  `OrderSearchSubscriber` and applied as `RangeFilter` (GTE / LTE) via the new
  `OrderSearchService::addDateRangeCriteria()` method. Invalid date strings are
  silently ignored.
- Pagination now preserves `dateFrom` and `dateTo` in addition to `search` — the
  JS plugin patches all three parameters into the pagination form's action URL.
- Reset link is now shown whenever any of `search`, `dateFrom`, or `dateTo` is
  set (previously only triggered by `search`).
- New snippet keys: `dateFromLabel`, `dateToLabel`, `dateRangeAriaLabel`
  (de_DE and en_GB).

## [1.2.1] – 2026-03-18

### Fixed
- JS plugin: replaced `onUnmount()` with the correct Shopware lifecycle method
  `disconnect()` so the `MutationObserver` is properly disconnected when the
  plugin instance is torn down (prevents a memory leak).

## [1.2.0] – 2026-03-17

### Added
- JS plugin `AlengoOrderSearchPlugin` that preserves the active search term
  when navigating between result pages via the pagination.
- `main.js` entry point for Shopware Storefront plugin registration.
- `data-alengo-order-search="true"` attribute on the search form container to
  mount the JS plugin via `PluginManager`.

### Changed
- Pagination search-parameter handling patches the `action` attribute of
  `.account-orders-pagination-form` to append `?search=term`. Shopware's
  pagination uses a POST form with radio inputs — there are no `<a>` links to
  intercept. A `MutationObserver` on `.account-orders-main` re-patches the form
  after each AJAX content replacement.

## [1.1.0] – 2026-03-17

### Added
- Search by customer e-mail address (`orderCustomer.email`).

## [1.0.0] – 2026-03-16

### Added
- Search field above the order list in the customer account (Account → Orders).
- Search by customer name (first and last name).
- Search by product name (line item label).
- Search by shipping address (street, city, ZIP code).
- Reset link to clear an active search query.
- Translations for German (`de_DE`) and English (`en_GB`).
