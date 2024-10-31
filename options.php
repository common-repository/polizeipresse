<?php

// Name of wordpress option
define('POLIZEIPRESSE_OPTIONS_KEY',     'polizeipresse_options');
define('POLIZEIPRESSE_MAX_OFFICE_COUNT', 10);

// List of options for this plugin
define('POLIZEIPRESSE_API_KEY',               'api_key');
define('POLIZEIPRESSE_OFFICE_ID',             'office_id');                // Old option. Needed for version 0.2 and below. Will be migrated.
define('POLIZEIPRESSE_OFFICE_NAME',           'office_name');              // Old option. Needed for version 0.2 and below. Will be migrated.
define('POLIZEIPRESSE_CRON_ADD_CATEGORIE_ID', 'cron_add_categorie_id');    // Old option. Needed for version 0.2 and below. Will be migrated.

for($office = 0; $office < POLIZEIPRESSE_MAX_OFFICE_COUNT; $office++) {
	define('POLIZEIPRESSE_OFFICE_ID' . $office,          'office_id' . $office);
	define('POLIZEIPRESSE_OFFICE_NAME' . $office,        'office_name' . $office);
	define('POLIZEIPRESSE_CRON_LAST_STORY_ID' . $office, 'cron_last_story_id' . $office);
	define('POLIZEIPRESSE_OFFICE_CATEGORY_ID' . $office, 'office_category_id' . $office);
}

define('POLIZEIPRESSE_DEFAULT_CATEGORY_ID',   'default_category_id');
define('POLIZEIPRESSE_FILTER_POSITIVE',       'filter_positive');
define('POLIZEIPRESSE_FILTER_NEGATIVE',       'filter_negative');
define('POLIZEIPRESSE_CRON_ENABLED',          'cron_enabled');
define('POLIZEIPRESSE_CRON_LAST_DATE',        'cron_last_date');
define('POLIZEIPRESSE_CRON_LAST_STATUS',      'cron_last_status');
define('POLIZEIPRESSE_CRON_LAST_STORY_ID',    'cron_last_story_id');       // Old option. Needed for version 0.2 and below. Will be migrated.
define('POLIZEIPRESSE_CRON_ADD_PUBLISH',      'cron_add_publish');
define('POLIZEIPRESSE_CRON_NOTIFY',           'cron_add_notify');
define('POLIZEIPRESSE_CRON_ADD_USER_ID',      'cron_add_user_id');


/**
 * Set default options
 */
function polizeipresse_set_default_options() {
	polizeipresse_update_option(POLIZEIPRESSE_API_KEY,               '');
	polizeipresse_update_option(POLIZEIPRESSE_OFFICE_ID,             '');  // Old option. Needed for version 0.2 and below. Will be migrated.
	polizeipresse_update_option(POLIZEIPRESSE_OFFICE_NAME,           '');  // Old option. Needed for version 0.2 and below. Will be migrated.
	polizeipresse_update_option(POLIZEIPRESSE_DEFAULT_CATEGORY_ID,   '');
	polizeipresse_update_option(POLIZEIPRESSE_FILTER_POSITIVE,       '');
	polizeipresse_update_option(POLIZEIPRESSE_FILTER_NEGATIVE,       '');
	polizeipresse_update_option(POLIZEIPRESSE_CRON_ENABLED,          false);
	polizeipresse_update_option(POLIZEIPRESSE_CRON_LAST_DATE,        '');
	polizeipresse_update_option(POLIZEIPRESSE_CRON_LAST_STATUS,      '');
	polizeipresse_update_option(POLIZEIPRESSE_CRON_LAST_STORY_ID,    ''); // Old option. Needed for version 0.2 and below. Will be migrated.
	polizeipresse_update_option(POLIZEIPRESSE_CRON_ADD_PUBLISH,      false);
	polizeipresse_update_option(POLIZEIPRESSE_CRON_NOTIFY,           '');
	polizeipresse_update_option(POLIZEIPRESSE_CRON_ADD_USER_ID,      '');
	polizeipresse_update_option(POLIZEIPRESSE_CRON_ADD_CATEGORIE_ID, '');

	for($office = 0; $office < POLIZEIPRESSE_MAX_OFFICE_COUNT; $office++) {
		polizeipresse_update_option(POLIZEIPRESSE_OFFICE_ID . $office,          '');
		polizeipresse_update_option(POLIZEIPRESSE_OFFICE_NAME . $office,        '');
		polizeipresse_update_option(POLIZEIPRESSE_CRON_LAST_STORY_ID . $office, '');
		polizeipresse_update_option(POLIZEIPRESSE_OFFICE_CATEGORY_ID . $office,   '');
	}
}

/**
 * Returns all options for this plugin.
 */
function polizeipresse_get_options() {
	return get_option(POLIZEIPRESSE_OPTIONS_KEY);
}

/**
 * Returns the options with the given name.
 *
 * @param name Name of option
 */
function polizeipresse_get_option($name) {
	$all_options = polizeipresse_get_options();
	return trim($all_options[$name]);
}

/**
 * Updates all options set in the given options array.
 *
 * @param options Array of options
 */
function polizeipresse_update_options($options) {
	update_option(POLIZEIPRESSE_OPTIONS_KEY, $options);
	wp_cache_set(POLIZEIPRESSE_OPTIONS_KEY, $options);
}

/**
 * Updates the option with the given name and value.
 *
 * @param name Name of option
 * @param value Value of option
 */
function polizeipresse_update_option($name, $value) {
	$options = polizeipresse_get_options();
	$options[$name] = $value;
	polizeipresse_update_options($options);
}

/**
 * Deletes the option with the given name.
 *
 * @param name Name of option
 */
function polizeipresse_delete_option($name) {
	$options = polizeipresse_get_options();
	unset($options[$name]);
	polizeipresse_update_options($options);
}

/**
 * Deletes all options
 */
function polizeipresse_delete_options() {
	delete_option(POLIZEIPRESSE_OPTIONS_KEY);
}

?>