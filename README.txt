=== Axis for WordPress===
Contributors: aendrew
Tags: charts, graphs, data
Requires at least: 3.0.1
Tested up to: 4.0
Stable tag: 1.0.1
License: MIT
License URI: http://opensource.org/licenses/MIT

A plugin for creating fancy D3-based interactive charts in WordPress.

== Description ==

Axis is a plugin for creating fancy D3-based charts in WordPress, using [axisJS](http://github.com/times/axisJS),
an Angular-based framework for streamlined chart creation.

It is based off [Quartz Chartbuilder](http://www.github.com/Quartz/Chartbuilder) and created by
[Times Digital Development](http://timesdigitaldevelopment.tumblr.com). Please note that this
is a work in progress and currently under active development.

**Note:** We do *not* check the WordPress support forums. Please direct any support
queries to the [GitHub issue queue](https://github.com/times/Axis/issues).
The actual chart creation panel is a separate project called [axisJS](http://github.com/times/axisJS).
For anything regarding that, please use its own, separate [issue queue](http://github.com/times/axisJS/issues).

== Installation ==

1. Unzip and upload the `Axis` directory to `/wp-content/plugins/`.
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Create charts by clicking the "chart" button in TinyMCE.

== Frequently Asked Questions ==

= How is this related to Quartz's ChartBuilder =

The underlying framework, axisJS, was originally created as an Angular-based
rewrite of ChartBuilder. Much of the image generation code is taken from that,
as is the interface layout.

== Screenshots ==

== Changelog ==

= 1.0.1 =
* Bugfix release. Fixes reusable charts (#11), bar charts (#10)

= 1.0.1 =
* First stable release. Adds Media Library functionality (#4).

= 0.1.2 =
* Bugfix release. Adds grouped charts (times/axisJS#7).

= 0.1.1 =
* Bugfix release. Adds interactive charts to frontend (#2).

= 0.1.0 =
* Initial release.
