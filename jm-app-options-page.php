<?php
$options = Array(
	'google_services_api_key'=>Array(
		'type'=>'text',
		'label'=>'Google Services (Maps) Server Key',
		'value'=>'',
		'description'=>'For the app to use Google Maps, you need an API key.',
		'admin_only' => 0
	),
	'google_services_json_file'=>Array(
		'type'=>'text',
		'label'=>'Absolute server path to Google Services JSON file',
		'value'=>'',
		'description'=>'used to generate Firebase compatible JWT tokens.',
		'admin_only' => 0
	),
	'fcm_server_key'=>Array(
		'type'=>'text',
		'label'=>'Firebase Cloud Messaging Server Key',
		'value'=>'',
		'description'=>'<a href="https://console.firebase.google.com">Firebase Console</a>. Choose Project &gt; Project Settings &gt; Cloud Messaging',
		'admin_only' => 0
	),
	'fcm_test_devices'=>Array(
		'type'=>'text',
		'label'=>'Firebase Cloud Messaging Test Device Token',
		'value'=>'',
		'description'=>'Will send test messages to these devices (comma separated).',
		'admin_only' => 0
	),
	'fcm_app_topic'=>Array(
		'type'=>'text',
		'label'=>'Firebase Cloud Messaging main "topic" for apps associated with this site.',
		'value'=>preg_replace('#https?://#', '', home_url()),
		'description'=>'Users of your app can subscribe to notifications according to topic. The value here must be unique to this website, and therefore, it probably should be the bundle id of your app or the full url of this website.',
		'admin_only' => 0
	),
	'fcm_extra_topics'=>Array(
		'type'=>'text',
		'label'=>'Extra topics to use when publishing',
		'value'=>'',
		'description'=>'The values here (comma-separated) will be used as topics whenever new content is published. If you have multiple sites running this plugin, it can be helpful to use the same topic on all of them. If another site claims this site as a "subsite" be sure to include that site\'s "main topic" here.',
		'admin_only' => 0
	),
	'app_subsites'=>Array(
		'type'=>'text',
		'label'=>'Sub-sites to tell the app about.',
		'value'=>'',
		'description'=>'If you run multiple WordPress sites with this plugin installed, one site must be the "master" site with the apps menu data, but you can let your users subscribe to notifications from the subsites by adding their URLs here separated by commas. Note: The values here must exactly match the "WordPress Address" of the other sites as specified on their "General Settings" page.',
		'admin_only' => 0
	),
	'auto_send_post_types'=>Array(
		'type'=>'text',
		'label'=>'Automatically send notifications on these post types.',
		'value'=>'post,sp_sermon',
		'description'=>'Push notifications will be sent whenever one of these post types is published. Enter a comma-separated list.',
		'admin_only' => 0
	),
	'app_notification_categories'=>Array(
		'type'=>'text',
		'label'=>'Specify the categories to expose as "subscribable" in the app.',
		'value'=>'',
		'description'=>'Users will be allowed to subscribe to notifications for these categories. The default category will always be subscribable. Use ALL to expose all categories. (Comma-separated category slugs)',
		'admin_only' => 0
	),
	'fcm_is_live'=>Array(
		'type'=>'checkbox',
		'label'=>'Send notifications for real to all devices.',
		'checkvalue'=>1,
		'value'=>1,
		'description'=>'Unless this is checked, all notifications will go to devices in the "test" group only.',
		'admin_only' => 0
	),
	// 'android_icon'=>Array(
	// 	'type'=>'text',
	// 	'label'=>'Android Icon Override',
	// 	'value'=>'ic_stat_notify',
	// 	'description'=>'Enter the name of a drawable resource available in your app. (e.g. ic_stat_notify)',
	// 	'admin_only' => 0
	// )
);


// populate values from those stored in the database
$stored_options = get_option('jmapp_options');
foreach ($options as $key=>$value)
{
	$options[$key]['value'] = $stored_options[$key];
}
?>

<div class="">
	<h2>Mobile App Notification Settings</h2>

	<form method="post" action="options.php">
		<?php settings_fields('jmapp_options'); ?>

		<table class="form-table">
			<?php foreach ($options as $key=>$value): ?>

			<?php if (($value['admin_only'] == 1) && (! current_user_can(JMAPP_ADMIN_CAP))) continue; ?>

			<tr valign="top">
				<th scope="row"><?php echo $value['label']; ?><?php if ($value['admin_only'] == 1):?><br />ADMIN
					ONLY<?php endif; ?></th>
				<td>
					<?php if ($value['type'] == 'checkbox') : ?>
					<input name="jmapp_options[<?php echo $key; ?>]" type="checkbox"
						value="<?php echo htmlentities($value['checkvalue']); ?>" <?php checked('1', $value['value']); ?> />
					<?php echo $value['label']; ?>
					<?php elseif ($value['type'] == 'text') : ?>
					<input style="width:60%;" name="jmapp_options[<?php echo $key; ?>]" type="text"
						value="<?php echo htmlentities($value['value']); ?>" />
					<?php elseif ($value['type'] == 'disabled') : ?>
					<input style="width:60%;" name="jmapp_options[<?php echo $key; ?>]" type="text" disabled="disabled"
						value="<?php echo htmlentities($value['value']); ?>" />
					<?php else: ?>
					<input style="width:60%;" name="jmapp_options[<?php echo $key; ?>]" type="<?php echo $value['type']; ?>"
						value="<?php echo htmlentities($value['value']); ?>" />
					<?php endif; ?>
					<p><small><?php echo $value['description']; ?></small></p>
				</td>
			</tr>

			<?php endforeach; ?>

		</table>

		<?php submit_button(); ?>

	</form>

	<?php /*
	<h3>Registered Devices</h3>
	<?php if (!empty($stored_options['fcm_app_topic'])) : ?>

	<table style="width:100%;">
		<tr>
			<th style="border-bottom:1px solid black;">Created</th>
			<th style="border-bottom:1px solid black;">Platform</th>
			<th style="border-bottom:1px solid black;">Name</th>
			<th style="border-bottom:1px solid black;">Token</th>
		</tr>

		<?php
		// $key = '2c6b294025577877583f4834793152543845495f34213c6b507b3f52773a7d2a';
		// $url = "https://jeffmikels.org/notifications/api.php?key=${key}&action=devices&topic=";
		// $url .= $stored_options['fcm_app_topic'];
		// $devices = json_decode(file_get_contents($url), TRUE);
		$devices = [];

		?>

		<?php foreach($devices['data'] as $device) : ?>

		<tr>
			<td style="text-align:center;"><?php echo $device['created_at'] ?></td>
			<td style="text-align:center;"><?php echo $device['platform'] ?></td>
			<td style="text-align:center;"><?php echo $device['name'] ?></td>
			<td style="text-align:center;"><textarea rows=2
					style="font-size:8px;width:300px;"><?php echo $device['device_token']?></textarea></td>
		</tr>

		<?php endforeach;?>

	</table>
	<?php else:?>

	NONE

	<?php endif;?>
	*/?>

</div>