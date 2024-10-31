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

    function issuesAPI() {
        updateIssues();

        if (!$.sabres.admin.isAuthenticated() || sabresData.shouldReactivate) {
            return;
        }

        var lastUpdated;
        if (localStorage.getItem('sabresIssues')) {
            var localData = JSON.parse(localStorage.getItem('sabresIssues'));
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
                    }
                });
    }

    function updateIssues() {
        var $tableBody = $('.data-table.issues tbody');
        $tableBody.empty();

        var $issuesCount = $('#issues-count');
        $issuesCount.text('');

        var localData = JSON.parse(localStorage.getItem('sabresIssues'));
        if (!localData) {
            return;
        }

        var issues = localData.issues;
        if (!issues) {
            return;
        }

        var url_to_fix_issues = sabresData.basePortalURL + '/#/nav/' + sabresData.clientToken + '/fix-issues';

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
                var link = sabresData.isRegistered ?
                    $("<a />", {class: "sabres-admin-btn btn-xs", href: url_to_fix_issues, text: "fix", title: "Fix Issues"}) :
                    $("<a />", {class: "sabres-admin-btn btn-xs id-sabres-tour", href: "#", text: "fix"});

                $column.append($span_visible);
                $column.append($span_hidden.append(link));
                row.append($column);
            }

            $tableBody.append(row);
        }

        var count = $('.issues-item').length;

        localData.issuesCount = count;
        localStorage.setItem('sabresIssues', JSON.stringify(localData));

        if (count && $issuesCount.length) {
            $issuesCount.text('(' + count + ')');
        }

        var $feedIssuesNano = $("#feed-issues.nano");
        if ($feedIssuesNano.length) {
            $feedIssuesNano.nanoScroller();
        }
    }

    function executeTimedAPIs() {
        issuesAPI();
    }

});