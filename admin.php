<?php
require_once dirname(__FILE__) . '/options.php';

/**
 * Shows an error message on the plugins page when plugin configuration is incomplete.
 */
function polizeipresse_admin_notice() {
	global $pagenow;
    if ($pagenow == 'plugins.php') {
		$api_key = polizeipresse_get_option(POLIZEIPRESSE_API_KEY);

		$office_id_count = 0;
		for($office = 0; $office < POLIZEIPRESSE_MAX_OFFICE_COUNT; $office++) {
			$office_id = polizeipresse_get_option(POLIZEIPRESSE_OFFICE_ID . $office);
			if (!empty($office_id)) {
				$office_id_count++;;
			}
		}

		if (empty($api_key) || ($office_id_count == 0)) {
			print ('<div id="message" class="error">' . __('Polizeipresse plugin: Please enter API key select police office', 'Polizeipresse') . '</div>');
		}
    }
}
add_action('admin_notices', 'polizeipresse_admin_notice');


/**
 * Options page
 */
function polizeipresse_option_page() {

	// Success message
	$action_message = '';

	/**
	 * Handles changed options
	 */
	if (isset ($_POST['save'])) {
		$options = polizeipresse_get_options();

		// General options

		if (isset ($_POST['api_key'])) {
			$options[POLIZEIPRESSE_API_KEY] = strip_tags($_POST['api_key']);
		}

		for($office = 0; $office < POLIZEIPRESSE_MAX_OFFICE_COUNT; $office++) {

			$old_office_id = $options[POLIZEIPRESSE_OFFICE_ID . $office];

			// Id of office
			$office_id = strip_tags($_POST['office_id' . $office]);
			if (!empty($office_id)) {
				$options[POLIZEIPRESSE_OFFICE_ID . $office] = $office_id;
			} else {
				$options[POLIZEIPRESSE_OFFICE_ID . $office] = '';
				$options[POLIZEIPRESSE_OFFICE_NAME . $office] = '';
				$options[POLIZEIPRESSE_CRON_LAST_STORY_ID . $office] = '';
			}
			if ($old_office_id != $office_id) {
				// If an office id changes the associated last story id must be deleted.
				// Different offices have different story ids
				$options[POLIZEIPRESSE_CRON_LAST_STORY_ID . $office] = '';
			}

			// Name of office
			$office_name = strip_tags($_POST['office_name' . $office]);
			if (!empty($office_name)) {
				$options[POLIZEIPRESSE_OFFICE_NAME . $office] = $office_name;
			} else {
				$options[POLIZEIPRESSE_OFFICE_ID . $office] = '';
				$options[POLIZEIPRESSE_OFFICE_NAME . $office] = '';
				$options[POLIZEIPRESSE_CRON_LAST_STORY_ID . $office] = '';
			}

			// Category for office
			$office_category_id = strip_tags($_POST['office_category_id' . $office]);
			if (isset ($office_category_id)) {
				$options[POLIZEIPRESSE_OFFICE_CATEGORY_ID . $office] = $office_category_id;
			}
		}

		// Filter options

		if (isset ($_POST['filter_positive'])) {
			$options[POLIZEIPRESSE_FILTER_POSITIVE] = strip_tags($_POST['filter_positive']);
		}

		if (isset ($_POST['filter_negative'])) {
			$options[POLIZEIPRESSE_FILTER_NEGATIVE] = strip_tags($_POST['filter_negative']);
		}


		// Cron options

		// Activate/deactivate cron job
		if (isset ($_POST['cron_enabled'])) {
			$options[POLIZEIPRESSE_CRON_ENABLED] = true;
			polizeipresse_activate_cron();
		} else {
			$options[POLIZEIPRESSE_CRON_ENABLED] = false;
			polizeipresse_deactivate_cron();
		}

		if (isset ($_POST['cron_user_id'])) {
			$options[POLIZEIPRESSE_CRON_ADD_USER_ID] = strip_tags($_POST['cron_user_id']);
		}

		if (isset ($_POST['default_category_id'])) {
			$options[POLIZEIPRESSE_DEFAULT_CATEGORY_ID] = strip_tags($_POST['default_category_id']);
		}

		// Add new posts as draft
		if (isset ($_POST['cron_add_publish'])) {
			$options[POLIZEIPRESSE_CRON_ADD_PUBLISH] = true;
		} else {
			$options[POLIZEIPRESSE_CRON_ADD_PUBLISH] = false;
		}

		// Email notification
		if (isset ($_POST['cron_notify'])) {
			$options[POLIZEIPRESSE_CRON_NOTIFY] = true;
		} else {
			$options[POLIZEIPRESSE_CRON_NOTIFY] = false;
		}

		// Save options
		polizeipresse_update_options($options);

		// Verify that api_key and office_id are set
		$office_id_count = 0;
		for($office = 0; $office < POLIZEIPRESSE_MAX_OFFICE_COUNT; $office++) {
			$office_id = polizeipresse_get_option(POLIZEIPRESSE_OFFICE_ID . $office);
			if (!empty($office_id)) {
				$office_id_count++;;
			}
		}
		$api_key = polizeipresse_get_option(POLIZEIPRESSE_API_KEY);
		if (empty ($api_key) || ($office_id_count == 0)) {
			polizeipresse_update_option(POLIZEIPRESSE_CRON_ENABLED, false);
			polizeipresse_deactivate_cron();
		}

		$action_message = __('Your settings have been saved.', 'Polizeipresse');
	}

	/**
	  * Forces loading of stories
	  */
	if (isset ($_POST['loadStories'])) {
		polizeipresse_load_stories_and_create_posts();

		$action_message = __('Stories have been loaded.', 'Polizeipresse');
	}

	/**
	  * Resets the id of the last processed story
	  */
	if (isset ($_POST['resetStoryId'])) {
		polizeipresse_update_option(POLIZEIPRESSE_CRON_LAST_DATE, '');
		polizeipresse_update_option(POLIZEIPRESSE_CRON_LAST_STATUS, '');

		for($office = 0; $office < POLIZEIPRESSE_MAX_OFFICE_COUNT; $office++) {
			polizeipresse_update_option(POLIZEIPRESSE_CRON_LAST_STORY_ID . $office, '');
		}

		$action_message = __('Last processed story id has been reset.', 'Polizeipresse');
	}

	// Show messages
	if (!empty($action_message)) {
?>
		<div id="message" class="updated"><?php echo $action_message ?></div>
<?php
	}
?>

	<script type="text/javascript">
		jQuery(document).ready(function() {
			polizeipresse_admin_show_tab("<?php echo (isset($_POST['admin_tab_index']) ? $_POST['admin_tab_index'] : ''); ?>");
		});
	</script>

	<div id="PolizeipresseAdmin" class="wrap" style="display:none;">

        <h2><span class="Polizei">Polizei</span>presse - <?php _e('Options', 'Polizeipresse'); ?></h2>

        <form id="PolizeipresseOptionsForm" name="settings-form" method="post" action="">

     		<ul>
         		<li><a href="#PolizeipresseTabGeneral"><?php _e('General', 'Polizeipresse'); ?></a></li>
         		<li><a href="#PolizeipresseTabFilter"><?php _e('Filter', 'Polizeipresse'); ?></a></li>
         		<li><a href="#PolizeipresseTabCron"><?php _e('Cronjob', 'Polizeipresse'); ?></a></li>
	     	</ul>

	        <table id="PolizeipresseTabGeneral" class="form-table" style="clear:none">

				<tr valign="top">
					<td colspan="2">
						<div class="adminHelpMessage">
							<img src="<?php echo plugin_dir_url(__FILE__)?>/img/info.gif" alt="Info" align="middle" />
							<?php _e('This page contains fundamental plugin settings. Without API key and office id the plugin is unable to work.', 'Polizeipresse'); ?>
							<br>
							<?php _e('If you do not have an API key, please register on the following page to get one', 'Polizeipresse'); ?>:
							<a href="http://www.presseportal.de/services/" target="_blank"><?php _e('Click here', 'Polizeipresse'); ?></a>.
						</div>
					</td>
				</tr>

	            <tr valign="top">
	                <td scope="row" class="label">
	                	<label for="api_key"><?php _e('Polizeipresse API key', 'Polizeipresse'); ?>:</lable>
	                </td>
	                <td>
						<?php $api_key = polizeipresse_get_option(POLIZEIPRESSE_API_KEY); ?>
	                    <input name="api_key" id="api_key" size="50" value="<?php echo $api_key; ?>" type="text" class="regular-text" />
	                </td>
	            </tr>

<?php
				for($office = 0; $office < POLIZEIPRESSE_MAX_OFFICE_COUNT; $office++) {
?>
		            <tr valign="top" class="officeRow">
		                <td scope="row" class="label">
		                	<label for="office_name<?php echo $office;?>"><?php _e('Police office', 'Polizeipresse'); ?></lable>
		                </td>
	                	<td>
	                		<input name="office_id<?php echo $office;?>"
	                			id="office_id<?php echo $office;?>"
	                			size="50"
	                			value="<?php echo polizeipresse_get_option(POLIZEIPRESSE_OFFICE_ID . $office); ?>"
	                			type="hidden" class="regular-text" />
	                		<input name="office_name<?php echo $office;?>"
	                			id="office_name<?php echo $office;?>"
	                			class="office_name"
	                			size="50"
	                			value="<?php echo polizeipresse_get_option(POLIZEIPRESSE_OFFICE_NAME . $office); ?>"
	                			type="text" class="regular-text" readonly="readonly" />
	                	</td>
	                	<td>
	                		&nbsp;
	                	</td>
                	</tr>
                	<tr>
	                	<td>
	                		&nbsp;
	                	</td>
	                	<td>
							<button id="searchOfficeDialogButton<?php echo $office;?>"
								class="searchOfficeDialogButton button-secondary" <?php echo (empty($api_key) ? ' disabled="disabled" ' : '') ?>>
								<?php _e('Search for offices', 'Polizeipresse'); ?>
							</button>
							<button id="removeOfficeButton<?php echo $office;?>"
								class="removeOfficeButton button-secondary">
								<?php _e('Remove office', 'Polizeipresse'); ?>
							</button>
	                	</td>
	            	</tr>
	            	<tr>
		                <td scope="row">
		                	<label for="office_category_id<?php echo $office;?>"><?php _e('Category for new posts', 'Polizeipresse'); ?></lable>
		                </td>
	                	<td>
<?php
							$current_cat = polizeipresse_get_option(POLIZEIPRESSE_OFFICE_CATEGORY_ID . $office);
							wp_dropdown_categories("hide_empty=0&name=office_category_id" . $office . "&selected=" . $current_cat . "&show_option_none=Default");
?>
           				</td>
	            	</tr>
<?php
				}
?>

			</table>

	        <table id="PolizeipresseTabFilter" class="form-table" style="clear:none">

				<tr valign="top">
					<td colspan="2">
						<div class="adminHelpMessage">
							<img src="<?php echo plugin_dir_url(__FILE__)?>/img/info.gif" alt="Info" />
							<?php _e('On this page you can define filters. These filteres are used to filter police stories. Multiple filters can be separated by comma.', 'Polizeipresse'); ?>
						</div>
					</td>
				</tr>

	            <tr valign="top">
	                <td scope="row" class="label">
	                	<label for="filter_positive"><?php _e('Positive filters', 'Polizeipresse'); ?>:<br/>
	                    	(<?php _e('Separate multiple filters by comma.', 'Polizeipresse'); ?>)
	                    </lable>
	                </td>
	                <td>
	                    <textarea name="filter_positive" cols="50" rows="5"><?php echo polizeipresse_get_option(POLIZEIPRESSE_FILTER_POSITIVE); ?></textarea>
	                </td>
	            </tr>

	            <tr valign="top">
	                <td scope="row" class="label">
	                	<label for="filter_negative"><?php _e('Negative filters', 'Polizeipresse'); ?>:<br/>
	                    	(<?php _e('Separate multiple filters by comma.', 'Polizeipresse'); ?>)
	                    </lable>
	                </td>
	                <td>
	                    <textarea name="filter_negative" cols="50" rows="5"><?php echo polizeipresse_get_option(POLIZEIPRESSE_FILTER_NEGATIVE); ?></textarea>
	                </td>
	            </tr>

			</table>

	        <table id="PolizeipresseTabCron" class="form-table" style="clear:none">

				<tr valign="top">
					<td colspan="2">
						<div class="adminHelpMessage">
							<img src="<?php echo plugin_dir_url(__FILE__)?>/img/info.gif" alt="Info" />
							<?php _e('New police stories can be automatically loaded and added to your blog. This is done every hour.', 'Polizeipresse'); ?>
						</div>
					</td>
				</tr>

	            <tr valign="top">
	                <td scope="row" class="label">
	                	<label for="cron"><?php _e('Enable cronjob', 'Polizeipresse'); ?>:</lable>
	                </td>
	                <td>
	                    <input type="checkbox" name="cron_enabled" value="true" <?php echo (polizeipresse_is_cron_enabled()) ? 'checked' : ''; ?> >
	                </td>
	            </tr>

	            <tr valign="top">
	                <td scope="row" class="label">
	                	<label for="cron_user_id"><?php _e('User for new posts', 'Polizeipresse'); ?>:</lable>
	                </td>
	                <td>
<?php
							$current_user = polizeipresse_get_option(POLIZEIPRESSE_CRON_ADD_USER_ID);
							wp_dropdown_users("orderby=user_nicename&name=cron_user_id&selected=" . $current_user);
?>
					</td>
	            </tr>

	            <tr valign="top">
	                <td scope="row" class="label">
	                	<label for="cron_categorie_id"><?php _e('Default category for new posts', 'Polizeipresse'); ?>:</lable>
	                </td>
	                <td>
<?php
						$current_cat = polizeipresse_get_option(POLIZEIPRESSE_DEFAULT_CATEGORY_ID);
						wp_dropdown_categories("hide_empty=0&name=default_category_id&selected=" . $current_cat . "&show_option_none=Keine");
?>
					</td>
	            </tr>

	            <tr valign="top">
	                <td scope="row" class="label">
	                	<label for="cron_add_publish"><?php _e('Publish new posts immediately', 'Polizeipresse'); ?>:</lable>
	                </td>
	                <td>
	                	<input type="checkbox" name="cron_add_publish" value="true" <?php echo polizeipresse_get_option(POLIZEIPRESSE_CRON_ADD_PUBLISH) ? 'checked' : ''; ?> >
	                </td>
	            </tr>

	            <tr valign="top">
	                <td scope="row" class="label">
	                	<label for="cron_notify"><?php _e('Notify on new stories', 'Polizeipresse'); ?>:</lable>
	                </td>
	                <td>
	                	<input type="checkbox" name="cron_notify" value="true" <?php echo polizeipresse_get_option(POLIZEIPRESSE_CRON_NOTIFY) ? 'checked' : ''; ?> >
	                </td>
	            </tr>

<?php
				for($office = 0; $office < POLIZEIPRESSE_MAX_OFFICE_COUNT; $office++) {
?>
		            <tr valign="top">
		                <td scope="row" class="label">
		                	<label for="cron_last_story_id<?php echo $office;?>">
<?php
								_e('Last processed story id', 'Polizeipresse');
								$office_name = polizeipresse_get_option(POLIZEIPRESSE_OFFICE_NAME . $office);
								if (empty ($office_name)) {
									$office_name = __('unknown', 'Polizeipresse');
								}
								echo "(" . $office_name . ")";
?>:</lable>
		                </td>
		                <td>
		                    <input name="cron_last_story_id<?php echo $office;?>" size="10"
		                    	value="<?php echo polizeipresse_get_option(POLIZEIPRESSE_CRON_LAST_STORY_ID . $office); ?>"
		                    	type="text"
		                    	readonly="readonly" />
		                </td>
		            </tr>
<?php
				}
?>
		            <tr valign="top">
		                <td scope="row" class="label">
		                	<label"><?php _e('Last cron execution time', 'Polizeipresse'); ?>:</lable>
		                </td>
		                <td>
<?php
							$last_load_date = polizeipresse_get_option(POLIZEIPRESSE_CRON_LAST_DATE);
							if (!empty ($last_load_date)) {
								$last_load_date_time = date_i18n(get_option('date_format') . " " . get_option('time_format'), $last_load_date);
							} else {
								$last_load_date_time = __('unknown', 'Polizeipresse');
							}

							$last_load_status = polizeipresse_get_option(POLIZEIPRESSE_CRON_LAST_STATUS);
							if (empty ($last_load_status)) {
								$last_load_status = false;
							}

							echo '(' . __('Date/time', 'Polizeipresse') . ': ' . $last_load_date_time . ', ' . __('State', 'Polizeipresse') . ': ' . ($last_load_status == true ? __('successfull', 'Polizeipresse') : __('unknown', 'Polizeipresse')) . ")";
?>
		                </td>

	            <tr>
	                <td scope="row" colspan="2">
						<input type="submit" name="loadStories" value="<?php _e('Load stories now', 'Polizeipresse'); ?>" class="button-secondary"
							<?php echo (empty($api_key) ? ' disabled="disabled" ' : '') ?>/>
						<input type="submit" name="resetStoryId" value="<?php _e('Reset last processed story id', 'Polizeipresse'); ?>" class="button-secondary" />
					</td>
	            </tr>

			</table>

	        <table class="form-table" style="clear:none">
	            <tr>
	                <td scope="row" colspan="2">
						<input type="submit" name="save" value="<?php _e('Save all', 'Polizeipresse'); ?>" class="button-primary" />
					</td>
	            </tr>

	        </table>

        </form>

	  	<div id="searchOfficeDialog" class="wp-dialog" style="display:none" title="<?php _e('Search for offices', 'Polizeipresse'); ?>">

			<div class="adminHelpMessage">
				<img src="<?php echo plugin_dir_url(__FILE__)?>/img/info.gif" alt="Info" align="middle" />
				<?php _e('You can search for German police offices here. Start the search and select one item from the result list. Press apply to accept.', 'Polizeipresse'); ?>
			</div>

			<form>
				<p>
					<label for="searchOfficeTerms"><?php _e('Search terms', 'Polizeipresse'); ?>:</lable>
					<input id="searchOfficeTerms" size="30" type="text" class="regular-text" />
				</p>

				<p id="searchOfficeErrorMessage">
				</p>

				<p style="text-align: center;">
					<button id="searchOfficeButton" class="button-primary">
						<?php _e('Search', 'Polizeipresse'); ?>
					</button>
					<button id="cancelSearchOfficeDialog" class="button-primary"><?php _e('Cancel', 'Polizeipresse'); ?></button>
				</p>
			</form>

			<div id="searchOfficeResult" style="display: none;">
				<hr/>

				<label for="office_id"><?php _e('Search result', 'Polizeipresse'); ?>:</lable>
				<select id="officeSelector" name="office_id"></select>
				<br/>
				<p style="text-align: center;">
					<button id="applySearchOfficeDialog" class="button-primary"><?php _e('Apply', 'Polizeipresse'); ?></button>
				</p>
			</div>

		</div>

 	</div>


<?php

}


/**
 * Creates the admin menu
 */
function polizeipresse_create_admin_menu() {
	if(current_user_can('manage_options')){
		// Create new top-level menu
		add_options_page('Polizeipresse-Plugin',
						 'Polizeipresse',
						 'manage_options',
						 'polizeipresse_options',
						 'polizeipresse_option_page');
	}
}
add_action('admin_menu', 'polizeipresse_create_admin_menu');

/**
 * Initializes the admin menu
 */
function polizeipresse_admin_init() {
	wp_enqueue_script('jquery');
	wp_enqueue_script('jquery-validate', plugin_dir_url(__FILE__) . 'js/jquery.validate.min.js', array ('jquery'), '1.8.1', true);
	wp_enqueue_script('jquery-ui-core');
	wp_enqueue_script('jquery-ui-tabs');

	wp_enqueue_script('jquery-ui-dialog');
	wp_enqueue_style ('wp-jquery-ui-dialog');

	wp_enqueue_style ('polizeipresse-admin.css', plugin_dir_url(__FILE__) . 'css/admin.css');
	wp_enqueue_script('polizeipresse-admin.js', plugin_dir_url(__FILE__) . 'js/admin.js', null, null, true);
	$locData = array ('error_no_result'             => __('No result from server.', 'Polizeipresse'),
	                  'error_unknown'               => __('An error occurred', 'Polizeipresse'),
	                  'validation_required_api_key' => __('Please enter API key', 'Polizeipresse'),
	                  'validation_required_office'  => __('Please select police office', 'Polizeipresse')
	                  );
	wp_localize_script('polizeipresse-admin.js', 'polizeipresse', $locData );
}
add_action('admin_init', 'polizeipresse_admin_init');

/**
 * Ajax function: Searches for offices
 */
function polizeipresse_search_office_callback() {

	$result = array();

	$terms = trim($_POST['terms']);

    $api_key = polizeipresse_get_option(POLIZEIPRESSE_API_KEY);
	if (empty($api_key)) {
		// If api key is not in database, use api_key from request
		$api_key = trim($_POST['api_key']);
	};

	if (!empty ($terms) && !empty ($api_key)) {
		require_once(dirname(__FILE__) . '/Presseportal.class.php');
	    $pp = new Presseportal($api_key, 'de');
	    $pp->format = 'xml';
	    $pp->limit = '30';

		$response = $pp->search_office($terms);

		if((!$response->error) && ($response->offices)) {
			foreach($response->offices AS $office) {
				$result[] = array('name' => $office->name, 'id' => $office->id);
			}
		}
		else {
			// Empty result
		}
	}

	// Return reponse
	echo json_encode($result);

	// this is required to return a proper result
	die();
}
add_action('wp_ajax_polizeipresse_search_office', 'polizeipresse_search_office_callback');

?>