import AlengoOrderSearchPlugin from './alengo-order-search/alengo-order-search.plugin';

window.PluginManager.register(
    'AlengoOrderSearch',
    AlengoOrderSearchPlugin,
    '[data-alengo-order-search]'
);
