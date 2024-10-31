(function (factory) {
    // Making your jQuery plugin work better with npm tools
    // http://blog.npmjs.org/post/112712169830/making-your-jquery-plugin-work-better-with-npm
    if (typeof module === "object" && typeof module.exports === "object") {
        factory(require("jquery"), window, document);
    } else {
        factory(jQuery, window, document);
    }
}(function ($, window, document, undefined) {

    // Define namespace
    if (!$.sabres) {
        $.sabres = {};
    }

    // Class constructor
    $.sabres = function (element, options) {
        this._name = "Sabres";
        this.element = element;
        this.options = $.sabres.setOptions(options);
    };

    // Class prototype
    $.sabres.prototype = {
        constructor: $.sabres
    };

    // Initialization logic
    $.sabres.init = function (options) {
        $.sabres.setOptions(options);

        // Restore the last admin API state
        $.sabres.admin.isAuthenticated();

        // Initialize links and menus
        //$.sabres.links.goToButton();
        //$.sabres.links.reportsMenu();
    };

    // Options getter and setter
    $.sabres.getOptions = function () {
        return this.options;
    };
    $.sabres.setOptions = function (options) {
        this.options = $.extend({}, $.sabres.defaults, options);
    };

    // Admin API methods
    $.sabres.admin = {
        _isAuthenticated: null,
        /**
         * Logins to the admin panel API.
         * @param {object} successCallback
         * @param {object} errorCallback
         * @returns {jqXHR}
         */
        authenticateAPI: function (successCallback, errorCallback) {
            return $.sabres.ajax.callAdminAPI('/loginAdminPanel', {apiKey: $.sabres.options.apiToken},
                    successCallback, errorCallback);
        },
        /**
         * Returns current admin API state, and optionally, sets a new API state.
         * @param {mixed} authenticated
         * @returns {bool}
         */
        isAuthenticated: function (authenticated) {
            if (typeof authenticated !== 'undefined') {
                $.sabres.admin._isAuthenticated = authenticated ? 1 : '';
                localStorage && localStorage.setItem('sbsApiAuthenticated', $.sabres.admin._isAuthenticated);
            } else if (null === $.sabres.admin._isAuthenticated) {
                $.sabres.admin._isAuthenticated = localStorage ? localStorage.getItem('sbsApiAuthenticated') : '';
            }

            return $.sabres.admin._isAuthenticated;
        }
    };

    // AJAX methods
    $.sabres.ajax = {
        _isLoginRetryAllowed: (function () {
            var lastFailedLoginAttempt = localStorage ? localStorage.getItem('lastFailedLoginAttempt') : 0;

            return function () {
                var thisFailedLoginAttempt = (new Date()).getTime();
                var diffFailedLoginAttempts = thisFailedLoginAttempt - lastFailedLoginAttempt;

                // Update lastFailedLoginAttempt to current time
                lastFailedLoginAttempt = thisFailedLoginAttempt;
                localStorage && localStorage.setItem('lastFailedLoginAttempt', lastFailedLoginAttempt);
                return (diffFailedLoginAttempts > 5 * 60 * 1000);
            };
        })(),
        /**
         * Logins to the admin panel API.
         * @param {object} successCallback
         * @param {object} errorCallback
         * @returns {jqXHR}
         */
        authenticateAPI: function (successCallback, errorCallback) {
            return $.sabres.ajax.callAdminAPI('/loginAdminPanel', {apiKey: $.sabres.options.apiToken},
                    successCallback, errorCallback);
        },
        /**
         * Performs an AJAX request to admin API.
         * @param {string} path
         * @param {object} params
         * @param {object} successCallback
         * @param {object} errorCallback
         * @returns {jqXHR}
         */
        callAdminAPI: function (path, params, successCallback, errorCallback) {
            if (!$.sabres.options.adminApiURL) {
                $.error('Setting option "adminApiURL" is not defined.');
            }

            var jqXHR = $.ajax({
                type: 'POST',
                url: $.sabres.options.adminApiURL + path,
                data: $.extend({}, params, {
                    websiteClientToken: $.sabres.options.clientToken
                }),
                xhrFields: {
                    withCredentials: true
                },
                dataFilter: $.sabres.ajax.dataFilter
            });

            jqXHR.done(function (data, textStatus) {
                var response = $.sabres.util.parseIfString(data);
                $.sabres.options.debug && console.log('[callAdminAPI.done]', path, response);

                switch (response && response.result) {
                    case 'authentication-fail':
                    case 'authentication-error':
                    case 'no_authenticated':
                    case 'failed':
                    case 'fail':
                        $.sabres.admin.isAuthenticated(false);
                        console.log('Failed authentication login attempt.');

                        // Exit when called from authenticateAPI()
                        if (path === '/loginAdminPanel') {
                            return false;
                        } else if (typeof errorCallback === 'function') {
                            errorCallback(null, jqXHR, textStatus);
                        }

                        // Exit if last failed login attempt was less than 5 minutes ago.
                        if (!$.sabres.ajax._isLoginRetryAllowed()) {
                            return false;
                        }

                        // Retry login and if successful, re-execute the original failed API that triggered the login.
                        $.sabres.admin.authenticateAPI(function () {
                            if ($.sabres.admin.isAuthenticated()) {
                                $.sabres.ajax.callAdminAPI(path, params, successCallback, errorCallback);
                            }
                        });
                        return false; // exit

                    case 'success':
                        // Change API-auth status
                        if (path === '/loginAdminPanel') {
                            $.sabres.admin.isAuthenticated(true);
                        }
                        break;
                }

                if (typeof successCallback === 'function') {
                    successCallback(response, jqXHR, textStatus);
                }
            });

            jqXHR.fail(function (jqXHR, textStatus) {
                $.sabres.options.debug && console.log('[callAdminAPI.fail]', path, textStatus);

                $.sabres.admin.isAuthenticated(false);
                console.log('Error communicating server: ' + textStatus);

                if (typeof errorCallback === 'function') {
                    errorCallback(null, jqXHR, textStatus);
                }

                // Exit if last failed login attempt was less than 5 minutes ago
                if (!$.sabres.ajax._isLoginRetryAllowed()) {
                    return false;
                }

                // Retry login and if successful, re-execute the original
                // failed API that triggered the login.
                $.sabres.admin.authenticateAPI();
                if ($.sabres.admin.isAuthenticated()) {
                    $.ajax(this); // success, re-execute
                } else {
                    return false; // failed, exit
                }
            });

            return jqXHR;
        },
        /**
         * Performs an AJAX request to portal API.
         * @param {string} path
         * @param {object} params
         * @param {object} successCallback
         * @param {object} errorCallback
         * @returns {jqXHR}
         */
        callPortalAPI: function (path, params, successCallback, errorCallback) {
            if (!$.sabres.options.portalApiURL) {
                $.error('Setting option "portalApiURL" is not defined.');
            }

            var jqXHR = $.ajax({
                type: 'POST',
                url: $.sabres.options.portalApiURL + path,
                data: $.extend({}, params, {
                    apiToken: $.sabres.options.apiToken,
                    websiteClientToken: $.sabres.options.clientToken
                }),
                xhrFields: {
                    withCredentials: true
                },
                dataFilter: $.sabres.ajax.dataFilter
            });

            jqXHR.done(function (data, textStatus) {
                var response = $.sabres.util.parseIfString(data);
                $.sabres.options.debug && console.log('[callPortalAPI.done]', path, response);

                if (typeof successCallback === 'function') {
                    successCallback(response, jqXHR, textStatus);
                }
            });

            jqXHR.fail(function (jqXHR, textStatus) {
                $.sabres.options.debug && console.log('[callPortalAPI.fail]', path, textStatus);

                if (typeof errorCallback === 'function') {
                    errorCallback(null, jqXHR, textStatus);
                }
            });

            return jqXHR;
        },
        // Response pre-filtering
        dataFilter: function (data) {
            if (data === 'no_authenticated') {
                data = {result: data};
            }

            return data;
        }
    };

    // Checkbox methods
    $.sabres.checkboxes = {
        /**
         * Returns true if checkboxes in the same state.
         * @param {object} $checkboxes
         * @returns {bool}
         */
        isInSameState: function ($checkboxes) {
            var checkedState = null, inSameState = true;
            $checkboxes = typeof $checkboxes !== 'undefined' ? $($checkboxes) : $(this);
            $checkboxes.each(function () {
                if (null === checkedState) {
                    checkedState = this.checked;
                } else if (checkedState !== this.checked) {
                    inSameState = false;
                    return false;
                }
            });

            return inSameState;
        },
        /**
         * Toggles or sets the visibility of "Select All" checkboxes.
         * @param {object} $parent
         * @param {bool} status
         */
        enableAll: function ($parent, status, force) {
            var $parentItem, $children, isParentChecked;
            $parent = typeof $parent !== 'undefined' ?
                    ($parent instanceof jQuery ? $parent : $($parent)) : $(this);
            $parentItem = $parent.closest('.feature-item');
            $children = $parentItem.next('.child-features').children();
            isParentChecked = typeof status !== 'undefined' ? status : $parent.is(':checked');
            if (isParentChecked) {
                force && $parentItem.removeClass('disabled-feature');
                $children.removeClass('disabled-feature');
            } else {
                force && $parentItem.addClass('disabled-feature');
                $children.addClass('disabled-feature');
            }
        },
        /**
         * Toggles or sets the check-status of "Select All" checkboxes.
         * @param {object} $parent
         * @param {bool} status
         * @param {bool} force
         */
        selectAll: function ($parent, status, force) {
            var $children, isParentChecked;
            $parent = typeof $parent !== 'undefined' ?
                    ($parent instanceof jQuery ? $parent : $($parent)) : $(this);
            $children = $parent.closest('.feature-item').next('.child-features').find('[type=checkbox]');
            isParentChecked = typeof status !== 'undefined' ? status : $parent.is(':checked');
            $parent.prop('checked', isParentChecked);
            if (force || $.sabres.checkboxes.isInSameState($children)) {
                $children.prop('checked', isParentChecked);
            }
        }
    };

    // Cookies methods
    $.sabres.cookie = {
        set: function (cname, cvalue, exdays) {
            var d = new Date();
            d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
            var expires = "expires=" + d.toUTCString();
            document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/wp-admin";
        },
        get: function (cname) {
            var name = cname + "=";
            var decodedCookie = decodeURIComponent(document.cookie);
            var ca = decodedCookie.split(';');
            for (var i = 0; i < ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0) === ' ') {
                    c = c.substring(1);
                }
                if (c.indexOf(name) === 0) {
                    return c.substring(name.length, c.length);
                }
            }

            return false;
        }
    };

    // Links methods
    $.sabres.links = {
        openPortal: function (target, websiteID) {
            var linkAction = target.data('portal-action');
            var portalUrl = $.sabres.options.basePortalURL;
            var url;

            switch ( linkAction ) {
                case "activity-report":
                    var timestampEnd = Math.round((new Date()).getTime() / 1000);
                    var timestampStart = timestampEnd - 604800;
                    url = portalUrl + '/api/sites/' + websiteID + '/activityReport?start=' + timestampStart + '&end=' + timestampEnd;
                    window.location.href = url;
                    break;
                case "status-report":
                    url = portalUrl + '/api/sites/' + websiteID + '/scansReport?format=pdf';
                    window.location.href = url;
                    break;
                case "investigate-traffic":
                    url = portalUrl + '/#/sites/' + websiteID + '/nav/investigate';
                    window.location.href = url;
                    break;
                case "investigate-ip":
                    var ip = $(target).data('investigate-ip');
                    url = portalUrl + '/#/sites/' + websiteID + '/nav/investigate/' + ip;
                    window.location.href = url;
                    break;
                case "fix-issues":
                    url = portalUrl + '/#/sites/' + websiteID + '/nav/fix-issues';
                    window.location.href = url;
                    break;

            }
        },
        portalLink: function (event) {
            if ( $.sabres.options.isRegistered ) {
                $.sabres.ajax.callPortalAPI('/wpAdminIsLoggedIn', {}, function (response) {
                    if ( !response.result || !localStorage.getItem('customerPortalWebsiteID') ) {
                        $.sabres.links.showPreloader();
                        $.sabres.ajax.callPortalAPI('/wpAdminLogin', {email: $.sabres.getOptions().ssoEmail},
                            function (response) {
                                if (!response || response.error) {
                                    localStorage.setItem('customerPortalLoginFailTime', Date.now());
                                    $.sabres.links.hidePreloader();
                                    $.modal.alert("Can't login to Customer Portal, please try again later", "Close").modal();
                                } else if (response.success && response.websiteID) {
                                    localStorage.setItem('customerPortalWebsiteID', response.websiteID);
                                    $.sabres.links.openPortal($(event.target), response.websiteID);
                                }
                            },
                            function () {
                                $.sabres.links.hidePreloader();
                                $.modal.alert("Can't login to Customer Portal, please try again later", "Close").modal();
                            })
                    } else {
                        $.sabres.links.openPortal($(event.target), localStorage.getItem('customerPortalWebsiteID'));
                    }
                } );
            } else {
                $.openSystemTour();
            }
        },
        showPreloader: function () {
            var preloaderBox = $("<div />", {class: "preloader-box"});
            var gif = $("<div />", {class: "preloader-image"});
            var text = $("<div />", {class: "preloader-text", text: "Please wait while we are redirecting you to Sabres customer portal"});

            preloaderBox.append(gif);
            preloaderBox.append(text);
            $(".sabres-content").append(preloaderBox);
        },
        hidePreloader: function () {
            $(".preloader-box").remove();
        }
    };

    // Utilities
    $.sabres.util = {
        parseDate: function (dateString) {
            // Check if a Unix timestamp
            if (!isNaN(dateString - parseFloat(dateString))) {
                dateString = parseFloat(dateString);
                if (dateString <= 4294967295) { // MAX_INT
                    dateString = Math.round(dateString * 1000); // convert to ms
                }
                return dateString;
            }

            return Date.parse(dateString);
        },
        parseDateUtcToLocalTime: function(dateString) {
            // Parse date and specify UTC local time
            var localTime = Date.parse(dateString + ' UTC');

            // Convert to locale time in 24-hour format
            return new Date(localTime).toLocaleTimeString('en-GB').substring(0, 5);
        },
        parseIfString: function (data) {
            if (typeof data === 'string' || data instanceof String) {
                if (!data || data.length === 0 || !data.trim())
                    return {};
                data = JSON.parse(data);
            }

            return data;
        }
    };

    // Default settings
    $.sabres.defaults = {
        debug: false,
        clientToken: '',
        apiToken: '',
        adminApiURL: '',
        portalApiURL: '',
        basePortalURL: '',
        isFirstActivation: true,
        shouldReactivate: true,
        isPremium: false,
        isRegistered: false,
        ssoEmail: ''
    };

    // Plugin wrapper around the constructor, 
    // preventing against multiple instantiations
    $.fn.sabres = function (options) {
        return this.each(function () {
            (new $.sabres(this, options));
        });
    };

}));
