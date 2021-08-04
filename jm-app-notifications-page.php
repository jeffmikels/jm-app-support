<?php
// populate values from those stored in the database
$stored_options = get_option('jmapp_options');
?>

	<script type="text/javascript">
		function jmapp_check_submit()
		{
			console.log('preparing to submit');
			
			// check to see if form has all required fields
			var title = jQuery('#jmapp_now_title').val();
			var subtitle = jQuery('#jmapp_now_subtitle').val();
			var message = jQuery('#jmapp_now_message').val();
			var image = jQuery('#jmapp_now_image').val();
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
					jmapp_now_image: image,
					jmapp_now_url: url,
					jmapp_now_custom: custom,
					jmapp_now_id: id,
					jmapp_now_test: testing,
					jmapp_now_ready: ready,
				};
				
				jQuery.post(ajaxurl, data, function(res){
					console.log(res);
					
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
				
				// jQuery('#jmapp_now_form').hide();
				jQuery('#jmapp_alert').html('SENDING...');
			}
			else
			{
				jQuery('#jmapp_alert').html('You must include a title, message, and check the "Are You Sure?" box.');
			}
		}
	</script>

<div class="">
	<div class="jmapp_alert" style="color:red; text-transform:uppercase;" id="jmapp_alert"></div>
	<form action="admin-post.php" method="post" onsubmit="jmapp_check_submit(); return false;" id="jmapp_now_form">
		<h2>Send Push Notification</h2>
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
				<th scope="row">Image URL:</th>
				<td><input style="width:60%;" type="text" id="jmapp_now_image" value="" /></td>
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
				<th scope="row">Send to Testing Devices Only?</th>
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
</div>