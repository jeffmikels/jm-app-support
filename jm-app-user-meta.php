<?php


// USER FEATURES
function jmapp_get_user($id) {
	$user = get_userdata($id);
	
	// make sure all users accessed by this plugin gain the JMAPP_USER_ROLE
	if (!$user->has_cap(JMAPP_PW_CAP)) $user->add_role(JMAPP_USER_ROLE);
	
	$meta = get_user_meta($id);
	$retval = [];
	$retval['id'] = $id;
	$retval['email'] = $user->user_email;
	$retval['username'] = $user->user_login;
	$retval['first_name'] = $user->first_name;
	$retval['first_name'] = $user->first_name;
	$retval['last_name'] = $user->last_name;
	$retval['cell_phone'] = $user->cell_phone;
	if (function_exists('get_wp_user_avatar'))
		$retval['avatar_url'] = get_wp_user_avatar_src($id);
	else
		$retval['avatar_url'] = get_avatar_url($user, ['rating'=>'PG', 'scheme' => 'https']);
	$retval['devices'] = get_user_meta($id, 'device');
	
	// $retval['firebasejwt'] = '';
	
	// $retval['meta'] = $meta;
	$retval['nickname'] = $user->display_name;
	if (empty($user->display_name)) $retval['nickname'] = $user->user_login;

	return $retval;
}

// device tokens are used to target push notifications
// each user account can have more than one
function jmapp_user_add_device($user_id, $newdevice) {
	// device data should have the following fields
	// device_token, device_type, device_name
	// only the first is required
	
	// does this token already exist?
	$devices = get_user_meta($user_id, 'device');
	foreach ($devices as $device) {
		if ($device['device_token'] == $newdevice['device_token']) return TRUE;
	}
	return add_user_meta($user_id, 'device', $newdevice, FALSE);
}

abstract class JMAPP_User_Meta_Box
{
	// public static function add()
	// {
	// 	$screens = ['user'];
	// 	foreach ($screens as $screen) {
	// 		add_meta_box(
	// 			'jmapp_user_devices_meta_box',     // Unique ID
	// 			'Registered Devices',              // Box title
	// 			[self::class, 'html'],             // Content callback, must be of type callable
	// 			$screen                            // Post type
	// 		);
	// 	}
	// }
 
	// public static function save($post_id)
	// {
	// 	if (array_key_exists('wporg_field', $_POST)) {
	// 		update_post_meta(
	// 			$post_id,
	// 			'_wporg_meta_key',
	// 			$_POST['wporg_field']
	// 		);
	// 	}
	// }
 
	public static function html($user)
	{
		$devices = get_user_meta($user->ID, 'device');
		
		?>
		<style>
			table.jmapp-table {width:100%;}
			table.jmapp-table th, table.jmapp-table td {text-align:left;}
			table.jmapp-table textarea {width:100%;}
		</style>
		<hr />
		
		
		<h2 class="jmapp-registered-devices-header">JM Apps Data</h2>
		<?php if (empty($devices)):?>
			<h3>No Devices Registered for Push Notifications</h3>
		<?php else: ?>
		<h3 class="jmapp-registered-devices-header">Devices Registered for Push Notifications</h3>
		<table class="jmapp-table">
			<tr><th>name</th><th>device</th><th>token</th></tr>
		
		<?php
		foreach ($devices as $device) {
			echo '<tr><td>'.$device['device_name'].'</td>';
			echo '<td>'.$device['device_type'].'</td>';
			echo '<td><textarea>'.$device['device_token'].'</textarea></td></tr>';
		}
		?>
		
		</table>
		<?php endif;?>
		
		<table class="form-table">
			<tbody>
				<tr scope="row">
					<th><label>Submitted Cell</label></th>
					<td><?php echo empty($user->cell_phone) ? 'NONE' : $user->cell_phone; ?></td>
				</tr>
			</tbody>
		</table>
		<hr />
		<?php
	}
}
add_action('show_user_profile', ['JMAPP_User_Meta_Box', 'html']);
add_action('edit_user_profile', ['JMAPP_User_Meta_Box', 'html']);
