
jQuery(function ($) {
    var infoTip = $('.info-tip');
    var body = $('body');

    infoTip.on("click touchend", createTip);
    body.on('touchend click', '.info-tip-close', destroyTips);

    function createTip() {
        if ( $(this).hasClass('active') ) {
            return;
        }

        destroyTips();

        $(this).addClass('active');

        var tipContainer = $("<div />", {class: "info-tip-container"}).appendTo($(this));
        $("<span />", {class: "info-tip-close"}).appendTo(tipContainer);
        $("<span />", {class: "info-tip-text", text: $(this).data("text")}).appendTo(tipContainer);
    }

    function destroyTips() {
        $('.info-tip.active').removeClass('active');
        $(".info-tip-container").remove();
    }


});