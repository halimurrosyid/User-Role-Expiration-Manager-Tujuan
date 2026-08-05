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
});
