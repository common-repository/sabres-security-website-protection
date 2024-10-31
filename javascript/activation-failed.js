jQuery(function ($) {

    var autoReactivationEnabled = false;
    var autoReactivationTime = 3000;
    var autoReactivationTimer;

    var $activationMessage = $('#activation-message');
    var $retryActivation = $('#retry-activation');

    $retryActivation.click(retryActivation);
    $('.show-error').click(function() {
        $('.last-error').toggle();
    });

    setChecks();
    setIsActivating();

    function setCheckboxClasses(isChecked,checkboxID) {
        var element = $(checkboxID);
        if (isChecked) {
            element.addClass('ok');
            element.removeClass('error');
        }
        else {
            element.removeClass('ok');
            element.addClass('error');
        }
    }

    function setChecks() {
        var data = $.sabres.act_fail.data;
        setCheckboxClasses(data.gatewayConnection,'#connection-to-gateway');
        setCheckboxClasses(data.serviceActivated,'#service-activated');
        setCheckboxClasses(data.trafficMonitor,'#traffic-monitor');
    }

    function setIsActivating() {
        var isActivating = $.sabres.act_fail.data.isActivating;
        if (!isActivating) {
            $activationMessage.text('Sabres plugin failed to activate with remote data center.');
            $retryActivation.removeClass('disabled');
        } else {
            retryActivation();
            return;
        }

        if (autoReactivationEnabled) {
            autoReactivationTimer && clearTimeout(autoReactivationTimer);
            autoReactivationTimer = setTimeout(retryActivation, autoReactivationTime);
        }
    }

    function retryActivation() {
        $activationMessage.text('Sabres is activating with remote data center. Please wait.');
        $retryActivation.addClass('disabled');

        $.ajax({
            type: 'POST',
            url: 'admin-ajax.php',
            data: {
                action: 'reset_activation'
            },
            success: function (data) {
                window.location.reload(false);
            },
            error: function (data) {
                $retryActivation.removeClass('disabled');
            }
        });
    }
});
