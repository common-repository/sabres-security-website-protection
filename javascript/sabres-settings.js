jQuery(function ($) {
    
    // Set options, init sabres
    $.sabres.init(sbs_admin_data);
    var sabresData = $.sabres.getOptions();
    
    var featureCheckbox = $(".setting-item input[type='checkbox']");

    $.ajax({
        type: 'POST',
        url: 'admin-ajax.php',
        data: {
            action: 'load_features'
        },
        success: function (data) {
            var loadedQuickFeatures = JSON.parse( data );

            featureCheckbox.each( function ( index, item ) {
                $( item ).prop('checked', loadedQuickFeatures[ $(item).attr('id') ]);
            });

            $('.parent-switch').each(updateChildSettings);

            localStorage.setItem('sabresQuickFeatures', JSON.stringify( loadedQuickFeatures ));
        }
    });

    $('.parent-switch').click( updateChildSettings );
    $('#settings_submit_button').click( updateSettings );
    $('input[name="tfa_strictness"]').click( function () {
        if ($('#strictness-new-device').prop("checked")) {
            $('#expiry-block').removeClass('disabled');
        } else {
            $('#expiry-block').addClass('disabled');
        }
    } );

    function updateChildSettings() {
        var childList = $(this).closest('.setting-item').next('.child-settings');
        if ($(this).prop("checked")) {
            childList.removeClass('disabled');
        } else {
            childList.addClass('disabled');
        }
    }

    function updateSettings() {

        var features = {};
        var loadedQuickFeatures = JSON.parse( localStorage.getItem('sabresQuickFeatures') );

        featureCheckbox.each( function( index, item ) {
            var key = $( item ).attr('id');
            var val = $( item ).is(':checked');

            if ( loadedQuickFeatures[ key ] !== val ) {
                features[ key ] = val;
            }
        });

        if ( ! $.isEmptyObject( features ) ) {
            if ( ! $.sabres.admin.isAuthenticated() ) {
                return;
            }

            $.sabres.ajax.callAdminAPI('/quick-feature-update', features, function () {
                localStorage.setItem('sabresQuickFeatures', JSON.stringify($.extend({}, loadedQuickFeatures, features)));
            });
        }
    }
});