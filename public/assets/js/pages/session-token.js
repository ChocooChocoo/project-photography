/**
 * Shares one CSRF token client for the whole application.
 *
 * window.PlatinumSession.token()   reads the live token from the page.
 * window.PlatinumSession.refresh() fetches a fresh token from the server.
 * window.PlatinumSession.post()    posts data and retries it one time after a 419.
 *
 * The script also installs one recovery rule for every jQuery request, so the
 * existing screens recover from a stale token without a page reload.
 */
(function (window, document) {
    'use strict';

    if (window.PlatinumSession) {
        return;
    }

    var ENDPOINT = '/csrf-token';
    var META_NAME = 'csrf-token';
    var RETRY_FLAG = 'platinumRetried';
    var OWN_RETRY_FLAG = 'platinumOwnRetry';
    var recoveryInstalled = false;

    function jquery() {
        return window.jQuery || window.$ || null;
    }

    // Read the token at the moment of the call. Do not cache the value.
    function token() {
        var meta = document.querySelector('meta[name="' + META_NAME + '"]');

        return meta ? (meta.getAttribute('content') || '') : '';
    }

    function storeToken(value) {
        if (!value) {
            return;
        }

        var meta = document.querySelector('meta[name="' + META_NAME + '"]');

        if (!meta) {
            meta = document.createElement('meta');
            meta.setAttribute('name', META_NAME);
            document.head.appendChild(meta);
        }

        meta.setAttribute('content', value);
    }

    // Fetch a live token. The response must never be cached.
    function refresh() {
        var $ = jquery();
        var deferred = $.Deferred();

        fetch(ENDPOINT, {
            method: 'GET',
            cache: 'no-store',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('Token refresh failed with status ' + response.status);
            }

            return response.json();
        }).then(function (payload) {
            storeToken(payload && payload.token);
            deferred.resolve(token(), payload);
        }, function (error) {
            deferred.reject(error);
        });

        return deferred.promise();
    }

    // Put the live token on a request that is about to be sent again.
    function applyLiveToken(settings) {
        var value = token();

        if (!value) {
            return settings;
        }

        settings.headers = settings.headers || {};
        settings.headers['X-CSRF-TOKEN'] = value;

        var data = settings.data;

        if (typeof data === 'string' && data.indexOf('_token=') !== -1) {
            settings.data = data.replace(/(^|&)_token=[^&]*/, '$1_token=' + encodeURIComponent(value));
        } else if (window.FormData && data instanceof window.FormData) {
            // A rendered @csrf field sits in the body and Laravel reads it before
            // the header. Replace it with the live token so the retry can pass.
            data.set('_token', value);
        } else if (data && typeof data === 'object') {
            data._token = value;
        }

        return settings;
    }

    function resend(settings) {
        var $ = jquery();

        settings[RETRY_FLAG] = true;
        applyLiveToken(settings);

        return $.ajax(settings);
    }

    function installRecovery() {
        var $ = jquery();

        if (!$ || recoveryInstalled) {
            return;
        }

        recoveryInstalled = true;

        // Every non-GET request carries the live token.
        $.ajaxSetup({
            beforeSend: function (xhr, settings) {
                var method = String(settings.type || settings.method || 'GET').toUpperCase();

                if (method === 'GET' || method === 'HEAD') {
                    return;
                }

                var value = token();

                if (value) {
                    xhr.setRequestHeader('X-CSRF-TOKEN', value);
                }
            }
        });

        // A 419 response gets one retry with a fresh token. The flag stops a loop.
        $(document).ajaxError(function (event, xhr, settings) {
            if (!xhr || xhr.status !== 419 || !settings) {
                return;
            }

            if (settings[RETRY_FLAG] || settings[OWN_RETRY_FLAG]) {
                return;
            }

            refresh().done(function () {
                resend(settings);
            });
        });
    }

    // Send a request. Retry it one time after a 419 response.
    function post(url, data) {
        var $ = jquery();
        var settings = {
            url: url,
            type: 'POST',
            data: data,
            dataType: 'json',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        settings[OWN_RETRY_FLAG] = true;

        return $.ajax(settings).then(null, function (xhr) {
            if (!xhr || xhr.status !== 419) {
                return $.Deferred().reject(xhr).promise();
            }

            var deferred = $.Deferred();

            refresh().done(function () {
                resend(settings).done(function (payload, textStatus, jqXhr) {
                    deferred.resolve(payload, textStatus, jqXhr);
                }).fail(function (jqXhr, textStatus, errorThrown) {
                    deferred.reject(jqXhr, textStatus, errorThrown);
                });
            }).fail(function () {
                deferred.reject(xhr);
            });

            return deferred.promise();
        });
    }

    window.PlatinumSession = {
        token: token,
        refresh: refresh,
        post: post
    };

    installRecovery();
})(window, document);
