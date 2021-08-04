<?php

// will be called from jm-app-custom-types.php
function jmapp_register_notifications()
{
	register_post_type( 'notification', array
	(
		'labels' => array
		(
			'name' => __( 'Notifications' ),
			'singular_name' => __( 'Notification' ),
			'add_new' => __( 'Add New Notification' ),
			'add_new_item' => __( 'Add New Notification' ),
			'edit_item' => __( 'Edit Notification' ),
			'new_item' => __( 'New Notification' ),
			'view_item' => __( 'View Notification' ),
			'search_items' => __( 'Search Notifications' ),
			'not_found' => __( 'No Notifications found' ),
			'not_found_in_trash' => __( 'No Notifications found in Trash' ),
		),
		'public' => true,
		'has_archive' => false,
		'rewrite' => array('slug' => 'notification'),
		'taxonomies' => array(),
		'publicly_queryable' => true,
		'exclude_from_search' => false,
		'menu_position' => 20,
		'menu_icon' => 'dashicons-format-status',
		'show_in_rest' => true,
		'rest_base' => 'notifications',
		'supports' => array('title', 'custom-fields'),
	));
}

/*
Notification items should have the following attributes:
'title','body','timestamp','postId','url'
*/
function jmapp_save_notification($n) {
	$title = time() . ' - ' . $n['title'];
	$id = wp_insert_post(['post_title' => $title, 'post_type' => 'notification', 'post_status'=>'publish']);
	if ($id) {
		if (!is_array($n['data'])) $n['data'] = [];
		$n['data']['notificationId'] = $id;
		update_post_meta($id, 'json', json_encode($n));
		// update_post_meta($id, 'data', $n);
		return ($id);
	}
	return 0;
}

function jmapp_get_notifications($id = NULL, $count=-1)
{
	$args = ['post_type' => 'notification', 'post_status'=>['publish'], 'numberposts' => $count];
	if ($id !== NULL) $args['p'] = $id;

	$notifications = get_posts($args);
	foreach($notifications as $key => $n)
	{
		$notifications[$key]->meta = get_post_meta($n->ID);
	}
	return $notifications;
}

/* REST MODIFICATIONS FOR CUSTOM POST TYPES */
add_filter("rest_prepare_notification", 'jmapp_rest_prepare_notification', 10, 3);
function jmapp_rest_prepare_notification($data, $post, $request) {
	// $_data = [];
	// $_data['title'] = $post->post_title;
	// $_data['json'] = $post->json;
	// $_data['notification_data'] = $post->data;
	$data->data['notification'] = json_decode($post->json);
	$data->data['notification_json'] = $post->json;
	return $data;
}

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
		'data' => [],
	];
	if (!empty($id)) {
		$imageUrl = get_the_post_thumbnail_url($id);
		$imageUrl = ($imageUrl) ? $imageUrl : '';
		$post = get_post($id);
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
	else if (!empty($url)) $notification['data']['targetUrl'] = $url;
	return jmapp_send_notification($notification);
}

add_action('transition_post_status', 'jmapp_maybe_notify_on_publish', 99, $accepted_args=3);
function jmapp_maybe_notify_on_publish($new_status, $old_status, $post)
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
				'data' => [
					'postId' => $post->ID,
					'title' => 'New Content Published',
					'body' => $post->post_title,
					'targetUrl' => get_post_permalink($post->ID), // in case the app can't handle wordpress providers
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
	
	// get the options
	$stored_options = get_option('jmapp_options',array());
	$test_only = TRUE;
	if (!empty($stored_options['fcm_is_live']) && $stored_options['fcm_is_live'] == 1) $test_only = FALSE;
	if (!empty($n['testing']) && ($n['testing'] == 1 || $n['testing'] == '1')) $test_only = TRUE;

	if (! $test_only )
	{
		$notificationId = jmapp_save_notification($n);
		$n['data']['notificationId'] = $notificationId;
	}
	
	// NOTE: We are still using the legacy API
	// https://firebase.google.com/docs/cloud-messaging/http-server-ref
	// consider migrating to the new api, following instructions here:
	// https://firebase.google.com/docs/cloud-messaging/migrate-v1
	
	$fcm_url = 'https://fcm.googleapis.com/fcm/send';
	
	/*
	Firebase Messaging Documentation
	
	https://firebase.google.com/docs/cloud-messaging/concept-options
	https://firebase.google.com/docs/reference/fcm/rest/v1/projects.messages
	https://firebase.google.com/docs/cloud-messaging/http-server-ref


	FCM Legacy-style api call
	curl https://fcm.googleapis.com/fcm/send -H "Content-Type:application/json" -X POST -d "$DATA" -H "Authorization: key=FCM_SERVER_KEY"
	

	// FCM LEGACY-STYLE NOTIFICATION MESSAGE WITH OPTIONAL DATA
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
		'data'         => [
			'title'    => $n['title'],
			'subtitle' => $n['subtitle'],
			'body'     => $n['body'],
			'image'    => $n['image'],
		],
		'notification' => [
			'title'    => $n['title'],
			'subtitle' => $n['subtitle'],
			'body'     => $n['body'],
			'image'    => $n['image'],
			'icon'     => empty($stored_options['android_icon']) ? 'ic_stat_notify' : $stored_options['android_icon'],
			'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
			'sound'        => 'default',
		],
	];
	
	// add the 'data' field if needed
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
