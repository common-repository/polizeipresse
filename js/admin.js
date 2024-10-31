jQuery(document).ready(function() {
	polizeipresse_admin_init_form_validation();
	polizeipresse_admin_init_tabs();
	polizeipresse_admin_init_search_dialog();
});

/**
 * Initializes the form validation in admin area
 */
function polizeipresse_admin_init_form_validation() {

	// Form validation
	jQuery("#PolizeipresseOptionsForm").validate({
		rules: {
			api_key: {
				required: true
			},
			office_name: {
				required: true
			}
		},
		messages: {
			api_key: polizeipresse.validation_required_api_key,
			office_name: polizeipresse.validation_required_office,
		},
		invalidHandler: function(form, validator) {
            var element = jQuery(validator.errorList[0].element);
            var tab = element.parents("table")[0];
			jQuery("#PolizeipresseOptionsForm").tabs('select', tab.id);
		}
	});
}

/**
 * Initializes the tabs in admin area
 */
function polizeipresse_admin_init_tabs() {

	// Init tabs
	jQuery("#PolizeipresseOptionsForm").tabs();

	// Remember tab after clicking the 'save' button
	jQuery("#PolizeipresseOptionsForm").submit(function() {
		var $form = this;
  		selected_tab_idx = jQuery("#PolizeipresseOptionsForm").tabs("option", "active");
		jQuery('<input />', {type: 'hidden', name: 'admin_tab_index', value: selected_tab_idx}).appendTo($form);
		return true;
	});

	// Show tabs
	jQuery("#PolizeipresseAdmin").show();
}

/**
 * Show the tab with the given index.
 */
function polizeipresse_admin_show_tab(tab_index) {
	jQuery("#PolizeipresseOptionsForm").tabs({active: tab_index});
}

/**
 * Initializes the dialog for search for offices in admin area
 */
function polizeipresse_admin_init_search_dialog() {

	// Enable search dialog button only if api_key is set
	jQuery("#api_key").keyup(function() {
		api_key = jQuery("#api_key").val();
		if (api_key) {
			jQuery(".searchOfficeDialogButton").removeAttr("disabled");
		}
		else {
			jQuery(".searchOfficeDialogButton").attr("disabled", "disabled");
		}
		return true;
	});

	// Open search dialog on click on button click
	jQuery(".searchOfficeDialogButton").each(function(office_index) {
		jQuery(this).click(function() {
			polizeipresse_admin_open_search_dialog(office_index);
			return false;
		});
	});

	// Open search dialog on click on office name
	jQuery(".office_name").each(function(office_index) {
		jQuery(this).click(function() {
			polizeipresse_admin_open_search_dialog(office_index);
			return false;
		});
	});

	// Delete office button
	jQuery(".removeOfficeButton").each(function(office_index) {
		jQuery(this).click(function() {
			jQuery("#office_id" + office_index).val("");
			jQuery("#office_name" + office_index).val("");
			return false;
		});
	});

	// Init cancel button in serch dialog
	jQuery("#cancelSearchOfficeDialog").click(function() {
		jQuery("#searchOfficeDialog").dialog("destroy");
		return false;
	});

	// On button click start searching
	jQuery("#searchOfficeButton").click(function() {
		polizeipresse_admin_process_search();
		return false;
	});
}

/**
 * Opens the search dialog
 */
function polizeipresse_admin_open_search_dialog(office_index) {

	api_key = jQuery('#api_key').val();
	if (api_key) {

		// Init apply button in search dialog
		jQuery("#applySearchOfficeDialog").unbind('click');
		jQuery("#applySearchOfficeDialog").click(function() {
			polizeipresse_admin_apply_selected_office(office_index);
			jQuery("#searchOfficeDialog").dialog("close");
			return false;
		});

		jQuery("#searchOfficeDialog").dialog({
			closeOnEscape: true,
			modal: true,
			width: 500,
			dialogClass: 'wp-dialog',
			open: function(event, ui) {
				// Set initial search terms
				jQuery('#searchOfficeTerms').val(jQuery('#office_name').val());

				// Enable search button
				jQuery("#searchOfficeButton").removeAttr("disabled");

				// Hide result list
				jQuery("#searchOfficeResult").hide();

				// Hide error message
				jQuery("#searchOfficeErrorMessage").hide();
			}
		});
	}
}

/**
 * Processes the search for police offices.
 */
function polizeipresse_admin_process_search() {
	// Disable search button
	jQuery("#searchOfficeButton").attr("disabled", "disabled");

	// Hide result list
	jQuery("#searchOfficeResult").hide();

	// Hide error message
	jQuery("#searchOfficeErrorMessage").hide();

	jQuery.post(
		ajaxurl,
		{
			action: 'polizeipresse_search_office',
			terms: jQuery('#searchOfficeTerms').val(),
			api_key: jQuery('#api_key').val(),
		},
		polizeipresse_admin_handle_search_result
	);
}

/**
 * Handles the search result from AJAX-respone.
 */
function polizeipresse_admin_handle_search_result(response, textStatus) {
	try {
		var offices = JSON.parse(response);

		if (offices && offices.length > 0) {
			// Add offices to result list
			jQuery("#officeSelector").empty();
			jQuery.each(offices, function(index, office) {
				jQuery("<option/>").val(office.id).text(office.name).appendTo("#officeSelector");
			});
			jQuery("#officeSelector option:first").attr("selected", "selected");

			// Show result list
			jQuery("#searchOfficeResult").show();
		}
		else {
			jQuery("#searchOfficeErrorMessage").text(polizeipresse.error_no_result);
			jQuery("#searchOfficeErrorMessage").show();
		}

	} catch(e) {
		jQuery("#searchOfficeErrorMessage").text(polizeipresse.error_unknown + ": " + e);
		jQuery("#searchOfficeErrorMessage").show();
	}

	// Enable search button
	jQuery("#searchOfficeButton").removeAttr("disabled");
}

/**
 * When the user has chosen an office, this method applies the new office.
 */
function polizeipresse_admin_apply_selected_office(office_index) {
	// Copy selected office from dialog to admin page

	office_id = jQuery("#officeSelector option:selected").val();
	jQuery("#office_id" + office_index).val(office_id);

	office_name = jQuery("#officeSelector option:selected").text();
	jQuery("#office_name" + office_index).val(office_name);
}