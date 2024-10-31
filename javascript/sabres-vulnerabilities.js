jQuery(function ($) {

    // Set options, init sabres
    $.sabres.init(sbs_admin_data);
    var sabresData = $.sabres.getOptions();
    
    var intervalAPIs = setInterval(executeTimedAPIs, 1000 * 60 * 2);

    authenticateAPI();
    executeTimedAPIs();

    function authenticateAPI() {
        if ($.sabres.admin.isAuthenticated() || sabresData.shouldReactivate) {
            return;
        }

        $.sabres.admin.authenticateAPI();
    }

    function vulnerabilitiesAPI() {
        updateVulnerabilities();

        if (!$.sabres.admin.isAuthenticated() || sabresData.shouldReactivate) {
            return;
        }

        var lastUpdated;
        if (localStorage.getItem('sabresVulnerabilities')) {
            var localData = JSON.parse(localStorage.getItem('sabresVulnerabilities'));
            lastUpdated = localData.lastUpdated;
        } else {
            lastUpdated = null;
        }

        $.sabres.ajax.callAdminAPI('/vulnerabilities', {lastUpdated: lastUpdated},
                function (response) {
                    if (!$.isEmptyObject(response)) {
                        var items = {};

                        if (response.themes && response.plugins) {
                            items = Object.assign(response.themes, response.plugins);
                        }

                        var serverDate = Date.parse(response.lastUpdated);
                        var localDate = Date.parse(lastUpdated);

                        if (serverDate > localDate || !localDate) {
                            localStorage.setItem('sabresVulnerabilities', JSON.stringify({
                                items: items,
                                lastUpdated: response.lastUpdated
                            }));
                            updateVulnerabilities();
                        }
                    }
                });
    }

    function updateVulnerabilities() {
        var $tableBody = $('.data-table.vulnerabilities tbody');
        $tableBody.empty();

        var $vulnerabilitiesCount = $('#vulnerabilities-count');
        $vulnerabilitiesCount.text('');

        var localData = JSON.parse(localStorage.getItem('sabresVulnerabilities'));
        if (!localData) {
            return;
        }

        var items = localData.items;
        if (!items) {
            return;
        }

        for (var prop in items) {
            if (items.hasOwnProperty(prop)) {

                if (!items[prop]) {
                    continue;
                }

                var vulnerabilities = items[prop].vulnerabilities;

                if (vulnerabilities.length) {
                    for (var i = 0; i < vulnerabilities.length; i++) {
                        var vuln = vulnerabilities[i];
                        var created = new Date(vuln.created_at);

                        var row = $("<tr />", {class: "vulnerabilities-item"});

                        row.append($("<td />", {class: "vulnerabilities-type", text: vuln.vuln_type}));
                        row.append($("<td />", {class: "vulnerabilities-description", text: vuln.title}));

                        row.append($("<td />", {
                            class: "vulnerabilities-added",
                            text: created.toLocaleString()
                        }));

                        $tableBody.append(row);
                    }
                }
            }
        }

        var count = $('.vulnerabilities-item').length;

        localData.vulnerabilitiesCount = count;
        localStorage.setItem('sabresVulnerabilities', JSON.stringify(localData));

        if (count && $vulnerabilitiesCount.length) {
            $vulnerabilitiesCount.text('(' + count + ')');
        }

        var $feedVulnerabilitiesNano = $("#feed-vulnerabilities.nano");
        if ($feedVulnerabilitiesNano.length) {
            $feedVulnerabilitiesNano.nanoScroller();
        }
    }

    function executeTimedAPIs() {
        vulnerabilitiesAPI();
    }

});