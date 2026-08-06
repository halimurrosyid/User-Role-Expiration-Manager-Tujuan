/**
 * Admin JavaScript for User Role Expiration Manager.
 */
jQuery(document).ready(function($) {
	// Confirmation modal for Reset Start Date
	$(document).on('click', '.urem-reset-start-date-btn', function(e) {
		var confirmMsg = (typeof uremVars !== 'undefined' && uremVars.confirmReset) ? uremVars.confirmReset : 'Reset start date?';
		if (!confirm(confirmMsg)) {
			e.preventDefault();
		}
	});

	// Confirmation modal for Expire Now
	$(document).on('click', '.urem-expire-now-btn', function(e) {
		var confirmMsg = (typeof uremVars !== 'undefined' && uremVars.confirmExpire) ? uremVars.confirmExpire : 'Expire role now?';
		if (!confirm(confirmMsg)) {
			e.preventDefault();
		}
	});

	// Confirmation modal for Clear Logs
	$(document).on('click', '.urem-clear-logs-btn', function(e) {
		var confirmMsg = (typeof uremVars !== 'undefined' && uremVars.confirmClear) ? uremVars.confirmClear : 'Clear all logs?';
		if (!confirm(confirmMsg)) {
			e.preventDefault();
		}
	});

	// Quick Presets Auto-fill handler
	$(document).on('change', '.urem-preset-selector', function() {
		var $selected = $(this).find(':selected');
		var duration = $selected.data('duration');
		var unit = $selected.data('unit');

		if (duration && unit) {
			var $container = $(this).closest('tr, td, .urem-user-profile-section');
			$container.find('.urem-duration-input').val(duration);
			$container.find('.urem-unit-select').val(unit);
		}
	});
});
