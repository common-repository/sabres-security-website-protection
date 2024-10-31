
var scanInProgress = false;

jQuery(function ($) {

    // Set options, init sabres
    $.sabres.init(sbs_admin_data);
    var sabresData = $.sabres.getOptions();

    var scanBtn = $(".sabres-admin-btn.scan-btn");
    var scanProgress = $(".in-progress");
    var featureCheckbox = $(".feature-item input[type='checkbox']");
    var securityGauge = $('#safety-percent');
    var body = $('body');

    var updateFeaturesTimer;
    var scanStatusTimer = setTimeout(scanStatusAPI, 1000 * 60 * 2);

    $.featuresUpdateAction = function () {
        clearTimeout(updateFeaturesTimer);
        updateFeaturesTimer = setTimeout(function() {
            updateFeaturesAPI();

            updateSecurityGauge();
            updateSafetyText();
            updateAdminProtectionStatus();
        }, 400);
    };

    $.portalLogin = function() {
        const dueTime = 300000;
        var loginExpiry = localStorage.getItem('customerPortalLoginExpiry');
        var lastFail = localStorage.getItem('customerPortalLoginExpiry');

        if ( ( ! loginExpiry || Date.now() > loginExpiry ) && ( ! lastFail || ( Date.now() - lastFail ) > dueTime ) ) {
            $.sabres.ajax.callPortalAPI('/wpAdminLogin', {email: $.sabres.options.ssoEmail},
                    function (response) {
                        if (response.error) {
                            localStorage.setItem('customerPortalLoginFailTime', Date.now());
                            $.modal.alert("Can't login to Customer Portal, please try again later", "Close").modal();
                        } else if (response.success && response.websiteID) {
                            localStorage.setItem('customerPortalLoginExpiry', Date.now() + dueTime);
                            localStorage.setItem('customerPortalWebsiteID', response.websiteID);
                        }
                    });
        }
    };

    securityGauge.circleProgress({
        startAngle: -1.45,
        size: 160,
        thickness: 14,
        lineCap: 'round',
        animation: false,
        fill: {
            color: "rgba(0, 0, 0, .3)",
            image: $.sabres.options.pluginURL+"/images/gauge.png"
        }
    }).on('circle-inited', function () {
        $(this).find('p').html('<span>' + Math.round(100 * $(this).circleProgress("value")) + '</span><i>%</i>');
    });

    loadQuickFeatures();
    authenticateAPI();
    scanStatusAPI();
    updateAdminProtectionStatus();
    updateHardeningStatus();

    if ( sabresData.isRegistered ) {
        $.portalLogin();
    }

    $(window).on("beforeunload unload", function () {
        if (updateFeaturesTimer) {
            clearTimeout(updateFeaturesTimer);
            updateFeaturesTimer = null;
            updateFeaturesAPI();
        }
    });

    body.on('touchend click', '#reset-activation', resetActivation);

    scanBtn.on("click touchend", function () {
        scanStatusAPI(true);
    });

    featureCheckbox.on("click touchend", $.featuresUpdateAction);
    $('.tab-title').on("click touchend", switchFeedTab);
    $('.parent-switch').on("click", function () {
        $.sabres.checkboxes.selectAll(this);
        $.sabres.checkboxes.enableAll(this);
        $.featuresUpdateAction();
    });
    $('#flip-previous').on("click touchend", flipPrevious);
    $('#flip-next').on("click touchend", flipNext);
    $('#tfa-pen').on("click touchend", openEmailPage);
    body.on("click touchend", '.sabres-portal-link', $.sabres.links.portalLink);


    $('.id-admin-protection').on("click touchend", function () {
        if ( $.sabres.options.isRegistered ) {
            $.sabres.checkboxes.selectAll('#admin-protection', true, true); // force selection
            $.sabres.checkboxes.enableAll('#admin-protection');
            $('#admin-protection-quick-feature').removeClass('disabled-feature');
        }
        $.featuresUpdateAction();
        updateHardeningStatus();
    });

    $('.id-scan-now').on("click touchend", function () {
        scanStatusAPI(true);
    });

    function authenticateAPI() {
        if ( $.sabres.admin.isAuthenticated() ) {
            executeTimedAPIs();
            statusIndicatorAPI();
            return;
        }
        if ( sabresData.shouldReactivate ) {
            return;
        }

        $.sabres.admin.authenticateAPI(function() {
              executeTimedAPIs();
              statusIndicatorAPI();
        });
    }

    function scanStatusAPI(scanNow) {

        if (!$.sabres.admin.isAuthenticated() || sabresData.shouldReactivate) {
            return;
        }

        if (!scanNow) {
            scanNow = false;
        }
        var wasScanInProgress=scanInProgress;

        $.sabres.ajax.callAdminAPI('/scan-status', {scanNow: scanNow},
                function (response) {

                    if (response.result === 'scan-status-error') {
                        return;
                    }

                    if (response.status === 'Progress') {
                        scanInProgress = true;
                        clearTimeout(scanStatusTimer);
                        scanStatusTimer = setTimeout(scanStatusAPI, 1000 * 5);

                        scanProgress.css('display', 'block');
                        scanBtn.css('display', 'none');

                        updateHardeningStatus();

                        updateProgress(response.progress / 100);
                    }

                    if (response.status === 'Stopped' || response.status === 'Error') {
                        if (response.status === 'Error')
                          scanBtn.text('Scan Error');
                        else {
                          if ( scanInProgress === true ) {
                              scanBtn.text('Scan is completed!');
                          }
                        }

                        scanInProgress = false;
                        updateProgress(0);
                        clearTimeout(scanStatusTimer);
                        scanStatusTimer = setTimeout(scanStatusAPI, 1000 * 60 * 2);

                        scanProgress.find('.scan-bar').width('0px');
                        scanProgress.css('display', 'none');
                        scanBtn.css('display', 'block');

                        if (response.status === 'Stopped' && wasScanInProgress) {
                          executeTimedAPIs();
                          statusIndicatorAPI();
                        }

                        // Next available scan is in UTC time so we'll to convert it
                        // to users timezone and display correct message.
                        if (!response.message) {
                          if (response.status === 'Error' && scanNow) {
                                 response.message="Sabres experienced an error while scanning you website. Please retry later";
                          } else if (response['next-available-scan']) {
                             var localTimeString = $.sabres.util.parseDateUtcToLocalTime(response['next-available-scan']);
                             response.message = "You can not start another scan just now. You are eligable to start another scan at " + localTimeString;
                         }
                        }

                        if (response.message) {
                            $.modal.alert(response.message, "Close").modal();
                        }
                    }


                });
    }

    function statusIndicatorAPI() {

        updateIndicators();

        if (!$.sabres.admin.isAuthenticated() || sabresData.shouldReactivate) {
            return;
        }

        $.sabres.ajax.callAdminAPI('/status-indicator', {},
                function (response) {
                    if (response.result === 'site-status-indicator-error') {
                        return;
                    }

                    if (!$.isEmptyObject(response)) {
                        localStorage.setItem('sabresIndicator', JSON.stringify(response));
                        updateIndicators();
                    }
                });
    }

    function updateIndicators() {
        var sabresIndicator = $.sabres.util.parseIfString(localStorage.getItem('sabresIndicator'));
        if (!$.isEmptyObject(sabresIndicator)) {
            if ( typeof sabresIndicator.unsolvedIssues !== 'undefined' && sabresIndicator.unsolvedIssues !== null ) {
                $("#summary_unresolved").text(sabresIndicator.unsolvedIssues);
            }
            if ( typeof sabresIndicator.vulnerabilities !== 'undefined' && sabresIndicator.vulnerabilities !== null ) {
                $("#summary_vulnerabilities").text( sabresIndicator.vulnerabilities );
            }
            if ( typeof sabresIndicator.uptime !== 'undefined' && sabresIndicator.uptime !== null ) {
                $("#summary_uptime").text( sabresIndicator.uptime + '%');
            }
            if ( typeof sabresIndicator.humans !== 'undefined' && sabresIndicator.humans !== null ) {
                $("#summary_humans").text( sabresIndicator.humans + '%/' );
                $("#bots_percent").text((100 - sabresIndicator.humans).toFixed(2) + '%');
            }
            if ( typeof sabresIndicator.googleBots !== 'undefined' && sabresIndicator.googleBots !== null ) {
                $("#summary_gbots").text( sabresIndicator.googleBots );
            }
            if ( typeof sabresIndicator.humanVisitors !== 'undefined' && sabresIndicator.humanVisitors !== null ) {
                $("#summary_visitors").text( sabresIndicator.humanVisitors );
            }
        }
    }

    function updateFeaturesAPI() {
        var features = {};
        var sabresQuickFeatures = $.sabres.util.parseIfString(localStorage.getItem('sabresQuickFeatures'));
        var loadedQuickFeatures = sabresQuickFeatures;

        if (!$.sabres.admin.isAuthenticated() || sabresData.shouldReactivate) {
            refreshFeatures(sabresQuickFeatures);
            return;
        }

        featureCheckbox.each(function (index, item) {
            var key = $(item).attr('id');
            var val = $(item).is(':checked');

            if (loadedQuickFeatures[ key ] !== val) {
                features[ key ] = val;
            }
        });

        if (!$.isEmptyObject(features) && features !== loadedQuickFeatures) {

            $.sabres.ajax.callAdminAPI('/quick-feature-update', features, function () {
                localStorage.setItem('sabresQuickFeatures', JSON.stringify($.extend({}, loadedQuickFeatures, features)));
                $.ajax({
                    type: 'POST',
                    url: 'admin-ajax.php',
                    data: $.extend({}, features, {action: 'update_features'}),
                    error: function () {
                        refreshFeatures(loadedQuickFeatures);
                        updateFeaturesAPI();
                    }
                });
            }, function () {
                refreshFeatures(sabresQuickFeatures);
                $.modal.alert("An error was experienced while trying to update feature settings. Please retry later", "Close").modal();
            });
        }
    }

    function eventsAPI() {

        updateEvents();

        if (!$.sabres.admin.isAuthenticated() || sabresData.shouldReactivate) {
            return;
        }

        var lastUpdated;

        if (localStorage.getItem('sabresEvents')) {
            var localData = $.sabres.util.parseIfString(localStorage.getItem('sabresEvents'));
            lastUpdated = localData.lastUpdated;
        } else {
            lastUpdated = null;
        }

        $.sabres.ajax.callAdminAPI('/events', {lastUpdated: lastUpdated},
                function (response) {
                    var events = {};

                    var items = Array.prototype.concat(response[0] || [], response[1] || []);
                    items.sort(function (a, b) {
                        if (a.x < b.x)
                            return -1;
                        if (a.x > b.x)
                            return 1;
                        return 0;
                    });
                    events.items = items;

                    events.lastUpdated = response.lastUpdated;

                    var serverDate = $.sabres.util.parseDate(events.lastUpdated);
                    var localDate = $.sabres.util.parseDate(lastUpdated);

                    if (serverDate > localDate || !localDate) {
                        localStorage.setItem('sabresEvents', JSON.stringify(events));
                        updateEvents();
                        updateSecurityGauge();
                    }
                });
    }

    function updateEvents() {
        var eventsTimeline = $(".feed-panel .timeline");
        eventsTimeline.empty();
        var events = $.sabres.util.parseIfString(localStorage.getItem('sabresEvents'));

        if (!events) {
            return;
        }

        if (events.items) {
            for (var i = 0; i < events.items.length; i++) {
                var item = events.items[i];
                var flag = $("<div />", {class: "flag", text: item.eventCode});
                var timelineLi = $("<li />", {class: "timeline-inverted"});
                var timelinePanel = $("<div />", {class: "timeline-panel"});
                var timelineHeading = $("<div />", {class: "timeline-heading"});
                var timelineHeadingH, timelineFrom, datetime, timelineTime, timelineInfo;
                var url_to_investigate, link_to_investigate;

                switch (item.eventCode) {
                    case 's':
                        timelineHeadingH = $("<h4 />", {text: "Sabres scan"});
                        timelineFrom = $("<div />", {class: "timeline-from", text: "From " + item.payload.siteName});
                        datetime = new Date($.sabres.util.parseDate(item.x)).toLocaleString();
                        timelineTime = $("<div />", {class: "timeline-caption datetime", text: datetime});
                        timelineInfo = $("<div />", {class: "timeline-caption info", text: item.payload.reportHeader});

                        timelineHeading.append(timelineHeadingH);
                        break;

                    case 'f':
                        link_to_investigate = $("<a />", {
                            "class": "color-inherit sabres-portal-link",
                            "text": item.payload.realAddrCalc,
                            "href": "#",
                            "data-investigate-ip": item.payload.realAddrCalc,
                            "data-portal-action": "investigate-ip"
                        });
                        timelineHeadingH = $("<h4 />", {text: "Request blocked"});
                        timelineFrom = $("<div />", {class: "timeline-from", html: "From "});
                        timelineFrom.append(link_to_investigate);
                        datetime = new Date($.sabres.util.parseDate(item.x)).toLocaleString();
                        timelineTime = $("<div />", {class: "timeline-caption datetime", text: datetime});
                        timelineInfo = $("<div />", {class: "timeline-caption info", text: item.payload.reqUserAgent});

                        timelineHeading.append(timelineHeadingH);
                        break;

                    default:
                        break;
                }

                timelinePanel.append(timelineHeading);
                timelinePanel.append(timelineFrom);
                timelinePanel.append(timelineTime);
                timelinePanel.append(timelineInfo);
                timelineLi.append(flag).append(timelinePanel);

                eventsTimeline.append(timelineLi);
            }
        } else {
            eventsTimeline.empty();
        }

        $("#feed-events.nano").nanoScroller();
    }

    function vulnerabilitiesAPI() {

        updateVulnerabilities();

        if (!$.sabres.admin.isAuthenticated() || sabresData.shouldReactivate) {
            return;
        }

        var lastUpdated;

        if (localStorage.getItem('sabresVulnerabilities')) {
            var localData = $.sabres.util.parseIfString(localStorage.getItem('sabresVulnerabilities'));
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
                            updateSecurityGauge();
                            updateHardeningStatus();
                        }
                    }
                });
    }

    function updateVulnerabilities() {
        var tableBody = $('.data-table.vulnerabilities tbody');
        tableBody.empty();
        $('#vulnerabilities-count').text('');

        var localData = $.sabres.util.parseIfString(localStorage.getItem('sabresVulnerabilities'));
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

                        tableBody.append(row);
                    }
                }
            }
        }

        var count = $('.vulnerabilities-item').length;

        localData.vulnerabilitiesCount = count;
        localStorage.setItem('sabresVulnerabilities', JSON.stringify(localData));

        if (count) {
            $('#vulnerabilities-count').text('(' + count + ')');
        }

        $("#feed-vulnerabilities.nano").nanoScroller();
    }

    function issuesAPI() {

        updateIssues();

        if (!$.sabres.admin.isAuthenticated() || sabresData.shouldReactivate) {
            return;
        }

        var lastUpdated;

        if (localStorage.getItem('sabresIssues')) {
            var localData = $.sabres.util.parseIfString(localStorage.getItem('sabresIssues'));
            lastUpdated = localData.lastUpdated;
        } else {
            lastUpdated = null;
        }

        $.sabres.ajax.callAdminAPI('/issues', {lastUpdated: lastUpdated},
                function (response) {
                    var serverDate = Date.parse(response.lastUpdated);
                    var localDate = Date.parse(lastUpdated) || null;

                    if (serverDate > localDate || !localDate) {
                        localStorage.setItem('sabresIssues', JSON.stringify(response));
                        updateIssues();
                        updateSecurityGauge();
                        updateHardeningStatus();
                    }
                });
    }

    function updateIssues() {
        var tableBody = $('.data-table.issues tbody');
        tableBody.empty();
        $('#issues-count').text('');

        var localData = $.sabres.util.parseIfString(localStorage.getItem('sabresIssues'));
        if (!localData) {
            return;
        }

        var issues = localData.issues;
        if (!issues) {
            return;
        }

        //var url_to_fix_issues = sabresData.basePortalURL + '/#/nav/' + sabresData.clientToken + '/fix-issues';

        for (var i = 0; i < issues.length; i++) {
            var issue = issues[i];
            var created = new Date(issue.createdAt);

            var row = $("<tr />", {class: "issues-item"});

            switch (issue.riskLevel) {
                case 1:
                    issue.riskLevel = "low";
                    row.addClass("low-risk");
                    break;
                case 2:
                    issue.riskLevel = "medium";
                    row.addClass("medium-risk");
                    break;
                case 3:
                    issue.riskLevel = "high";
                    row.addClass("high-risk");
                    break;
                default:
                    row.addClass(issue.riskLevel + "-risk");
                    break;
            }

            // row.append( $("<td />", { class: "issues-scantype", text: issue.reportType }) );
            row.append($("<td />", {class: "issues-type", text: issue.code}));
            row.append($("<td />", {class: "issues-risk", text: issue.riskLevel}));
            row.append($("<td />", {class: "issues-description", text: issue.desc}));
            // row.append($("<td />", {class: "issues-added", text: created.toLocaleString()}));

            if (issue.status !== 'open') {
                row.append($("<td />", {class: "issues-status", text: issue.status}));
            } else {
                var $column = $("<td />", {class: "issues-status"});
                var $span_visible = $("<span />", {class: "issue-status visible", text: issue.status});
                var $span_hidden = $("<span />", {class: "issue-status hidden"});
                // var link = sabresData.isRegistered ?
                //     $("<a />", {class: "sabres-admin-btn btn-xs", href: url_to_fix_issues, text: "fix", title: "Fix Issues"}) :
                //     $("<a />", {class: "sabres-admin-btn btn-xs id-sabres-tour", href: "#", text: "fix"});
                var link = $("<a />", {class: "sabres-admin-btn btn-xs sabres-portal-link", href: "#", text: "fix", title: "Fix Issues", "data-portal-action": "fix-issues"});

                $column.append($span_visible);
                $column.append($span_hidden.append(link));
                row.append($column);
            }

            tableBody.append(row);
        }

        var count = $('.issues-item').length;

        localData.issuesCount = count;
        localStorage.setItem('sabresIssues', JSON.stringify(localData));

        if (count) {
            $('#issues-count').text('(' + count + ')');
        }

        $("#feed-issues.nano").nanoScroller();
    }

    function executeTimedAPIs() {
        eventsAPI();
        vulnerabilitiesAPI();
        issuesAPI();
    }

    function loadQuickFeatures() {
        $.ajax({
            type: 'POST',
            url: 'admin-ajax.php',
            data: {
                action: 'load_features'
            },
            success: function (data) {
                var loadedQuickFeatures = $.sabres.util.parseIfString(data);
                localStorage.setItem('sabresQuickFeatures', JSON.stringify(loadedQuickFeatures));
                refreshFeatures(loadedQuickFeatures);
            }
        });
    }

    function refreshFeatures(loadedQuickFeatures) {
        featureCheckbox.each(function (index, item) {
            $(item).prop('checked', loadedQuickFeatures[ $(item).attr('id') ]);
        });

        if (!sabresData.isRegistered) {
            $.sabres.checkboxes.selectAll('#admin-protection', false);
        }

        $('.features-panel .panel-body.disabled').removeClass('disabled');
        $('.parent-switch').each(function (index, value) {
            $.sabres.checkboxes.enableAll(value);
        });

        localStorage.setItem('sabresQuickFeatures', JSON.stringify(loadedQuickFeatures));

        updateSecurityGauge();
    }

    function updateSecurityGauge() {
        var percent = 0;

        if ($('#firewall').is(':checked')) {
            percent += $('#firewall_fake-crawler').is(':checked') ? 2 : 0;
            percent += $('#firewall_known-attacks').is(':checked') ? 1 : 0;
            percent += $('#firewall_human-detection').is(':checked') ? 2 : 0;
            percent += $('#firewall_spam-registration').is(':checked') ? 2 : 0;
            percent += $('#firewall_anon-browsing').is(':checked') ? 1 : 0;
        }

        if ($('#admin-protection').is(':checked')) {
            percent += $('#2factor-authentication').is(':checked') ? 8 : 0;
            percent += $('#brute-force').is(':checked') ? 4 : 0;
            percent += $('#suspicious-login').is(':checked') ? 4 : 0;
        }

        percent += $('#scheduled-scans').is(':checked') ? 4 : 0;
        percent += sabresData.isRegistered ? 12 : 0;
        percent += sabresData.isPremium ? 25 : 0;

        var issuesData = $.sabres.util.parseIfString(localStorage.getItem('sabresIssues'));
        var issuesPoints = 0;
        if (issuesData) {
            var issuesPenalty = issuesData.issuesCount * 2;
            if (issuesPenalty < 14) {
                issuesPoints = 14 - issuesPenalty;
            }
        }

        var vulnerabilitiesData = $.sabres.util.parseIfString(localStorage.getItem('sabresVulnerabilities'));
        var vulnPoints = 0;
        if (vulnerabilitiesData) {
            var vulnPenalty = vulnerabilitiesData.vulnerabilitiesCount * 7;
            if (vulnPenalty < 21) {
                vulnPoints = 21 - vulnPenalty;
            }
        }

        percent += vulnPoints;
        percent += issuesPoints;

        securityGauge.circleProgress({value: Math.round(percent) / 100});

        updateSafetyText();
    }

    function updateSafetyText() {
        var vulnerabilitiesData = $.sabres.util.parseIfString(localStorage.getItem('sabresVulnerabilities'));
        var issuesData = $.sabres.util.parseIfString(localStorage.getItem('sabresIssues'));
        var vulnerabilitiesCount = 0;
        var issuesCount = 0;

        if (vulnerabilitiesData) {
            vulnerabilitiesCount = vulnerabilitiesData.vulnerabilitiesCount;
        }
        if (issuesData) {
            issuesCount = issuesData.issuesCount;
        }

        if (!sabresData.isRegistered) {
            $('.prompt-item').hide();
            $('#register-prompt').show();
        } else if (!$('#2factor-authentication').is(':checked')) {
            $('.prompt-item').hide();
            $('#tfa-prompt').show();
        } else if (!localStorage.getItem('sabresIndicator') && !scanInProgress) {
            $('.prompt-item').hide();
            $('#scan-prompt').show();
        } else {
            $('.prompt-item').hide();
            $('#flip-prompt').show();
            $('.flip-item').removeClass("shown-flip");
            if (!sabresData.isPremium) {
                $('#premium-prompt').addClass("shown-flip");
            }
            if (issuesCount) {
                $('#issue-prompt').addClass("shown-flip");
            }
            if (vulnerabilitiesCount) {
                $('#vulnerabilities-prompt').addClass("shown-flip");
            }
            if (!$('#firewall_fake-crawler').is(':checked')) {
                $('#fake-crawler-prompt').addClass("shown-flip");
            }
            if (!$('#firewall_known-attacks').is(':checked')) {
                $('#attack-sources-prompt').addClass("shown-flip");
            }
            if (!$('#firewall_human-detection').is(':checked')) {
                $('#human-detection-prompt').addClass("shown-flip");
            }
            if (!$('#firewall_spam-registration').is(':checked')) {
                $('#spam-protection-prompt').addClass("shown-flip");
            }
            if (!$('#brute-force').is(':checked')) {
                $('#brute-force-prompt').addClass("shown-flip");
            }
            if (!$('#suspicious-login').is(':checked')) {
                $('#suspicious-login-prompt').addClass("shown-flip");
            }
            if (!$('#scheduled-scans').is(':checked')) {
                $('#scheduled-scans-prompt').addClass("shown-flip");
            }

            if ($('.flip-item.shown-flip.current-flip').size() === 0) {
                $('.flip-item.shown-flip:eq( 0 )').addClass('current-flip');
            }
        }
    }

    function flipPrevious() {
        var flipItems = $('.flip-item.shown-flip');
        var currentFlip = $('.flip-item.shown-flip.current-flip');
        var i = flipItems.index(currentFlip);
        var size = flipItems.size();

        currentFlip.removeClass('current-flip');

        if (i > 0) {
            i--;
            $('#flip-next').css('display', 'block');
        }

        if (i === 0) {
            $('#flip-previous').css('display', 'none');
        }

        $('.flip-item.shown-flip:eq( ' + i + ' )').addClass('current-flip');
    }

    function flipNext() {
        var flipItems = $('.flip-item.shown-flip');
        var currentFlip = $('.flip-item.shown-flip.current-flip');
        var i = flipItems.index(currentFlip);
        var size = flipItems.size();

        currentFlip.removeClass('current-flip');

        if (i < size - 1) {
            i++;
            $('#flip-previous').css('display', 'block');
        }

        if (i === size - 1) {
            $('#flip-next').css('display', 'none');
        }

        $('.flip-item.shown-flip:eq( ' + i + ' )').addClass('current-flip');
    }

    function updateProgress(progress) {
        var maxWidth = scanProgress.find(".scan-mount").outerWidth();
        var curWidth = scanProgress.find(".scan-bar").width();
        var nextWidth = maxWidth * progress;
        var step = (nextWidth - curWidth) / 25;

        if (!step) {
            step = 1;
        }

        var id = setInterval(frame, 200);

        function frame() {
            if (curWidth >= nextWidth || !scanInProgress) {
                clearInterval(id);
            } else {
                curWidth += step;
                scanProgress.find(".scan-bar").width(curWidth);
            }
        }
    }

    function updateAdminProtectionStatus() {
        if ($('#2factor-authentication').prop('checked') && $('#admin-protection').prop('checked')) {
            $('.step-item.item-2 .step-image').addClass('complete');
        } else {
            $('.step-item.item-2 .step-image').removeClass('complete');
        }
    }

    function updateHardeningStatus() {
        var vulns = $.sabres.util.parseIfString(localStorage.getItem('sabresVulnerabilities'));
        var issues = $.sabres.util.parseIfString(localStorage.getItem('sabresIssues'));
        if (!((issues && !$.isEmptyObject(issues)) || (vulns && !$.isEmptyObject(vulns)) || scanInProgress)) {
            $('.step-item.item-3 .step-image').removeClass('complete');
            $('#step-hardning').addClass('id-scan-now');
        } else {
            $('.step-item.item-3 .step-image').addClass('complete');
            $('#step-hardning').removeClass('id-scan-now');
        }
    }

    function openEmailPage() {
        $('.overlay').show();
        $('.tour-content').show();
        $('.sabres-content').foggy();
        $('.tour-step.first-step').show();
    }

    function switchFeedTab() {
        $('.feed-content').removeClass('active');
        $('.feed-tabs-item').removeClass('active');

        $(this).closest('.feed-tabs-item').addClass('active');
        var contentId = '#feed-' + $(this).attr('id');
        $(contentId).addClass('active');

        $(contentId + ".nano").nanoScroller();
    }

    function resetActivation() {
        var modalQuestion = $.modal.question('Would you like to reset settings to default values?', 'Yes', 'No');
        var yesBtn = modalQuestion.find('.modal-yes-btn');
        var noBtn = modalQuestion.find('.modal-no-btn');
        modalQuestion.modal();
        yesBtn.on("click touchend", function() {
            $.ajax({
                type: 'POST',
                url: 'admin-ajax.php',
                data: {action: 'get_default_features'},
                success: function (data) {
                    var defaultQuickFeatures = $.sabres.util.parseIfString(data);
                    $.sabres.ajax.callAdminAPI('/quick-feature-update', defaultQuickFeatures, function () {
                        localStorage.setItem('sabresQuickFeatures', JSON.stringify(defaultQuickFeatures));
                        $.post('admin-ajax.php', {'action': 'reset_settings'}, function () {
                            $.post('admin-ajax.php', {'action': 'reset_activation'}, function () {
                                location.reload();
                            })
                        });
                    });
                }
            });
        });
        noBtn.on("click touchend", function() {
            $.post('admin-ajax.php', {'action': 'reset_activation'}, function () {
                location.reload();
            });
        });
    }

});
