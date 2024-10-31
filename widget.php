<?php
add_action('widgets_init', 'polizeipresse_widget_load');

/**
 * Loads the widget
 */
function polizeipresse_widget_load() {
	register_widget('Polizeipresse_Widget');
}

/**
 * The widget class
 */
class Polizeipresse_Widget extends WP_Widget {

	/**
	 * Name of cache file
	 */
	private $cache_filename = 'widget.cache';

	/**
	 * Constructor
	 */
	function Polizeipresse_Widget() {

		$widget_options = array (
			'classname' => 'polizeipresse_widget',
			'description' => __('Displays latest police stories', 'Polizeipresse')
		);

		$this->WP_Widget('polizeipresse-latest-stories', 'Polizeipresse', $widget_options);
	}

	/**
	 * Prints the widget
	 */
	function widget($args, $instance) {
		$title = apply_filters('widget_title', $instance['title']);
		$number = $instance['number'];
		$show_date = $instance['show_date'];
		$cachetime = $instance['cachetime'];

		echo $args['before_widget'];

		// Show title
		if ($title) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		// Load stories! First try to use cache. If cache is expired reload stories from server.
		$stories = $this->load_stories_from_cache($cachetime);
		if (!$stories) {
			$stories = polizeipresse_load_stories(true);
			$this->update_stories_in_cache($stories);
		}

		// Show stories
		if ($stories) {
			// Stories are returned in chronological order. So reverse order to display newsest first.
			$stories = array_reverse($stories);

			print('<ul>');
			$counter = 0;
			foreach($stories AS $story) {
				$counter++;

				if ($counter <= $number) {
					print('<li>');
					print('<a href="' . $story->url . '" target="_blank"');
					print(' title="' . $story->teaser . '">');

					// Show date if wanted
					if ($show_date) {
						print(date_i18n(get_option('date_format'), strtotime($story->published)) . ' - ');
					}

					print($story->title);
					print ('</a>');
					print('</li>');
				}
			}
			print('</ul>');
		}
		else {
			print '<p>' . __('No stories') . '</p>';
		}

		echo $args['after_widget'];
	}

	/**
	 * Updates widget's settings.
	 */
	function update($new_instance, $old_instance) {
		$instance = $old_instance;

		$instance['title'] = strip_tags($new_instance['title']);
		$instance['number'] = strip_tags($new_instance['number']);
		$instance['show_date'] = strip_tags($new_instance['show_date']);
		$instance['cachetime'] = strip_tags($new_instance['cachetime']);

		return $instance;
	}

	/**
	 * Shows the settings form
	 */
	function form($instance) {

		// Set default values
		$defaults = array (
			'title' => __('Latest police stories', 'Polizeipresse'),
			'number' => '5',
			'show_date' => true,
			'cachetime' => 600
		);

		$instance = wp_parse_args((array) $instance, $defaults);
?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title', 'Polizeipresse'); ?>:</lable>
			<input type="text" class="widefat"
				   name="<?php echo $this->get_field_name('title'); ?>"
				   value="<?php echo $instance['title']; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id('number'); ?>"><?php _e('Story count', 'Polizeipresse'); ?>:</lable>
			<select name="<?php echo $this->get_field_name('number'); ?>">
				<option <?php if($instance['number'] == 1) echo 'selected="selected"'; ?> value="1">1</option>
				<option <?php if($instance['number'] == 3) echo 'selected="selected"'; ?> value="3">3</option>
				<option <?php if($instance['number'] == 5) echo 'selected="selected"'; ?> value="5">5</option>
				<option <?php if($instance['number'] == 10) echo 'selected="selected"'; ?> value="10">10</option>
				<option <?php if($instance['number'] == 15) echo 'selected="selected"'; ?> value="15">15</option>
				<option <?php if($instance['number'] == 20) echo 'selected="selected"'; ?> value="20">20</option>
			</select>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id('show_date'); ?>"><?php _e('Show date', 'Polizeipresse'); ?>:</lable>
			<input type="checkbox"
				   name="<?php echo $this->get_field_name('show_date'); ?>"
				   <?php echo ($instance['show_date']) ? 'checked' : ''; ?> />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id('cachetime'); ?>"><?php _e('Cache timeout', 'Polizeipresse'); ?>:</lable>
			<select name="<?php echo $this->get_field_name('cachetime'); ?>">
				<option <?php if($instance['cachetime'] ==   60) echo 'selected="selected"'; ?> value="60">1 <?php _e('minute', 'Polizeipresse'); ?></option>
				<option <?php if($instance['cachetime'] ==  120) echo 'selected="selected"'; ?> value="120">2 <?php _e('minutes', 'Polizeipresse'); ?></option>
				<option <?php if($instance['cachetime'] ==  300) echo 'selected="selected"'; ?> value="300">5 <?php _e('minutes', 'Polizeipresse'); ?></option>
				<option <?php if($instance['cachetime'] ==  600) echo 'selected="selected"'; ?> value="600">10 <?php _e('minutes', 'Polizeipresse'); ?></option>
				<option <?php if($instance['cachetime'] == 1800) echo 'selected="selected"'; ?> value="1800">30 <?php _e('minutes', 'Polizeipresse'); ?></option>
				<option <?php if($instance['cachetime'] == 3600) echo 'selected="selected"'; ?> value="3600">1 <?php _e('hour', 'Polizeipresse'); ?></option>
				<option <?php if($instance['cachetime'] == 7200) echo 'selected="selected"'; ?> value="7200">2 <?php _e('hours', 'Polizeipresse'); ?></option>
			</select>
		</p>

<?php
	}

	/**
	 * Loads the stories from cache. If cache is empty or expired, null is returned.
	 * The cachetime is set in seconds.
	 */
	function load_stories_from_cache($cachetime) {
		$cache_file = dirname(__FILE__) . '/' . $this->cache_filename;

		if (file_exists($cache_file)
           && (time() - $cachetime < filemtime($cache_file))
           && filesize($cache_file) > 0) {
			$fp = fopen($cache_file, 'r');
			flock($fp, LOCK_SH); // Acquire read lock
			$stories = fread($fp, filesize($cache_file));
			flock($fp, LOCK_UN); // Release the lock
			fclose($fp);

			return unserialize($stories);
        } else {
        	return null;
        }
	}

	/**
	 * Stories the stories in cache.
	 */
	function update_stories_in_cache($stories) {
		$cache_file = dirname(__FILE__) . '/' . $this->cache_filename;

		$fp = fopen($cache_file, 'w');
		flock($fp, LOCK_EX); // Acquire write lock
		fwrite($fp, serialize($stories));
		flock($fp, LOCK_UN); // Release lock
		fclose($fp);
	}
}