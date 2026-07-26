/**
 * app-globals.js - Expone baseUrl y window.APP a partir de las meta tags
 * emitidas por views/layouts/header.php, para que estén disponibles antes
 * de cualquier otro script del documento.
 */
(function () {
    function metaContent(name) {
        const el = document.querySelector('meta[name="' + name + '"]');
        return el ? el.content : '';
    }

    window.baseUrl = metaContent('app-url');
    window.APP = {
        name: metaContent('app-name'),
        version: metaContent('app-version'),
        currency: metaContent('app-currency')
    };
})();
