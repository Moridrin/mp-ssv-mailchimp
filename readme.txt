=== SSV MailChimp ===
Contributors: moridrin
Tags: ssv, mp-ssv, mailchimp, mail, members, user management, moridrin, Users, sportvereniging, sports club,
Requires at least: 3.0.1
Tested up to: 4.7
Stable tag: trunk
License: WTFPL
License URI: http://www.wtfpl.net/txt/copying/

SSV MailChimp is a plugin that allows you to connect other SSV plugins to MailChimp.

== Description ==
SSV MailChimp is a plugin that allows you to link other SSV plugins to MailChimp. With this plugin you can:
* Create lists for event registrants (requires SSV Events)
* Create members in list on user registration (requires SSV Users)
* Update user meta to MailChimp Merge Fields (requires SSV Users).
* Etc.
This plugin is fully compatible with the SSV library which can add functionality like: Users, Events, etc.

== Installation ==
1. Upload the plugin files to the `/wp-content/plugins/ssv-mailchimp` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Use the SSV Options->MailChimp screen to configure the plugin
1. (Make your instructions match the desired user flow for activating and installing your plugin. Include any steps that might be needed for explanatory purposes)

== Frequently Asked Questions ==
= How do I request a feature? =
The best way is to add an issue on GitHub (https://github.com/Moridrin/ssv-mailchimp/issues). But you can also send an email to J.Berkvens@Moridrin.com (the lead developer).
= How do I report a bug? =
The best way is to add an issue on GitHub (https://github.com/Moridrin/ssv-mailchimp/issues). But you can also send an email to J.Berkvens@Moridrin.com (the lead developer).

== Changelog ==
= 3.1.6 =
* Updating name fields disabled (due to possible bugs)

= 3.1.5 =
* Not trying to make requests without API Key
* Register only functionality (without metadata links)

= 3.1.4 =
* Successful Code Inspection
* Add Registrants to List

= 3.1.2 =
* Create List on Event Create
* Add Registrants to List

= 3.1.1 =
* curl replaced with wp_remote_*
* Push all to MailChimp function added

= 3.1.0 =
* Namespaces added

= 3.0.0 =
* Rebuild from the ground up

= 2.0.0 =
* Merge Tags support

= 1.0.0 =
* Linking to MailChimp with API Key
* Create member in list on user registration (only from SSV-Users plugin)
