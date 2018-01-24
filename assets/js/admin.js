var BSWP_Admin = function () {
	var handler = function () {
		jQuery('#bswp-connect-btn').click(function () {
			window.location.href = jQuery(this).data('url');
		});

		jQuery('#bswp-disconnect-btn').click(function () {
			return confirm('Are you sure you want to disconnect your BirdSend account?');
		});

		jQuery('#bswp_settings_switch').click(function () {
			handleSettings();
		});

		jQuery('select').material_select();
	};

	var handleSettings = function () {
		if (jQuery('#bswp_settings_switch').is(':checked')) {
			return jQuery('#bswp_settings_box').show('fast');
		}
		jQuery('#bswp_settings_box').hide('fast');
	};

	return {
		init: function () {
			handler();
			handleSettings();
		}
	};
}();

(function(){ BSWP_Admin.init(); })();
