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
	$title    = stripslashes($_POST['jmapp_now_title']);
	$subtitle = stripslashes($_POST['jmapp_now_subtitle']);
	$message  = stripslashes($_POST['jmapp_now_message']);
	$url      = stripslashes($_POST['jmapp_now_url']);
	$custom   = stripslashes($_POST['jmapp_now_custom']);
	$id       = stripslashes($_POST['jmapp_now_id']);
	$testing  = stripslashes($_POST['jmapp_now_test']);
	$ready    = stripslashes($_POST['jmapp_now_ready']);
	$notification = [
		'title' => $title,
		'subtitle' => $subtitle,
		'body' => $message,
		'testing' => $testing,
	];
	if (!empty($id)) {
		$imageUrl = get_the_post_thumbnail_url($id);
		$imageUrl = ($imageUrl) ? $imageUrl : '';
		$post = get_post($id);
		$notification['big_picture'] = $imageUrl;
		$notification['image'] = $imageUrl;
		$notification['data'] = [
			'image' => $imageUrl,
			'postId' => $id,
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
	else if (!empty($custom)) $notification['data'] = json_decode($custom, TRUE);
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
	
	if (empty($stored_options['fcm_server_key']))
	{
		jmapp_err('Notifications have not been set up. No notifications were sent.');
		return;
	}
	
	// check to see if post is one of the valid post types for notifications
	foreach (explode(',', $stored_options['auto_send_post_types']) as $post_type)
	{
		
		if (trim($post_type) == $post->post_type)
		{
			$imageUrl = get_the_post_thumbnail_url($post);
			$imageUrl = ($imageUrl) ? $imageUrl : '';
			
			// generate notification
			$notification = [
				'title' => 'New Content Published!',
				'subtitle' => '',
				'body' => $post->post_title,
				'big_picture' => $imageUrl,
				'image' => $imageUrl,
				'url' => get_post_permalink($post->ID),
				'data' => [
					'postId' => $post->ID,
					'title' => 'New Content Published',
					'body' => $post->post_title,
					'targetUrl' => get_post_permalink($post->ID),
					'image' => $imageUrl,
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
			$result = jmapp_send_notification($notification);
			jmapp_msg('Push notifications were sent. <div class="debug" style="display:none">' . $result .'</div>');
			return;
		}
	}
}

// PLUGIN FUNCTIONS
function jmapp_send_notification($n)
{
	if (!is_array($n['data'])) $n['data'] = [];
	$notificationId = jmapp_save_notification($n);
	$n['data']['notificationId'] = $notificationId;
	
	// this plugin used to use onesignal for notifications
	// we are now using Firebase Cloud Messaging directly
	
	// get the options
	$stored_options = get_option('jmapp_options',array());
	$test_only = TRUE;
	if (!empty($stored_options['fcm_is_live']) && $stored_options['fcm_is_live'] == 1) $test_only = FALSE;
	if (!empty($n['testing']) && ($n['testing'] == 1 || $n['testing'] == '1')) $test_only = TRUE;

	$fcm_url = 'https://fcm.googleapis.com/fcm/send';
	
	/*
	Firebase Messaging Documentation

	curl https://fcm.googleapis.com/fcm/send -H "Content-Type:application/json" -X POST -d "$DATA" -H "Authorization: key=FCM_SERVER_KEY"
	
	https://firebase.google.com/docs/cloud-messaging/concept-options
	https://firebase.google.com/docs/reference/fcm/rest/v1/projects.messages
	https://firebase.google.com/docs/cloud-messaging/http-server-ref

	// LEGACY FCM NOTIFICATION MESSAGE WITH OPTIONAL DATA
	// targeting devices: use "to", "registration_ids", "condition" or none
	{
		"condition" : "'TopicA' in topics && ('TopicB' in topics || 'TopicC' in topics)",
		"to":"[device_token] | /topics/[topic] (optional)",
		"registration_ids" : [
			"token1","token2","etc"
		],
		"collapse_key": "up-to-four-unique-keys-per-server",
		"priority": "high / normal",
		"content_available": "true | false",   //notifies ios that it is a data message only
		"time_to_live"     : 604800,           // time in seconds
		"notification": {
			"title"    : "Title",
			"body"     : "Body",
			"subtitle" : "subtitle",
			"sound"    : "default | resource_name_of_sound_file",
			"badge"    : "on ios, puts a number on the home screen app icon",
			"icon"     : "android, drawable resource name for the notification icon",
			"color"    : "android, #rrggbb",
			"click_action":"FLUTTER_NOTIFICATION_CLICK",
		},
		"data" : {
			"key" : "value",
			"click_action": "FLUTTER_NOTIFICATION_CLICK",
			"complex_data": "{\"json_key\":\"json_value\"}"
		},
	}
	*/
	
	
	$fcm_fields = [
		'priority'     => 'normal',
		'time_to_live' => 60*60*24*7,
		'data'         => [],
		'notification' => [
			'title'    => $n['title'],
			'subtitle' => $n['subtitle'],
			'body'     => $n['body'],
			'icon'     => empty($stored_options['android_icon']) ? 'ic_stat_notify' : $stored_options['android_icon'],
			'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
			'sound'        => 'default',
		],
	];
	
	// make sure the 'data' contains title and body too
	if (!empty($n['data']))
	{
		$fcm_fields['data'] = $n['data'];
	}
	
	// also include all of fcm data in a json encoded field for backward compatibility
	$fcm_fields['data']['json'] = json_encode($fcm_fields['data']);
	
	if (!empty($stored_options['fcm_app_topic'])) {
		$topics = explode(',', $stored_options['fcm_app_topic']);
		$condition = [];
		foreach ($topics as $topic)
		{
			$topic = trim($topic);
			$condition[] = "'$topic' in topics";
		}
		$condition_string = implode(' || ', $condition);
		$fcm_fields['condition'] = $condition_string;
	}
	
	
	// This next line groups notifications together on android
	// it isn't useful unless you have a feature in the app to show
	// recent notifications
	$fcm_fields['android_group'] = site_url();
	
	if ($test_only)
	{
		if (!empty($stored_options['fcm_test_devices']))
		{
			$test_ids = explode(',',$stored_options['fcm_test_devices']);
			$fcm_fields['registration_ids'] = $test_ids;
			unset($fcm_fields['to']);
			unset($fcm_fields['condition']);
		}
		else
		{
			$fcm_fields['to'] = '/topics/testing';
			unset($fcm_fields['registration_ids']);
			unset($fcm_fields['condition']);
		}
	}
	
	// add fcm data to the notification post
	add_post_meta($notificationId, 'fcm_data', $fcm_fields, TRUE);
	
	$fcm_fields = json_encode($fcm_fields);
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $fcm_url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8',
											   'Authorization: key=' . $stored_options['fcm_server_key']));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HEADER, FALSE);
	curl_setopt($ch, CURLOPT_POST, TRUE);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $fcm_fields);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

	$response = curl_exec($ch);
	curl_close($ch);
	
	return $response;
}