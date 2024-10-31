=== Plugin Name ===
Contributors: strunker
Donate link: http://wordpress.org/extend/plugins/polizeipresse/
Tags: police, news, german
Requires at least: 3.0
Tested up to: 3.9
Stable tag: 0.3.2

This plugin loads the latest news from German police stations and displays them in your blog.

== Description ==

This plugin loads the latest news from German police stations and displays them in your blog.
The plugin offers two ways to publish the news:

1. You can include a widget which always displays the latest news. For performance reasons the news are cached
   for some minutes to reduce remote calls.

1. The plugin offers a cronjob which automatically loads the latest news and creates new posts in your blog. You
   can choose if you want to publish them directly oder edit them first. Whatever you choose you can get notified
   by email if a new polic story is added.

== Installation ==

1. Upload the directory `Polizeipresse` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= Where do I get the required API-Key =

You need to register to [Polizeipresse](http://www.presseportal.de/services/).

= Where can I find the office id =

You can find the office id of your German police station [here](http://www.presseportal.de/polizeipresse/p_dienststellen.htx).

= How often will the cronjob check for news =

Every hour.

= There are too many news I don't care of. Can you help? =

Yes, you can define a filter to get the news you want.

== Screenshots ==

* General options page: screenshot-1.png.
* Filter options page: screenshot-2.png.
* Cronjob options page: screenshot-3.png.
* Widget options page: screenshot-4.png.

== Changelog ==

= 0.3.2 =
* Time of new posts fixed
* Ttime of last cron fixed

= 0.3.1 =
* Fix for migration problems

= 0.3 =
* Support for multiple police offices added
* Separate categories for each police office added

= 0.2 =
* Widget added
* English translation added

= 0.1 =
* Initial version

== Upgrade Notice ==

No further actions
