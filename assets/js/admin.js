var BSWP_Admin = function () {
    var handler = function () {
        jQuery('#bswp-connect-btn').click(function () {
            window.location.href = jQuery(this).data('url');
        });

        jQuery('#bswp-disconnect-btn').click(function () {
            return confirm('Are you sure you want to disconnect your BirdSend account?');
        });

        jQuery('select').formSelect();

        M.updateTextFields();
    };

    return {
        init: function () {
            handler();
        }
    };
}();

(function(){ BSWP_Admin.init(); })();
