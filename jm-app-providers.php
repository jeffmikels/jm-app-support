<?php
function jmapp_get_providers()
{
	return [
		'home' => [
			'type' => 'home',
			'tabbable' => false,
			'display' => 'Home Menu',
			'arguments' => ['url'],
			'instructions' => 'The home menu provider loads a json configuration from the url and populates the home menu. This is useful if you want to have multiple screens like the main home menu. If the url field is left blank, the data is pulled from the home_menu section of the main configuration file.'
		],
		'wordpress' => [
			'type' => 'wordpress',
			'tabbable' => true,
			'display' => 'Wordpress Data',
			'arguments' => ['endpoint','static','post_category','post_type','child_post_type', 'child_meta_key'],
			'field_options' => ['static'=>['true'=>'True', 'false'=>'False']],
			'field_help' => ['endpoint' => 'wordpress home page', 'static'=>'Set "static" to "true" when the endpoint returns raw JSON data in wordpress format. All other settings will be disregarded.'],
			'instructions' => 'The only required argument is "endpoint" and it should be set to the home page of your wordpress site. "post_category" will only pull posts with that category. "post_type" will only pull posts of that type. If "child_post_type" and "child_meta_key" are set, the app will also search for related posts. The child post must have a custom field named according to the "child_meta_key" whose value is the id of the parent post. For example, if you are using my Sermon Publisher plugin, you could set post_type to "sp_series", "child_post_type" to "sp_sermon", and "child_meta_key" to "sermon_series".'
		],
		'gcal' => [
			'type' => 'gcal',
			'tabbable' => true,
			'display' => 'Google Calendar',
			'arguments' => ['calid', 'url'],
			'instructions' => 'The "calid" looks like an email address and can be found in the sharing settings for that calendar. (xxxxxxxxx@group.calendar.google.com). You can alternatively supply a url to a location that simulates the Google Calendar search API. (That\'s useful when aggregating data from multiple calendars.)'
		],
		'elvanto-cal' => [
			'type' => 'elvanto-cal',
			'tabbable' => true,
			'display' => 'Elvanto Calendar',
			'arguments' => [],
			'instructions' => 'This will access the upcoming events from your Elvanto calendar. Note that the Elvanto apiKey must be setup in the app configuration files itself.'
		],
		'webview' => [
			'type' => 'webview',
			'tabbable' => false,
			'display' => 'Web Browser (in app)',
			'arguments' => ['url'],
			'instructions' => 'This link will open in a web browser built into your app.'
		],
		'link' => [
			'type' => 'link',
			'tabbable' => false,
			'display' => 'External Link',
			'arguments' => ['url'],
			'instructions' => 'This URL can be anything a mobile device can understand. Make a link to a phone call with tel:5555551234. Make a link to a text message with sms:5555551234. Make a link to an email with mailto:sample@example.com. Link to a website in the device default web browser with http://example.com'
		],
		'facebook' => [
			'type' => 'facebook',
			'tabbable' => true,
			'display' => 'Facebook',
			'arguments' => ['page_id'],
			'instructions' => 'Not implemented in the app yet.'
		],
		'twitter' => [
			'type' => 'twitter',
			'tabbable' => true,
			'display' => 'Twitter',
			'arguments' => ['user_id'],
			'instructions' => 'Not implemented in the app yet.'
		],
		'youtube' => [
			'type' => 'youtube',
			'tabbable' => true,
			'display' => 'YouTube',
			'arguments' => ['id','type'],
			'field_options' => ['type' => ['uploads'=>'Channel Uploads','live'=>'Channel Live Stream','playlists'=>'Channel Playlists','playlist'=>'Single Playlist']],
			'field_help' => ['id'=>'channel id or playlist id'],
			'instructions' => '"Type" can be "uploads", "live", "playlists", or "playlist". The "id" should be the channel id in each case except the last case where the id should be just the playlist id.'
		]
	];
}