# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [1.2.0] – 2026-03-17

### Added
- JS plugin `AlengoOrderSearchPlugin` that appends the active search term to all
  pagination links, so navigating between result pages preserves the search query.
- `main.js` entry point for Shopware Storefront plugin registration.
- `data-alengo-order-search="true"` attribute on the search form container to
  mount the JS plugin via `PluginManager`.
- `MutationObserver` in the JS plugin to re-patch pagination links that are
  injected into the DOM after the initial page load (e.g. by Shopware's AJAX
  pagination).

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
