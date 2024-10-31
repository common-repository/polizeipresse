<?php
/*
 Plugin Name: Polizeipresse Plugin
 Plugin URI: http://wordpress.org/extend/plugins/polizeipresse/
 Description: The plugin loads police news from German police offices and shows them in your blog.
 Version: 0.3.2
 Author: Karsten Strunk
 Author URI: http://www.strunk.eu/wordpress
 Min WP Version: 3.0

 This program is distributed under the GNU General Public License, Version 2,
 June 1991. Copyright (C) 1989, 1991 Free Software Foundation, Inc., 51 Franklin
 St, Fifth Floor, Boston, MA 02110, USA

 THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
 ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

require_once dirname(__FILE__) . '/options.php';
require_once dirname(__FILE__) . '/widget.php';

if (is_admin()) {
    require_once dirname(__FILE__) . '/admin.php';
}

/********************************/
/** Installation/Deinstallation */
/********************************/

/**
 * Called on plugin deinstallation
 */
function polizeipresse_uninstall() {
    polizeipresse_delete_options();
}
register_uninstall_hook(__FILE__, 'polizeipresse_uninstall');


/********************************/
/** Activation/Deactivation     */
/********************************/

/**
 * Called on plugin activation. Enables the cron job.
 */
function polizeipresse_activate() {
    if (polizeipresse_is_cron_enabled()) {
        polizeipresse_activate_cron();
    }

    //
    // Do database migration. Needed for upgrade 0.2 -> 0.3
    //

    // Copy office_id to office_id0.
    $office_id_old = polizeipresse_get_option(POLIZEIPRESSE_OFFICE_ID);
    if ($office_id_old) {
        polizeipresse_update_option(POLIZEIPRESSE_OFFICE_ID0, $office_id_old);
        polizeipresse_delete_option(POLIZEIPRESSE_OFFICE_ID);
    }

    // Copy office_name to office_name0.
    $office_name_old = polizeipresse_get_option(POLIZEIPRESSE_OFFICE_NAME);
    if ($office_name_old) {
        polizeipresse_update_option(POLIZEIPRESSE_OFFICE_NAME0, $office_name_old);
        polizeipresse_delete_option(POLIZEIPRESSE_OFFICE_NAME);
    }

    // Copy last_story_id to last_story_id0
    $last_story_id_old = polizeipresse_get_option(POLIZEIPRESSE_CRON_LAST_STORY_ID);
    if ($last_story_id_old) {
        polizeipresse_update_option(POLIZEIPRESSE_CRON_LAST_STORY_ID0, $last_story_id_old);
        polizeipresse_delete_option(POLIZEIPRESSE_CRON_LAST_STORY_ID);
    }

    // Copy cron_add_categorie_id to default_category_id.
    $category_id_old = polizeipresse_get_option(POLIZEIPRESSE_CRON_ADD_CATEGORIE_ID);
    if ($category_id_old) {
        polizeipresse_update_option(POLIZEIPRESSE_DEFAULT_CATEGORY_ID, $category_id_old);
        polizeipresse_delete_option(POLIZEIPRESSE_CRON_ADD_CATEGORIE_ID);
    }

    //
    // End migration
    //
}
add_action('init', 'polizeipresse_activate');

/**
 * Called on plugin deactivation. Disables the cron job.
 */
function polizeipresse_deactivate() {
    polizeipresse_deactivate_cron();
}
register_deactivation_hook(__FILE__, 'polizeipresse_deactivate');


/********************************/
/** Cron job                    */
/********************************/

/**
 * Activates the cron job
 */
function polizeipresse_activate_cron() {
    if (!wp_next_scheduled('polizeipresse_load_stories_event')) {
        wp_schedule_event(time(), 'hourly', 'polizeipresse_load_stories_event');
    }
}

/**
 * The cron job to load new stories regulary.
 */
function polizeipresse_load_stories_hourly() {
    polizeipresse_load_stories_and_create_posts();
}
add_action('polizeipresse_load_stories_event', 'polizeipresse_load_stories_hourly');

/**
 * Deactivates the cron job
 */
function polizeipresse_deactivate_cron() {
    wp_clear_scheduled_hook('polizeipresse_load_stories_event');
}

/**
 * Determines whether the cron job is enabled
 */
function polizeipresse_is_cron_enabled() {
    $cron_enabled =  polizeipresse_get_option(POLIZEIPRESSE_CRON_ENABLED);
    if ($cron_enabled == true) {
        return true;
    }
    else {
        return false;
    }
}


/********************************/
/** Story functions             */
/********************************/

/**
 * Initializes the plugin
 */
function polizeipresse_init()
{
    wp_register_style('plugin_style', plugin_dir_url(__FILE__) . '/css/Polizeipresse.css');
    wp_enqueue_style('plugin_style',WP_PLUGIN_URL . '/css/Polizeipresse.css');

    // Localize plugin
    load_plugin_textdomain('Polizeipresse', false, dirname(plugin_basename(__FILE__)) . '/i18n/');
}
add_action('init', 'polizeipresse_init');

/**
 * Loads the newest stories from PressePortal.de and creates new posts for each of them.
 * Previously loaded stories are ignoried. Stories are filtered by filter rules settings.
 * The stories are returned in chronological order.
 */
 function polizeipresse_load_stories_and_create_posts() {

    polizeipresse_update_option(POLIZEIPRESSE_CRON_LAST_DATE, current_time('timestamp'));
    polizeipresse_update_option(POLIZEIPRESSE_CRON_LAST_STATUS, null);

    // Load stories for each office
    $success = true;
    for($office = 0; $office < POLIZEIPRESSE_MAX_OFFICE_COUNT && $success = true; $office++) {
        // Get id of office to load stories for
        $office_id = polizeipresse_get_option(POLIZEIPRESSE_OFFICE_ID . $office);

        // Do not execute empty configs
        if (empty($office_id)) {
            continue;
        }

        // Get id of last retrieved story
        $last_story_id = polizeipresse_get_option(POLIZEIPRESSE_CRON_LAST_STORY_ID . $office);

        // Do load stories for current office
        $stories = polizeipresse_load_stories($office_id);

         // Check for valid response
        if (isSet($stories)) {
            $success = true;

            // Add new posts for new stories
            $max_story_id = $last_story_id;
            foreach($stories AS $story) {
                $story_id = $story->id;

                // Do we need to create a new post for this story?
                if ($story_id > $last_story_id) {
                    polizeipresse_add_story($story, $office);
                    $max_story_id = $story_id;
                }
            }

            // Remember the highest story id so it don't get added again.
            polizeipresse_update_option(POLIZEIPRESSE_CRON_LAST_STORY_ID . $office, $max_story_id);
        } else {
            // No data received. Something has gone wrong.
            $success = false;
        }
    }

    polizeipresse_update_option(POLIZEIPRESSE_CRON_LAST_STATUS, $success);
}
do_action('polizeipresse_load_stories_and_create_posts');

/**
 * Loads the newest stories from PressePortal.de. Stories are filtered by filter rules settings.
 * The stories are returned in chronological order.
 */
function polizeipresse_load_stories($office_id, $load_teaser_only = false) {
    require_once(dirname(__FILE__) . '/Presseportal.class.php');

    // Get options
    $api_key = polizeipresse_get_option(POLIZEIPRESSE_API_KEY);
    $filter_positive = polizeipresse_get_option(POLIZEIPRESSE_FILTER_POSITIVE);
    $filter_negative = polizeipresse_get_option(POLIZEIPRESSE_FILTER_NEGATIVE);

    // Check for required options
    if (empty ($api_key) || empty ($office_id)) {
        return null;
    }

    // Prepare positive filters
    $positive_filters = explode(',', strtolower(trim($filter_positive)));
    foreach($positive_filters as $key => $val) {
        if(empty($val)){
            unset($positive_filters[$key]);
        } else {
            $positive_filters[$key] = trim($val);
        }
    }

    // Prepare negative filters
    $negative_filters = explode(',', strtolower(trim($filter_negative)));
    foreach($negative_filters as $key => $val) {
        if(empty($val)){
            unset($negative_filters[$key]);
        } else {
            $negative_filters[$key] = trim($val);
        }
    }

    $pp = new Presseportal($api_key, 'de');
    $pp->format = 'xml';
    $pp->limit = '30';

    if ($load_teaser_only) {
        $pp->teaser = true;
    }

    // Get latest stories for the company with the given department id
    $response = $pp->get_office_articles($office_id);

    $stories = array();
    if((!$response->error) && ($response->stories)) {
        foreach($response->stories AS $story) {
            $title = $story->title;

            if ($load_teaser_only) {
                $content = $story->teaser;
            }
            else {
                $content = $story->body;
            }


            // Apply postiv filters
            $filter_success = true;
            if (count($positive_filters) > 0) {
                $filter_success = (polizeipresse_do_filter($title, $positive_filters) || polizeipresse_do_filter($content, $positive_filters));
            }

            // Apply negativ filters
            if (count($negative_filters) > 0) {
                if (polizeipresse_do_filter($title, $negative_filters) || polizeipresse_do_filter($content, $negative_filters)) {
                    $filter_success = false;
                }
            }

            if ($filter_success) {
                $stories[] = $story;
            }
        }

        // Stories are in not in chronological order. So sort it;
        usort($stories, create_function('$value1, $value2', 'return ($value1 >= $value2) ? +1 : -1;'));
    } else {
        $stories = null;
    }

    return $stories;
}
do_action('polizeipresse_load_stories');

/**
 * Internal function: Filters a story
 */
function polizeipresse_do_filter($text, $filters) {
    $text = strtolower($text);

    $matches = false;
    for ($i = 0; $i < count($filters) && ($matches == false); $i++) {
        if (preg_match('/' . $filters[$i] . '/', $text)) {
            $matches = true;
        }
    }

    return $matches;
}

/**
 * Internal function: Creates a new post out of story.
 *
 * @param story Story to add
 * @param office Number of office
 */
function polizeipresse_add_story($story, $office) {

    // Format text
    $text = ereg_replace("\n\n", "<p/>", $story->body);
    $text = ereg_replace("\n", " ", $text);

    // Create new post text
    $post_content = '<div class="polizeipresse-post">';
    $post_content .= '<div class="text" style="text-align: justify;">' . $text . '</div>';
    $post_content .= '<div class="source">' . __('Source', 'Polizeipresse') . ': <a href="' . $story->url . '" target="_blank">www.polizeipresse.de</a></div>';
    $post_content .= '</div>';

    // Title
    $post_title = $story->title;

    // Categorie. Use special category for office or default if none is defined.
    $post_category = polizeipresse_get_option(POLIZEIPRESSE_OFFICE_CATEGORY_ID . $office);
    if (empty($post_category) || $post_category == -1) {
        // Use default category
        $post_category = polizeipresse_get_option(POLIZEIPRESSE_DEFAULT_CATEGORY_ID);
    }

    // User
    $post_user = polizeipresse_get_option(POLIZEIPRESSE_CRON_ADD_USER_ID);

    // Status
    $post_status = (polizeipresse_get_option(POLIZEIPRESSE_CRON_ADD_PUBLISH) ? 'publish' : 'draft');

    // Create new wordpress post
    $new_post = array(
        'post_title' => $post_title,
        'post_content' => $post_content,
        'post_status' => $post_status,
        'post_author' => $post_user,
        'post_type' => 'post',
        'post_category' => array($post_category)
    );
    $post_id = wp_insert_post($new_post);

    // Send notification email if configured
    $notify_email = polizeipresse_get_option(POLIZEIPRESSE_CRON_NOTIFY);
    if ($notify_email) {
        $user_data = get_userdata($post_user);

        $email_title = __('Polizeipresse: New post added', 'Polizeipresse') . ':' . $post_title;
        $email_message = $story->body . "\r\n\r\n" . __('URL to admin page', 'Polizeipresse') . ': ' . site_url('/wp-admin/edit.php');
        $email_sender = get_bloginfo('admin_email');

        $email_header = 'From: ' . $email_sender . "\r\n";
        $email_header .= "Content-type: text/plain; charset=UTF-8\r\n";

        mail($user_data->user_email, $email_title, $email_message, $email_header);
    }
}

?>