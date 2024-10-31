jQuery(function ($) {

    // Set options, init sabres
    $.sabres.init(sbs_admin_data);
    var sabresData = $.sabres.getOptions();

    var tourContent = $('.tour-content');
    var tourOverlay = $('.overlay');
    var content = $('.sabres-content');
    var nextBtn = $('.progress-btn.next');
    var backBtn = $('.progress-btn.back');
    var finishBtn = $('.progress-btn.finish');
    var closeBtn = $('.skip-btn');

    var steps = [
        function () {
            $('.tour-step.activation-step').show();

            var elements = [
                $('.sabres-content .status-menu').first()
            ];
            var hints = [
                $('.tour-step.activation-step .hint-container.first-hint')
            ];

            createHints(hints, elements);
        },
        function () {
            $('.tour-step.first-step').show();
        },
        function () {
            $('.tour-step.second-step').show();
        },
        function () {
            $('.tour-step.third-step').show();

            // var elements = [
            //     $('.sabres-content .glossary-menu').first()
            // ];
            // var hints = [
            //     $('.tour-step.third-step .hint-container.first-hint')
            // ];
            //
            // createHints(hints, elements);
        },
        function () {
            $('.tour-step.fourth-step').show();

            var elements = [
                $('.sabres-content .row.summary').first(),
                $('.sabres-content .sabres-admin-btn.scan-btn').first()
            ];
            var hints = [
                $('.tour-step.fourth-step .hint-container.first-hint'),
                $('.tour-step.fourth-step .hint-container.second-hint')
            ];

            createHints(hints, elements);
        },
        function () {
            $('.tour-step.fifth-step').show();

            var elements = [
                $('.sabres-content .features-panel').first(),
                $('.sabres-content .feed-panel').first(),
                // $('.sabres-content .settings-menu').first()
            ];
            var hints = [
                $('.tour-step.fifth-step .hint-container.first-hint'),
                $('.tour-step.fifth-step .hint-container.second-hint'),
                // $('.tour-step.fifth-step .hint-container.third-hint')
            ];

            createHints(hints, elements);
        }
    ];


    $.openSystemTour = function () {
        if (sabresData.shouldReactivate) {
            startTour(0); // if not activated
        } else if (!sabresData.isRegistered) {
            startTour(1); // if email not entered
        } else {
            startTour(2); // if registered
        }
    };
    if (sabresData.isFirstActivation) {
        $.openSystemTour();
    }
    if (window.location.hash === '#sabres-tour') {
        $.openSystemTour();
    }
    $('#sabres-tour').on("click touchend", $.openSystemTour);
    $(document.body).on("click touchend", ".id-sabres-tour", $.openSystemTour);
    if (window.location.hash === '#sabres-tour') {
        $.openSystemTour();
    }


    $('#email-btn').on( "click touchend", submitEmail );
    $('#terms-link').on( "click touchend", function () {
        $('.terms-block').show();
        $(".modal-body.nano").nanoScroller();
    } );
    $('#close-terms').on( "click touchend", function () {
        $('.terms-block').hide();
    } );

    nextBtn.click( nextStep );
    backBtn.click( previousStep );
    closeBtn.click( finishTour );
    finishBtn.click( finishTour );


    $('#send-email-opts').click(saveEmailOpts);

    $('#slider-control-1').click( function() {
        $(this).addClass('active');
        $('#slider-control-2').removeClass('active');
        $('#tour-slider-1').show();
        $('#tour-slider-2').hide();
    });

    $('#slider-control-2').click( function() {
        $(this).addClass('active');
        $('#slider-control-1').removeClass('active');
        $('#tour-slider-2').show();
        $('#tour-slider-1').hide();
    });

    function createHints( hints, elements ) {
        for ( var i = 0; i < hints.length; i++ ) {
            var cloned;
            if ( hints[ i ].html() === '' ) {
                cloned = elements[ i ].clone();
                cloned.appendTo( hints[ i ] );
            }
            updateOffset( hints[ i ], elements[ i ], cloned );
        }
    }

    function updateOffset( hint, element, cloned ) {
        var offset;
        if (cloned && hint.hasClass('stretch-hint')) {
            var wrap = cloned.wrap( "<div class='hint-wrap'></div>" );
            if (wrap.outerWidth() < element.outerWidth())
                wrap.outerWidth(element.outerWidth());
            if (wrap.outerHeight() < element.outerHeight())
                wrap.outerHeight(element.outerHeight());
        }

        if (hint.hasClass('angle-view')) {
            offset = {
                top: element.offset().top - hint.height() * 0.3,
                left: element.offset().left - 15
            };
        } else if (hint.hasClass('menu-hint')) {
            offset = {
                top: element.offset().top - hint.height() * 0.25,
                left: element.offset().left + element.outerWidth() * 0.1
            };
        } else if (hint.hasClass('round-hint')) {
            offset = {
                top: element.offset().top - hint.height() * 0.5,
                left: element.offset().left + element.outerWidth() * 0.14
            };
        } else {

            var m_top = parseInt(element.css('margin-top'));
            var m_left = parseInt(element.css('margin-left'));
            var p_top = -m_top;
            var p_left = -m_left;

            hint.css('padding-top', p_top + "px");
            hint.css('padding-left', p_left + "px");

            offset = {top: element.offset().top, left: element.offset().left};

        }

        hint.offset(offset);
    }

    function nextStep() {
        var tourStep = getCookieStep();

        if ( tourStep < 5 ) {
            changeStep( ++tourStep );
        }
    }

    function previousStep() {
        var tourStep = getCookieStep();

        if ( tourStep > 1 ) {
            changeStep( --tourStep );
        }
    }

    function startTour( step ) {
        window.scrollTo(0, 0);
        $('body').css('overflow', 'hidden');
        tourOverlay.show();
        tourContent.show();
        content.foggy();

        // Get the last step
        if ( !$.isNumeric(step) ) {
            step = Math.max(getCookieStep(), 1); // activation fix
        }

        changeStep(step);
    }

    function finishTour() {
        setCookieStep( 1 );

        $('.hint-container').empty();
        tourContent.hide();
        tourOverlay.hide();
        content.foggy(false);
        $('body').css('overflow', 'visible');

        var vulns = $.sabres.util.parseIfString(localStorage.getItem('sabresVulnerabilities'));
        var issues = $.sabres.util.parseIfString(localStorage.getItem('sabresIssues'));

        if ( !issues && !vulns && !scanInProgress && $('#initial-scan').prop('checked') ) {
            $(".sabres-admin-btn.scan-btn").click();
        }
        var quickFeatures=$.sabres.util.parseIfString(localStorage.getItem('sabresQuickFeatures'));
        if ($.sabres.options.isRegistered) {
          if (quickFeatures && quickFeatures.hasOwnProperty('admin-protection')) {
            $('#admin-protection').prop('checked', quickFeatures['admin-protection']);
            $('.features-group-admin-protection .feature-item').removeClass('disabled-feature');

          }
        }

        if ( $('#tfa').prop('checked') ) {
            $('.features-group-admin-protection .feature-item').removeClass('disabled');
            $('.features-group-admin-protection .child-features').removeClass('disabled');
            $('#2factor-authentication').prop('checked', true);
            $('#brute-force').prop('checked', true);
            $('#suspicious-login').prop('checked', true);
            $('#step-admin-protection-image').addClass('complete');
            $.featuresUpdateAction();
        }

        $.post('admin-ajax.php', {'action': 'tour_finished'});

    }

    function changeStep( tourStep ) {
        tourStep = Math.min(parseInt(tourStep) || 0, 5);
        setCookieStep( tourStep );
        $('.tour-step').hide();
        steps[ tourStep ]();
    }

    function submitEmail() {
        var code = $('#verification-field').val();
        var initialScan = $('#initial-scan').prop( "checked" );
        var terms = $('#terms').prop( "checked" );
        var reEmail = /^(?:[\w\!\#\$\%\&\'\*\+\-\/\=\?\^\`\{\|\}\~]+\.)*[\w\!\#\$\%\&\'\*\+\-\/\=\?\^\`\{\|\}\~]+@(?:(?:(?:[a-zA-Z0-9](?:[a-zA-Z0-9\-](?!\.)){0,61}[a-zA-Z0-9]?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9\-](?!$)){0,61}[a-zA-Z0-9]?)|(?:\[(?:(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\.){3}(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\]))$/;
        var email = $('#email-input').val();
        var apiUrl = sabresData.adminApiURL + '/add-website';

        if ( !email.match(reEmail) ) {
            $.modal.alert("Invalid email address", "Close").modal();
            return false;
        }

        var requestData = {email: email, name: email};

        if ( code ) {
            requestData.code = code;
        }

        if ( sabresData.isRegistered ) {
            apiUrl = sabresData.adminApiURL + '/contact-info-update';
        }

        if ( !terms ) {
            //alert("Terms are not accepted!");
            $.modal.alert("Terms are not accepted!", "Close").modal();
            return false;
        }

        $.ajax({
            type: 'POST',
            url: apiUrl,
            data: $.extend({}, requestData, {
                websiteClientToken: sabresData.clientToken
            }),
            xhrFields: {
                withCredentials: true
            },
            success: function (data) {
                var response = $.sabres.util.parseIfString(data);

                if (!response) {
                    return;
                }

                switch (response.result) {
                    case "authentication-fail":
                        $.modal.alert("Sabres plugin was unable to verify your email address, please retry later.", "Close").modal();
                        break;

                    case "verification-required":
                        $('#email-input').prop("disabled", true);
                        $('#verification-block').show();
                        $('#terms-opt').hide();
                        break;

                    case "verification-fail":
                        $.modal.alert("The code you have entered is invalid, please retry.", "Close").modal();
                        break;

                    case "verification-error":
                        $.modal.alert("Sabres plugin experienced an error while verifying your email, please retry later.", "Close").modal();
                        break;

                    case "verification-limit":
                        $.modal.alert("Maximum allowed email verification was exceeded, please retry later.", "Close").modal();
                        break;

                    case "verification-success":
                        $('#terms-opt').hide();
                        emailSuccess( email );
                        $.modal.alert("Email successfully verified!", "Close").modal();
                        break;

                    case "add-success":
                        $('#terms-opt').hide();
                        emailSuccess( email );
                        $.modal.alert("Thank you for registering Sabres Plugin!", "Close").modal();
                        break;

                    case "add-error":
                        $.modal.alert("Sabres plugin experienced an error while registering your website, please retry later.", "Close").modal();
                        break;

                    case "add-fail":
                        $.modal.alert("Sabres plugin failed to regiter your website, please retry later.", "Close").modal();
                        break;
                }

            },
            error: function () {
                console.log('Error communicating server');
            }
        });
    }

    function emailSuccess( email ) {
        $.sabres.options.ssoEmail = email;
        $.sabres.options.isRegistered = true;

        $('#tfa-opt').css('display', 'inline-block');
        $('#scan-opt').css('display', 'inline-block');

        $('id-admin-protection').removeClass('.id-sabres-tour');

        nextBtn.removeClass('disabled');
        $('#verification-block').hide();
        $('#register-block').hide();
        $('#email-input').addClass('disabled');
        $('#step-complete-regitration').removeClass('id-sabres-tour');
        $('#step-admin-protection').removeClass('id-sabres-tour');
        $('#step-complete-regitration-image').addClass('complete');
        saveEmailOpts();

    }

    function saveEmailOpts() {
        $.ajax({
            type: 'POST',
            url: 'admin-ajax.php',
            data: {
                action: 'save_email',
                tfa: $('#tfa').prop( "checked" ),
                email: $('#email-input').val()
            }
        });
    }

    function getCookieStep() {
        var name = "sbs_tour_page=";
        var decodedCookie = decodeURIComponent(document.cookie);
        var ca = decodedCookie.split(';');
        for(var i = 0; i <ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) === ' ') {
                c = c.substring(1);
            }
            if (c.indexOf(name) === 0) {
                return c.substring(name.length, c.length);
            }
        }
        setCookieStep( 1 );
        return 1;
    }

    function setCookieStep( value ) {
        document.cookie = "sbs_tour_page=" + value + ";";
    }

});
