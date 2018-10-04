<?php

add_action( 'wp_ajax_jmapp_ajax_notify', 'jmapp_ajax_notify', 99 );
function jmapp_ajax_notify()
{
	$res = jmapp_maybe_notify();
	echo $res;
	wp_die();
}

add_action( 'admin_post_jmapp_maybe_notify', 'jmapp_maybe_notify', 99 );
function jmapp_maybe_notify() {
	// generate notification
	$title = $_POST['jmapp_now_title'];
	$subtitle = $_POST['jmapp_now_subtitle'];
	$message = $_POST['jmapp_now_message'];
	$id = $_POST['jmapp_now_id'];
	$url = $_POST['jmapp_now_url'];
	$testing = empty($_POST['jmapp_now_test']) ? 'false' : 'true';
	$ready = $_POST['jmapp_now_ready'];
	$notification = [
		'title' => $title,
		'subtitle' => $subtitle,
		'message' => $message,
		'testing' => $testing,
	];
	if (!empty($id)) {
		$post = get_post($id);
		$notification['big_picture'] = get_the_post_thumbnail_url($id);
		$notification['data'] = [
			'providerData' => [
				'title' => $post->post_title,
				'provider' => 'wordpress',
				'arguments' => [
					'endpoint' => get_post_permalink($id),
					'static' => 'true'
				]
			]
		];
	}
	else if (!empty($url)) $notification['url'] = $url;
	return jmapp_send_notification($notification);
}

add_action('transition_post_status', 'jmapp_publish_post', 99, $accepted_args=3);
function jmapp_publish_post($new_status, $old_status, $post)
{
	
	if ($new_status != 'publish') return;
	if ($old_status == 'publish') return;
	
	// get the options
	$stored_options = get_option('jmapp_options',array());
	
	if (empty($stored_options['onesignal_app_id']) || empty($stored_options['onesignal_rest_key']))
	{   
		jmapp_err('OneSignal app is active but not set up. No notifications were sent.');
		return;
	}
	
	// check to see if post is one of the valid post types for notifications
	foreach (explode(',', $stored_options['auto_send_post_types']) as $post_type)
	{
		
		if (trim($post_type) == $post->post_type)
		{
			// generate notification
			$notification = [
				'title' => 'New Content Published!',
				'subtitle' => '',
				'message' => $post->post_title,
				'url' => get_post_permalink($post->ID),
				'big_picture' => get_the_post_thumbnail_url($post),
				'data' => [
					'providerData' => [
						'title' => $post->post_title,
						'provider' => 'wordpress',
						'arguments' => [
							'endpoint' => get_post_permalink($post->ID),
							'static' => 'true'
						]
					]
				]
			];
			jmapp_msg('OneSignal push notifications were sent.');
			jmapp_send_notification($notification);
			return;
		}
	}
}

// PLUGIN FUNCTIONS
function jmapp_send_notification($n)
{
	// get the options
	$stored_options = get_option('jmapp_options',array());
	$test_only = TRUE;
	if (!empty($stored_options['onesignal_is_live']) && $stored_options['onesignal_is_live'] == 1) $test_only = FALSE;
	if (!empty($n['testing']) && $n['testing'] == 'true') $test_only = TRUE;
		
	$os_url = 'https://onesignal.com/api/v1/notifications';
	
	$fields = array(
		'app_id' => $stored_options['onesignal_app_id'],
		'included_segments' => array('Test Devices'),
		'contents' => ['en' => $n['message']],
		'headings' => ['en' => $n['title']],
		'subtitle' => ['en' => $n['subtitle']],
	);
	
	// if 'url' is set, the device will launch
	// that url in a web browser when notification
	// is opened, but preferably, we want the app
	// to handle data inside the app if possible
	if (!empty($n['data']))
	{
		$fields['data'] = $n['data'];
	}
	elseif (!empty($n['url']))
	{
		$fields['url'] = $n['url'];
	}
	
	// This next line groups notifications together on android
	// it isn't useful unless you have a feature in the app to show
	// recent notifications
	// $fields['android_group'] = site_url(),
	
	if (! $test_only)
		$fields['included_segments'] = array('All');
		
	
	// check for big_picture
	if (!empty($n['big_picture'])) $fields['big_picture'] = $n['big_picture'];
	
	// specify the icon resource
	if (!empty($stored_options['android_icon']))
		$fields['small_icon'] = $stored_options['android_icon'];
	
	
	$fields = json_encode($fields);
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8',
											   'Authorization: Basic ' . $stored_options['onesignal_rest_key']));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HEADER, FALSE);
	curl_setopt($ch, CURLOPT_POST, TRUE);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

	$response = curl_exec($ch);
	curl_close($ch);
	
	return $response;
}