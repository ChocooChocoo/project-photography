/**
 * One shared email format check for every screen that asks for an email address.
 * Publish one global name, window.PlatinumEmail, with one member, isValid(value).
 * The check accepts the same plain address shape on the client that the server accepts:
 * one "@", a non-empty local part, and a domain with at least one dot.
 * A label on either side of every dot must not be empty.
 */
(function () {
    'use strict';

    function isValid(value) {
        if (typeof value !== 'string') {
            return false;
        }

        var parts = value.trim().split('@');

        // Exactly one "@" leaves two parts.
        if (parts.length !== 2) {
            return false;
        }

        var localPart = parts[0];
        var domain = parts[1];

        // The local part must not be empty.
        if (localPart.length === 0) {
            return false;
        }

        // A space is not part of a plain address. The server email rule rejects it.
        if (/\s/.test(localPart) || /\s/.test(domain)) {
            return false;
        }

        var labels = domain.split('.');

        // The domain needs at least one dot, so at least two labels.
        if (labels.length < 2) {
            return false;
        }

        // Every label on either side of a dot must not be empty.
        return labels.every(function (label) {
            return label.length > 0;
        });
    }

    if (!window.PlatinumEmail) {
        window.PlatinumEmail = { isValid: isValid };
    }
})();
