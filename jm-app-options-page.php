<?php
/*
The old system used onesignal for push notifications.
This new system uses FCM (Firebase Cloud Messaging) directly.
*/
$options = Array(
	'fcm_server_key'=>Array(
		'type'=>'text',
		'label'=>'Firebase Cloud Messaging Server Key',
		'value'=>'',
		'description'=>'<a href="https://console.firebase.google.com">Firebase Console</a>. Choose Project &gt; Project Settings &gt; Cloud Messaging',
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
	'fcm_test_devices'=>Array(
		'type'=>'text',
		'label'=>'Firebase Cloud Messaging Test Device Token',
		'value'=>'',
		'description'=>'Will send test messages to these devices (comma separated).',
		'admin_only' => 0
	),
	'fcm_app_topic'=>Array(
		'type'=>'text',
		'label'=>'Firebase Cloud Messaging "topic" for apps associated with this site.',
		'value'=>'',
		'description'=>'Will send test messages only to apps that have "subscribed" to this site. NOTE: All Jeff Mikels\'s apps that use FCM for notifications will by default subscribe the device to a topic identified by the app\'s bundle id (i.e. org.jeffmikels.bradylane)',
		'admin_only' => 0
	),

	// 'onesignal_app_id'=>Array(
	// 	'type'=>'text',
	// 	'label'=>'OneSignal App ID',
	// 	'value'=>'',
	// 	'description'=>'',
	// 	'admin_only' => 0
	// ),
	// 'onesignal_rest_key'=>Array(
	// 	'type'=>'text',
	// 	'label'=>'OneSignal REST Key',
	// 	'value'=>'',
	// 	'description'=>'',
	// 	'admin_only' => 0
	// ),
	// 'onesignal_is_live'=>Array(
	// 	'type'=>'checkbox',
	// 	'label'=>'Send notifications for real to all devices.',
	// 	'checkvalue'=>1,
	// 	'value'=>1,
	// 	'description'=>'Unless this is checked, all notifications will go to devices in the "Test Devices" segment only.',
	// 	'admin_only' => 0
	// ),
	// 
	'auto_send_post_types'=>Array(
		'type'=>'text',
		'label'=>'Automatically send notifications on these post types.',
		'value'=>'post',
		'description'=>'A push notification will be sent whenever one of these post types is published. Enter a comma-separated list.',
		'admin_only' => 0
	),
	'android_icon'=>Array(
		'type'=>'text',
		'label'=>'Android Icon Resource',
		'value'=>'ic_stat_onesignal_default',
		'description'=>'Enter the name of a drawable resource available in your app. (e.g. ic_stat_notify)',
		'admin_only' => 0
	),
	/*
	'ios'=>Array(
		'type'=>'checkbox',
		'checkvalue'=>1,
		'label'=>'Push to iOS Devices',
		'value'=>0,
		'description'=>'Apple iOS must be set up as a platform in your OneSignal account for this to work.',
		'admin_only' => 0
	),
	'android'=>Array(
		'type'=>'checkbox',
		'checkvalue'=>1,
		'label'=>'Push to Android Devices',
		'value'=>0,
		'description'=>'Android must be set up as a platform in your OneSignal account for this to work.',
		'admin_only' => 0
	),
	'win8'=>Array(
		'type'=>'checkbox',
		'checkvalue'=>1,
		'label'=>'Push to Windows Phone 8.0 Devices',
		'value'=>0,
		'description'=>'Windows Phone 8.0 must be set up as a platform in your OneSignal account for this to work.',
		'admin_only' => 0
	),
	'win81'=>Array(
		'type'=>'checkbox',
		'checkvalue'=>1,
		'label'=>'Push to Windows Phone 8.1 Devices',
		'value'=>0,
		'description'=>'Windows Phone 8.1 must be set up as a platform in your OneSignal account for this to work.',
		'admin_only' => 0
	),
	'fire'=>Array(
		'type'=>'checkbox',
		'checkvalue'=>1,
		'label'=>'Push to Amazon Fire Devices',
		'value'=>0,
		'description'=>'Amazon Fire must be set up as a platform in your OneSignal account for this to work.',
		'admin_only' => 0
	),*/
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

				<?php if (($value['admin_only'] == 1) && (! current_user_can('jmapp_admin'))) continue; ?>

				<tr valign="top">
					<th scope="row"><?php echo $value['label']; ?><?php if ($value['admin_only'] == 1):?><br />ADMIN ONLY<?php endif; ?></th>
					<td>
						<?php if ($value['type'] == 'checkbox') : ?>
						<input name="jmapp_options[<?php echo $key; ?>]" type="checkbox" value="<?php echo htmlentities($value['checkvalue']); ?>" <?php checked('1', $value['value']); ?> /> <?php echo $value['label']; ?>
						<?php elseif ($value['type'] == 'text') : ?>
						<input style="width:60%;" name="jmapp_options[<?php echo $key; ?>]" type="text" value="<?php echo htmlentities($value['value']); ?>" />
						<?php elseif ($value['type'] == 'disabled') : ?>
						<input style="width:60%;"
							name="jmapp_options[<?php echo $key; ?>]"
							type="text"
							disabled="disabled"
							value="<?php echo htmlentities($value['value']); ?>"
							/>
						<?php else: ?>
						<input
							style="width:60%;"
							name="jmapp_options[<?php echo $key; ?>]"
							type="<?php echo $value['type']; ?>"
							value="<?php echo htmlentities($value['value']); ?>"
							/>
						<?php endif; ?>
						<p><small><?php echo $value['description']; ?></small></p>
					</td>
				</tr>

			<?php endforeach; ?>
			
		</table>

		<?php submit_button(); ?>

	</form>
	
	<script type="text/javascript">
		function jmapp_check_submit()
		{
			console.log('preparing to submit');
			
			// check to see if form has all required fields
			var title = jQuery('#jmapp_now_title').val();
			var subtitle = jQuery('#jmapp_now_subtitle').val();
			var message = jQuery('#jmapp_now_message').val();
			var url = jQuery('#jmapp_now_url').val();
			var custom = jQuery('#jmapp_now_custom').val();
			var id = jQuery('#jmapp_now_id').val();
			var testing = jQuery('#jmapp_now_test')[0].checked ? "1" : "0";
			var ready = jQuery('#jmapp_now_confirm')[0].checked ? "1" : "0";
			
			if (ready && title && message)
			{
				var data = {
					action: 'jmapp_ajax_notify',
					jmapp_now_title: title,
					jmapp_now_subtitle: subtitle,
					jmapp_now_message: message,
					jmapp_now_url: url,
					jmapp_now_custom: custom,
					jmapp_now_id: id,
					jmapp_now_test: testing,
					jmapp_now_ready: ready,
				};
				
				jQuery.post(ajaxurl, data, function(res){
					console.log(res);
					
					// onesignal used to report the number of recipients
					// with a recipients field
					// fcm just returns an array of message_ids in the results field
					// or one single message_id
					var plural = '';
					var recipients = 0;
					if (res.multicast_id && res.success == 1 && res.results.length > 1) {
						plural = 's';
						recipients = res.results.length;
					}
					if (res.message_id || (res.results && res.results.length == 1)) recipients = 1;
					
					jQuery('#jmapp_alert').html('Sent to ' + recipients + ' recipient' + plural + '.');
					// else jQuery('#jmapp_alert').html('FAILED TO SEND');
				}, 'json');
				
				jQuery('#jmapp_now_form').hide();
				jQuery('#jmapp_alert').html('SENDING...');
			}
			else
			{
				jQuery('#jmapp_alert').html('You must include a title, message, and check the "Are You Sure?" box.');
			}
		}
	</script>
	
	<div class="jmapp_alert" style="color:red; text-transform:uppercase;" id="jmapp_alert"></div>
	<form action="admin-post.php" method="post" onsubmit="jmapp_check_submit(); return false;" id="jmapp_now_form">
		<h2>Send Immediate Message</h2>
		<p>Please don't overuse this feature!</p>
		<table class="form-table">
			<tr valign="top">
				<th scope="row">Title:</th>
				<td><input style="width:60%;" type="text" id="jmapp_now_title" value="" /></td>
			</tr>
			<tr valign="top">
				<th scope="row">iOS 10 Subtitle:</th>
				<td><input style="width:60%;" type="text" id="jmapp_now_subtitle" value="" /></td>
			</tr>
			<tr valign="top">
				<th scope="row">Message:</th>
				<td><input style="width:60%;" type="text" id="jmapp_now_message" value="" /></td>
			</tr>
			<tr valign="top">
				<th scope="row">URL:</th>
				<td><input style="width:60%;" type="text" id="jmapp_now_url" value="" />
					<br /><small>The device will open a browser to this URL when the notification is clicked.</small>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">JSON Data:</th>
				<td><input style="width:60%;" type="text" id="jmapp_now_custom" value="" />
				<br /><small>Data for the app to handle. Must be valid JSON.</small>
			</td>
			</tr>
			<tr valign="top">
				<th scope="row">POST ID</th>
				<td><input style="width:60%;" type="text" id="jmapp_now_id" value="" />
					<br /><small>The device will open the data for this post inside the app when notification is clicked. (This takes precedence over the 'url' and 'JSON Data' settings above.)</small>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Send to Testing Devices?</th>
				<td><input type="checkbox" id="jmapp_now_test" checked="checked" /><br /><small>Send to devices in the "Test Devices" group only</small></td>
			</tr>
			<tr valign="top">
				<th scope="row">Are You Sure?</th>
				<td><input type="checkbox" id="jmapp_now_confirm" value="1" onClick="//return confirm('Are you sure?');" /><br /><small>You have to click this checkbox if you really want to send the notification.</small></td>
			</tr>
		</table>
		
		<input type="hidden" name="action" value="jmapp_maybe_notify" />
		<?php submit_button('Send Notification Now'); ?>
		
	</form>
	
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
		$key = '2c6b294025577877583f4834793152543845495f34213c6b507b3f52773a7d2a';
		$url = "https://jeffmikels.org/notifications/api.php?key=${key}&action=devices&topic=";
		$url .= $stored_options['fcm_app_topic'];
		$devices = json_decode(file_get_contents($url), TRUE);
	?>
	
	<?php foreach($devices['data'] as $device) : ?>
		
		<tr>
			<td style="text-align:center;"><?php echo $device['created_at'] ?></td>
			<td style="text-align:center;"><?php echo $device['platform'] ?></td>
			<td style="text-align:center;"><?php echo $device['name'] ?></td>
			<td style="text-align:center;"><textarea rows=2 style="font-size:8px;width:300px;"><?php echo $device['device_token']?></textarea></td>
		</tr>
		
	<?php endforeach;?>
	
		</table>

	<?php else:?>
		
	NONE
		
	<?php endif;?>
	
</div>