<?php
/**
 * Forked and modified from the "simply-json" plugin
 * from http://wordpress.org/extend/plugins/simply-json and authored by http://zeamedia.de
 */
use \Firebase\JWT\JWT;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define('JMAPP_SCORE_APIKEY', 'b34fbaddc8a0059358c901f7b682018e');

/* NEW WP-REST API STYLE */
/*
	AVAILABLE FUNCTIONS:
	game score
		+ submit score and day
			@auth not required
			POST wp-json/jmapp/v1/scores
			( args: score, apikey, name )
		+ get scores
			@auth not required
			GET wp-json/jmapp/v1/scores
			returns the top ten users of all time
			and the top 5 users of the previous week

	prayer walks
		+ create prayer walk
			@auth required
			POST wp-json/jmapp/v1/prayer_walks/create
			( args: title & description )

		+ get prayer walk(s)
			GET wp-json/jmapp/v1/prayer_walks/{optional id}

		+ get prayer walk(s)
			GET wp-json/jmapp/v1/prayer_walks/big_map

		+ add marker to prayer walk
			@auth required
			POST wp-json/jmapp/v1/prayer_walks/{prayer_walk_id}
			( args: timestamp & location & note & type OR markers[] )
			type can be 'automatic' or 'manual'

	notifications
		+ get notifications
			GET wp-json/jmapp/v1/notifications
		+ get notification
			GET wp-json/jmapp/v1/notifications/{optional id}
		+ get a list of potential notification topics
			GET wp-json/jmapp/v1/notifications/topics
			topics should be generated for post_type and post_category
	
	subsites
		+ get subsites
			GET wp-json/jmapp/v1/subsites
			returns a list of subsites
	
	user data
		+ get user data with prayer walks
			@auth required
			GET wp-json/jmapp/v1/user
		+ register new user
			POST wp-json/jmapp/v1/user/register
		+ update user data
			@auth required
			POST wp-json/jmapp/v1/user/update
	
	device token for user
		POST wp-json/jmapp/v1/device/add
			@auth required
			device_token={token}

	device token for non-logged-in user
		POST wp-json/jmapp/v1/device/add
			@auth not required
			device_token={token}

	user authentication (via JWT plugin)
		get authentication token
		POST wp-json/jwt-auth/v1/token/
			username={username}&password={password}

		use authentication token
		-H "Authorization: Bearer {token}"

	fire TV data feed
		GET wp-json/jmapp/v1/tvfeed
		returns a paged list of sermons with series as categories following this format:
		{
		  "id": "169313",
		  "title": "Beautiful Whale Tail Uvita Costa Rica",
		  "description": "Beautiful Whale Tail Uvita Costa Rica",
		  "duration": "86",
		  "thumbURL": "http://le2.cdn01.net/videos/0000169/0169313/thumbs/0169313__007f.jpg",
		  "imgURL": "http://le2.cdn01.net/videos/0000169/0169313/thumbs/0169313__007f.jpg",
		  "videoURL": "http://edge-vod-media.cdn01.net/encoded/0000169/0169313/video_1880k/T7J66Z106.mp4?source=firetv&channel_id=13454",
		  "categories": [
		    "Costa Rica Islands"
		  ],
		  "channel_id": "13454"
		},

*/

add_action( 'rest_api_init', function () {
	// WE ADD SPECIAL REST ROUTES TO ACCOUNT FOR CUSTOM ROLES/CAPABILITIES

	// ADMIN MAINTENANCE ENDPOINT ===========================
	register_rest_route( 'jmapp/v1', '/fix', array(
		'methods' => 'GET',
		'callback' => 'jmapp_fix_handler',
	) );

	/// TV FEED ENDPOINT ====================================
	register_rest_route( 'jmapp/v1', '/tvfeed', array(
		'methods' => 'GET',
		'callback' => 'jmapp_tvfeed_handler',
	) );
	
	register_rest_route( 'jmapp/v1', '/tvfeed/live', array(
		'methods' => 'GET',
		'callback' => 'jmapp_tvfeed_live_handler',
	) );
	

	/// GAME DATA ENDPOINTS =================================
	register_rest_route( 'jmapp/v1', '/scores', array(
		'methods' => 'POST',
		'callback' => 'jmapp_scores_post_handler',
	) );
	register_rest_route( 'jmapp/v1', '/scores', array(
		'methods' => 'GET',
		'callback' => 'jmapp_scores_get_handler',
	) );
	
	/// PRAYER WALK ENDPOINTS ===============================
	register_rest_route( 'jmapp/v1', '/prayer_walks/create', array(
		'methods' => 'POST',
		'callback' => 'jmapp_prayer_walks_create_handler',
	) );
	register_rest_route( 'jmapp/v1', '/prayer_walks', array(
		'methods' => 'GET',
		'callback' => 'jmapp_prayer_walks_get_handler',
	) ); // only returns prayer_walks for authenticated users
	register_rest_route( 'jmapp/v1', '/prayer_walks', array(
		'methods' => 'POST',
		'callback' => 'jmapp_prayer_walks_post_handler',
	) ); // only returns prayer_walks for authenticated users
	register_rest_route( 'jmapp/v1', '/prayer_walks/(?P<id>\d+)', array(
		'methods' => 'GET',
		'callback' => 'jmapp_prayer_walks_get_handler',
	) ); // only returns prayer_walks for authenticated users
	register_rest_route( 'jmapp/v1', '/prayer_walks/(?P<id>\d+)', array(
		'methods' => 'POST',
		'callback' => 'jmapp_prayer_walks_post_handler',
	) );
	register_rest_route( 'jmapp/v1', '/prayer_walks/big_map', array(
		'methods' => 'GET',
		'callback' => 'jmapp_prayer_walks_big_map_handler',
	) ); // returns the url for the map of all prayer walks
	register_rest_route( 'jmapp/v1', '/prayer_walks/stats', array(
		'methods' => 'GET',
		'callback' => 'jmapp_prayer_walks_stats_handler',
	) ); // returns the url for the map of all prayer walks
	register_rest_route( 'jmapp/v1', '/prayer_walks/(?P<id>.+)/marker', array(
		'methods' => 'POST',
		'callback' => 'jmapp_prayer_walks_marker_handler',
		'args' => array(
			'id' => array(
				'validate_callback' => function($param, $request, $key) {
					return is_numeric( $param );
				}
			),
		),
	) );

	// USER ENDPOINTS ====================================
	register_rest_route( 'jmapp/v1', '/user', array(
		'methods' => 'GET',
		'callback' => 'jmapp_user_handler',
	) );
	register_rest_route( 'jmapp/v1', '/user/register', array(
		'methods' => 'POST',
		'callback' => 'jmapp_user_register_handler',
	) );
	register_rest_route( 'jmapp/v1', '/user/update', array(
		'methods' => 'POST',
		'callback' => 'jmapp_user_update_handler',
	) );
	register_rest_route( 'jmapp/v1', '/user/firebase-jwt', array(
		'methods' => 'GET',
		'callback' => 'jmapp_user_firebasejwt_handler',
	) );

	// password reset requires two stages
	// stage one creates a verification key and sends an email
	// args: email
	register_rest_route( 'jmapp/v1', '/user/reset/1', array(
		'methods' => 'POST',
		'callback' => 'jmapp_user_reset_handler_1',
	) );
	// stage two just checks the verification key with the username
	// if a password is provided, it also changes the password
	// args: key, username, [password]
	register_rest_route( 'jmapp/v1', '/user/reset/2', array(
		'methods' => 'POST',
		'callback' => 'jmapp_user_reset_handler_2',
	));


	// DEVICE ENDPOINT =======================================
	register_rest_route( 'jmapp/v1', '/device/add', array(
		'methods' => 'POST',
		'callback' => 'jmapp_user_device_handler',
		'args' => array(
			'device_token' => array(
				'validate_callback' => function($param, $request, $key) {
					return is_string( $param );
				}
			),
		),
	));

	// NOTIFICATIONS ENDPOINTS ===============================
	register_rest_route( 'jmapp/v1', '/notifications', array(
		'methods' => 'GET',
		'callback' => 'jmapp_notifications_get_handler',
	) );
	register_rest_route( 'jmapp/v1', '/notifications/(?P<id>\d+)', array(
		'methods' => 'GET',
		'callback' => 'jmapp_notifications_get_handler',
	) );
	register_rest_route( 'jmapp/v1', '/notifications/topics', array(
		'methods' => 'GET',
		'callback' => 'jmapp_notifications_topics_get_handler',
	) );

	// SUBSITES ENDPOINTS ===============================
	register_rest_route( 'jmapp/v1', '/subsites', array(
		'methods' => 'GET',
		'callback' => 'jmapp_subsites_get_handler',
	) );

});

function jmapp_tvfeed_live_handler($request)
{
	// TODO: change this to a wordpress option
	// check for a live stream
	$live_stream_url = trim(file_get_contents('https://luke.lafayettecc.org/live/streamactive'));
	if (!empty($live_stream_url))
	{
		return [
			'id' => 'live',
			'channel_id' => 'live',
			'title' => 'Live Stream',
			'description' => 'Our live stream is now active.',
			'videoURL' => $live_stream_url,
			'videoURL_RTMP' => 'rtmp://jeffmikels.org/live/test',
			'series' => 'Live',
			'categories' => ['live'],
			'dateTime' => Date('Y-m-d'),
			'date' => Date('m/d/Y'),
			'imgURL' => 'https://lafayettecc.org/images/LCC-Logo-Gradient.jpg',
			'thumbURL' => 'https://lafayettecc.org/images/LCC-Logo-Gradient-768x432.jpg',
		];
	}
	return [];
}

function jmapp_tvfeed_handler($request)
{
	/*
		{
		  "id": "169313",
		  "title": "Beautiful Whale Tail Uvita Costa Rica",
		  "description": "Beautiful Whale Tail Uvita Costa Rica",
		  "duration": "86",
		  "thumbURL": "http://le2.cdn01.net/videos/0000169/0169313/thumbs/0169313__007f.jpg",
		  "imgURL": "http://le2.cdn01.net/videos/0000169/0169313/thumbs/0169313__007f.jpg",
		  "videoURL": "http://edge-vod-media.cdn01.net/encoded/0000169/0169313/video_1880k/T7J66Z106.mp4?source=firetv&channel_id=13454",
		  "categories": [
		    "Costa Rica Islands"
		  ],
		  "channel_id": "13454"
		},
	*/
	$page = $request['page'] ?? 1;
	$posts_per_page = $request['numseries'] ?? $request['posts_per_page'] ?? $request['count'] ?? 20;
	if (function_exists('sp_get_series_with_sermons'))
	{
		$results = [];
		$series = sp_get_series_with_sermons($page, $posts_per_page);
		foreach ($series as $s)
		{
			$series_name = $s->post_title;
			foreach ($s->children as $child)
			{
				$date = get_the_date('', $child);
				$item = [
					'id' => $child->ID,
					'channel_id' => $child->ID,
					'title' => $child->post_title,
					'description' => $date . "\r\n" . wp_strip_all_tags($child->post_content),
					'videoURL' => ($child->{'hq-video'} ?? $child->video)['url'],
					'series' => $series_name,
					'categories' => [$series_name],
					'dateTime' => $child->post_date,
					'date' => $date,
					'imgURL' => $s->imageUrl,
					'thumbURL' => ($s->images['medium'] ?? $s->images['thumbnail'] ?? [''])[0],
				];
				$results[] = $item;
			}
		}
		return $results;
	}
	else
	{
		return [];
	}
}

function jmapp_notifications_get_handler($request)
{
	$id = $request['id'];
	return jmapp_get_notifications($id,10);
}

function jmapp_notifications_topics_get_handler()
{
	return jmapp_notifications_topics();
}

function jmapp_subsites_get_handler()
{
	$s = jmapp_get_option('app_subsites');
	if (empty($s)) return [];
	return explode(',',$s);
}


function jmapp_user_firebasejwt_handler()
{
	
	$user = wp_get_current_user();
	if (!isset($user->ID) || $user->ID == 0)
		return ['error' => 'not logged in'];
	
	$uid = "wpauth-" . $user->ID;
	
	$stored_options = get_option('jmapp_options');
	$json_path = $stored_options['google_services_json_file'];
	$data = json_decode(file_get_contents($json_path));
	
	// generate a firebase valid JWT
	// assuming that jwt is already in a plugin and specified above
	$privateKey = $data->private_key;
	$clientEmail = $data->client_email;
	$now_seconds = time();
	$token = array(
		"iss" => $clientEmail,
		"sub" => $clientEmail,
		"aud" => "https://identitytoolkit.googleapis.com/google.identity.identitytoolkit.v1.IdentityToolkit",
		"iat" => $now_seconds,
		"exp" => $now_seconds + (60*60),
		"uid" => $uid,
	);

	$jwt = JWT::encode($token, $privateKey, 'RS256');
	
	return ['uid' => $uid, 'token' => $jwt];
}

function jmapp_scores_post_handler($request)
{
	// + submit score and day
	// 	@auth not required
	// 	POST wp-json/jmapp/v1/scores
	// 	( args: score, apikey, owner_id, owner_nickname, emoji )
	$game = $request['game'];
	if (empty($game)) $game = 'dash'; // backwards compatibility
	$score = $request['score'];
	$owner_id = $request['owner_id'];
	$owner_nickname = $request['owner_nickname'];
	$emoji = $request['emoji'];
	if (empty($emoji)) $emoji = '';
	$apikey = $request['apikey'];
	if ($apikey != JMAPP_SCORE_APIKEY) return ['error' => 'apikey mismatch'];
	
	return jmapp_add_game_score($game, $score, ['id'=>$owner_id, 'nickname'=>$owner_nickname], $emoji);
}

function jmapp_scores_get_handler($request)
{
	// + get scores
	// 	@auth not required
	// 	GET wp-json/jmapp/v1/scores
	return jmapp_get_game_scores();
}

function jmapp_fix_handler()
{
	return jmapp_get_prayer_walk();
}

function jmapp_prayer_walks_big_map_handler($request)
{
	$boundaries = $request['boundaries'];
	if (!empty($boundaries)) $url = jmapp_prayer_walks_big_map_url($boundaries);
	else $url = jmapp_prayer_walks_big_map_url();
	if ($request['raw'] == 1)
	{
		print($url);
		exit();
	}
	return ['success' => 'url retrieved', 'map_url' => $url];
}

function jmapp_prayer_walks_stats_handler($request)
{
	$user = wp_get_current_user();
	$recompute = FALSE;
	if (isset($request['recompute'])) $recompute = TRUE;
	if (isset($user->ID)) return jmapp_get_prayer_walk_stats($user->ID, $recompute);
	else return jmapp_get_prayer_walk_stats(NULL, $recompute);
}

function jmapp_prayer_walks_create_handler($request) {
	$user = wp_get_current_user();
	if (!isset($user->ID) || $user->ID == 0)
		return ['error' => 'not logged in'];
	
	// title, description
	if (!current_user_can(JMAPP_PW_CAP)) return ['error' => 'you don\'t have permission to add a prayer walk' ];
	$res = wp_insert_post([
		'post_type' => 'prayer_walk',
		'post_status' => 'private',
		'post_title' => $request['title'],
		'post_content' => $request['description'],
	]);
	if ($res === 0) return ['error' => 'failed to insert new prayer walk'];
	return ['success' => 'added new prayer walk', 'prayer_walk' => jmapp_get_prayer_walk($res)];
}
	
function jmapp_prayer_walks_get_handler($request) {
	$user = wp_get_current_user();
	if (!isset($user->ID) || $user->ID == 0)
		return ['error' => 'not logged in'];
	
	$id = $request['id'];
	return jmapp_get_prayer_walk($id, $user->ID);
}

function jmapp_prayer_walks_post_handler($request) {
	$user = wp_get_current_user();
	if (!isset($user->ID) || $user->ID == 0)
		return ['error' => 'prayer walk not changed, not logged in'];
	
	$id = $request['id'];
	if (!empty($id))
	{
		$post = jmapp_get_prayer_walk($id);
		if ($post == FALSE)
			return ['error' => 'no prayer walk with that id could be found'];

		if ((int)$post['author'] !== $user->ID)
			return ['error' => 'you are not the owner of this prayer walk'];
	}
	else
	{
		// wordpress uses id of 0 to indicate that it is a new item
		$id = 0;
	}
	
	$title = $request['title'];
	$description = $request['description'];
	$new_data = [];
	$new_data['ID'] = $id;
	if (!empty($title)) $new_data['post_title'] = $title;
	if (!empty($description)) $new_data['post_content'] = $description;
	
	if ($id === 0)
	{
		$new_data['post_type'] = 'prayer_walk';
		// $new_data['post_status'] = 'publish';
		$new_data['post_status'] = 'private';
		$response = wp_insert_post($new_data);
	}
	else
	{
		$response = wp_update_post($new_data);
	}
	
	if ($response === 0)
		return ['error' => 'prayer walk was not updated'];
	else
	{
		wp_schedule_single_event(time(), 'recompute_prayer_walk_stats');
		return ['success' => 'prayer walk was successfully updated', 'response' => $response, 'prayer_walk' => jmapp_get_prayer_walk($response)];
	}
	
}

function jmapp_prayer_walks_marker_handler($request) {
	// type should be one of 'automatic' or 'manual'
	// should contain location (comma separated lat,long), timestamp, note, type
	// location can also be an associative array with keys latitude and longitude
	// or should be an array of these items to merge with existing markers
	
	$user = wp_get_current_user();
	if (!isset($user->ID) || $user->ID == 0)
		return ['error' => 'prayer walk marker not added, not logged in'];
	
	// id is sent in the main request always
	$id = $request['id'];
	
	$pw = jmapp_get_prayer_walk($id);
	if ($pw == FALSE)
		return ['error' => 'no prayer walk with that id could be found'];

	if ((int)$pw['author'] !== $user->ID)
		return ['error' => 'you are not the owner of this prayer walk'];
	
	// $pw_markers = get_post_meta($id, 'markers', true);
	// if (empty($pw_markers)) $pw_markers = [];
	
	
	// convert posted markers to array if not already an array
	$posted_markers = [];
	
	if (empty($request['markers'])) $posted_markers[] = $request;
	else $posted_markers = $request['markers'];
	
	foreach ($posted_markers as $posted_marker)
	{
		$timestamp = empty($posted_marker['timestamp']) ? time() : $posted_marker['timestamp'];

		// if this timestamp is already recorded, skip it
		foreach ($pw_markers as $pwm)
		{
			if ($pwm['timestamp'] == $timestamp) continue;
		}

		$loc = $posted_marker['location'];
		if (is_array($loc))
		{
			$lat = $loc['latitude'];
			$long = $loc['longitude'];
		} else {
			$ldata = explode(',', $loc);
			$lat = $ldata[0];
			$long = $ldata[1];
		}
		$type = $posted_marker['type'];
		
		// optional fields
		$note = empty($posted_marker['note']) ? '' : $posted_marker['note'];
		
		$marker = [
			'timestamp' => $timestamp,
			'lat' => $lat,
			'long' => $long,
			'type' => $type,
			'note' => $note,
		];
		
		$pw['markers'][] = $marker;
	}

	// actually save new marker data
	delete_post_meta($id, 'markers');
	if (add_post_meta($id, 'markers', $pw['markers'], true)) {
		add_post_meta($id, 'should_snap', 1, true);
		
		// reload the data
		$pw = jmapp_get_prayer_walk($id);
		return ['success' => 'marker(s) added successfully', 'posted_markers' => $posted_markers, 'prayer_walk'=>$pw];
	}
	else
		return ['error' => 'failed to add marker(s) to prayer walk with id ' . $id, 'posted_markers' => $posted_markers];
}

function jmapp_user_handler($request) {
	/*
		create a user (don't use this method... use the ivanhoes endpoint):
			POST https://ivanhoes.jeffmikels.org/wp-json/wp/v2/users
				username, password, nickname, name (same as nickname), email
		test user account data
			username: test
			password: 13098751039857104987
			auth endpoint: https://ivanhoes.jeffmikels.org/wp-json/jwt-auth/v1/token/
			token: eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwczpcL1wvaXZhbmhvZXMuamVmZm1pa2Vscy5vcmciLCJpYXQiOjE1NTM5MTE5MDksIm5iZiI6MTU1MzkxMTkwOSwiZXhwIjoxNTU0NTE2NzA5LCJkYXRhIjp7InVzZXIiOnsiaWQiOiIyIn19fQ.XKU21VmLpzxmZAxKDPeJPX8n0fQAjN7cYUbdEP9DYog
			-H 'Authorization: Bearer {token}'

		jeff user
			token: eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwczpcL1wvaXZhbmhvZXMuamVmZm1pa2Vscy5vcmciLCJpYXQiOjE1NTM5MTQxMDMsIm5iZiI6MTU1MzkxNDEwMywiZXhwIjoxNTg1NDUwMTAzLCJkYXRhIjp7InVzZXIiOnsiaWQiOiIxIn19fQ.GII5QgpMf2NMQH-n4ZevuFyLxl6lwaU4Wk3nf10ArS0
	*/
	$user = wp_get_current_user();
	if (!isset($user->ID) || $user->ID == 0) return [];
	return jmapp_get_user($user->ID);
}

function jmapp_user_update_handler($request) {
	// is this user the currently logged in user?
	$user = wp_get_current_user();
	if (!isset($user->ID) || $user->ID == 0)
		return ['error' => 'user edit failed, not logged in'];
	
	if (!empty($request['user_id']) && $user->ID != $request['user_id'])
		return ['error' => 'user edit failed, logged in as a user ' . $user->ID . ' but attempted to edit user ' . $request['user_id'], 'data'=>$request];
	
	// merge new data with original data
	$userdata = [
		'ID' => $user->ID,
		'display_name' => jmapp_get($request['nickname'],$user->display_name),
		'user_nicename' => jmapp_get($request['nickname'],$user->user_nicename),
		'nickname' => jmapp_get($request['nickname'],$user->nickname),
		'first_name' => jmapp_get($request['first_name'],$user->first_name),
		'last_name' => jmapp_get($request['last_name'],$user->last_name),
		'user_email' => jmapp_get($request['email'],$user->user_email),
	];

	if (!empty($request['password'])) $userdata['user_pass'] = $request['password'];
	
	$user_id = wp_update_user($userdata); // wp_update_user will hash password if provided

	if ( is_wp_error($user_id)) {
		return ['error' => 'user edit error','details' => $user_id->errors, 'attempted'=>$userdata];
	}
	else {
		if (!empty($request['cell']))
			update_user_meta($user_id, 'cell_phone', $request['cell']);
		if (!empty($request['device']))
			jmapp_user_add_device($user_id, $request['device']);

		return ['success' => 'user edit successful', 'user_id'=>$user_id, 'user' => jmapp_get_user($user_id)];
	}
}

function jmapp_user_register_handler($request) {
	// if a user_id is included, this will perform an update,
	// but when doing an update, the password must be hashed first!
	// so use wp_update_user for that purpose instead.
	$userdata = [
		'user_login' => $request['username'],
		'user_pass' => $request['password'], // will be hashed as long as no user_id
		'display_name' => $request['nickname'],
		'user_nicename' => $request['nickname'],
		'nickname' => $request['nickname'],
		'user_email' => $request['email'],
		'role' => 'subscriber',
	];
	$user_id = wp_insert_user($userdata);
	if ( is_wp_error($user_id)) {
		$messages = [];
		return ['error' => 'registration error','details' => $user_id->errors];
	}
	else {
		$user = get_userdata($user_id);
		$user->add_role(JMAPP_USER_ROLE);
		return ['success' => 'registration successful', 'user_id'=>$user_id];
	}
}

function jmapp_user_reset_handler_1($request) {
	$email = $request['email'];
	$user = get_user_by('email', $email);
	if ($user === FALSE)
		return ['error' => 'user with that email could not be found'];
	
	// need to bypass google-captcha
	if (function_exists('gglcptch_add_actions')) {
		remove_action( 'lostpassword_form', 'gglcptch_login_display' );
		remove_action( 'allow_password_reset', 'gglcptch_lostpassword_check' );
	}
	$reset_key = get_password_reset_key( $user );

	$user_login = $user->user_login;
	/*
	$rp_link = '<a href="' . wp_login_url()."/resetpass/?key=$adt_rp_key&login=" . rawurlencode($user_login) . '">' . wp_login_url()."/resetpass/?key=$adt_rp_key&login=" . rawurlencode($user_login) . '</a>';
	*/


	$message = "Hi '".$user_login."',";
	$message .= "<p>Someone has requested a password reset for your church account using our Mobile App.";
	$message .= "<p>To complete the reset, copy the following verification code and paste it into the app.";
	$message .= "<p>USERNAME: <b>" . $user_login . "</b>";

	$message .= "<p>RESET CODE: <b>" . $reset_key . "</b>";

	$subject = "Church Mobile App Password Reset";
	$headers = array();
	
	add_filter( 'wp_mail_content_type', function( $content_type ) {return 'text/html';});
	$headers[] = 'From: Church Mobile App <'.get_bloginfo('admin_email').'>'."\r\n";
	$result = wp_mail( $email, $subject, $message, $headers);

	// Reset content-type to avoid conflicts -- http://core.trac.wordpress.org/ticket/23578
	remove_filter( 'wp_mail_content_type', 'set_html_content_type' );

	return ['success' => 'reset email sent', 'data'=>$result];
}

function jmapp_user_reset_handler_2($request) {
	// return [$request['key'], $request['username']];
	
	// need to bypass google-captcha
	if (function_exists('gglcptch_add_actions')) {
		remove_action( 'lostpassword_form', 'gglcptch_login_display' );
		remove_action( 'allow_password_reset', 'gglcptch_lostpassword_check' );
	}
	
	$user = check_password_reset_key($request['key'], $request['username']);
	if ($user instanceof WP_Error) return ['error' => "That code doesn't go with that username.", 'request'=>$request];
	if (empty($request['password']))
		return ['success' => 'reset key is correct'];

	$new_pass = $request['password'];
	reset_password($user, $new_pass);
	return ['success' => 'password has been changed'];
}

function jmapp_user_device_handler($request) {
	$user = wp_get_current_user();
	if (!isset($user->ID) || $user->ID == 0) {
		// should we record this device token anyway?
		// $res = jmapp_user_add_device(0, $request['device']);
		return ['error' => 'user edit failed, not logged in'];
	}
	
	$res = jmapp_user_add_device($user->ID, $request['device']);
	if (FALSE === $res){
		return ['error' => 'user edit failed, adding device token failed'];
	}
	else if (TRUE === $res) {
		return ['error' => 'device token already exists'];
	}
	else
		return ['success' => 'user edit success, device token added'];
}


/* OLD STYLE BASED ON SIMPLY JSON */
function jmapp_simply_json($wp) {
	global $post, $query_string, $wp_query;
	$post_id = $post->ID;

	if(isset($_REQUEST['json'])) {

		header('Content-Type: application/json');
		header("Access-Control-Allow-Origin: *");
		header("Access-Control-Allow-Credentials: false");
		header('Cache-Control: public');
		header('Cache-Control: max-age=' . 60*60);

		// making it more compatible with the output of JSON-API
		$output['status'] = 'ok';
		$output['blog_data'] = array();
		$output['blog_data']['blog_title'] = get_bloginfo('name');
		$output['blog_data']['blog_desc'] = get_bloginfo('description');
		$output['blog_data']['blog_url'] = get_bloginfo('url');
		$output['blog_data']['blog_rss'] = get_bloginfo('rss2_url');
		$output['posts'] = Array();

		$posts_returned = 0;

		if (isset($_REQUEST['category'])) {
			$category = trim(strip_tags($_REQUEST['category']));
		}


		if( have_posts() ) {
			$i = 0;
			while( have_posts() ) {
				the_post();
				if (!isset($category) || in_category($category)) {
					$permalink_array = explode('/', trim(get_permalink(),'/'));
					$output['posts'][$i] = Array();
					$output['posts'][$i]['id'] = get_the_ID();
					$output['posts'][$i]['guid'] = $post->guid;
					$output['posts'][$i]['slug'] = end($permalink_array);
					$output['posts'][$i]['post_type'] = $post->post_type;
					$output['posts'][$i]['url'] = get_permalink();
					// the wordpress function get_the_title does some formatting to the title including converting utf-8 to htmlentities. :-P
					// $output['posts'][$i]['title'] = get_the_title();
					$output['posts'][$i]['title'] = $post->post_title;
					$output['posts'][$i]['raw'] = $post->post_content;
					$output['posts'][$i]['content'] = apply_filters('the_content', $output['posts'][$i]['raw']);
					$output['posts'][$i]['alt_excerpt'] = wp_trim_words(get_the_content(), 55);
					$output['posts'][$i]['excerpt'] = jmapp_strip_tags_content_simply_json(get_the_excerpt());
					if (empty($output['posts'][$i]['excerpt']))
						$output['posts'][$i]['excerpt'] = $output['posts'][$i]['alt_excerpt'];

					// handle the author like json-api does
					$output['posts'][$i]['author'] = array('id' => $post->post_author);
					foreach (array('description','first_name','last_name','nickname','slug','url') as $key)
					{
						$output['posts'][$i]['author'][$key] = get_the_author_meta($key);
					}
					$output['posts'][$i]['author']['name'] = get_the_author_meta('display_name');
					
					if (!empty($_REQUEST['date_format']))
						$output['posts'][$i]['date'] = get_the_time($_REQUEST['date_format']);
					else
						$output['posts'][$i]['date'] = get_the_date();
					
					$output['posts'][$i]['niceDate'] = get_the_date();
					$output['posts'][$i]['uDate'] = get_the_time("U");
					$output['posts'][$i]['isoDate'] = get_the_time('c');
					$output['posts'][$i]['pubDate'] = get_the_time("D, d M Y H:i:s O");

					// get featured images
					$featured_id = get_post_thumbnail_id($post->ID);
					$images = array();
					foreach (['full','large','medium','thumbnail'] as $size)
					{
						$details = wp_get_attachment_image_src($featured_id, $size);
						$images[$size] = empty($details) ? array() : $details;
					}
					
					// legacy support for old apps
					$output['posts'][$i]['thumbnail'] = empty($images['thumbnail']) ? '' : $images['thumbnail'][0];
					$output['posts'][$i]['image']     = empty($images['full']) ? array() : $images['full'];
					
					// add the images array
					$output['posts'][$i]['images'] = $images;

					// get all media
					$output['posts'][$i]['media'] = get_attached_media( '', $post->ID );

					// json-api aware apps, expect the media items to show up in the attachments value
					$output['posts'][$i]['attachments'] = array();
					foreach ($output['posts'][$i]['media'] as $id => $item)
					{
						// get this attachment into a format understood by json-api apps
						// die(json_encode($item));
						$attachment = array(
							'id' => $id,
							'title' => $item->post_title,
							'alt' => get_post_meta( $id, '_wp_attachment_image_alt', true ),
							'caption' => $item->post_excerpt,
							'description' => $item->post_content,
							'url' => $item->guid,
							'mime_type' => $item->post_mime_type,
							'parent' => $item->post_parent,
							'slug' => $item->post_name
						);
						$attachment['images'] = array();
						foreach (array('full','medium','thumbnail') as $size)
						{
							$image_data = wp_get_attachment_image_src($id, $size);
							$attachment['images'][$size] = array(
								'url'=>$image_data[0],
								'width'=>$image_data[1],
								'height' => $image_data[2],
							);
						}
						$output['posts'][$i]['attachments'][] = $attachment;
					}

					// get enclosures and downloads
					$output['posts'][$i]['enclosures'] = array();
					$enclosures = get_post_custom_values('enclosure');
					if (is_array($enclosures))
					{
						foreach ($enclosures as $enclosure)
						{
							$enc = array();
							// replace \r\n with \n
							$enclosure = str_replace("\r\n", "\n", $enclosure);
							$encdata = explode("\n", $enclosure);
							$enc['url'] = $encdata[0];
							$enc['size'] = $encdata[1];
							$enc['type'] = $encdata[2];
							$enc['details'] = unserialize($encdata[3]);
							$format = $enc['details']['format'];
							$output['posts'][$i]['enclosures'][$format] = $enc;
						}
					}

					$downloads = get_post_custom_values('download');
					if (is_array($downloads))
					{
						foreach ($downloads as $download)
						{
							$enc = array();
							// replace \r\n with \n
							$enclosure = str_replace("\r\n", "\n", $download);
							$encdata = explode("\n", $enclosure);
							$url = $encdata[0];
							$enc['url'] = $encdata[0];
							$label = $encdata[1];
							$extension = substr($url, -3);
							if ($extension == 'pdf') $enc['type'] = 'application/pdf';
							elseif ($extension == 'ogg') $enc['type'] = 'audio/ogg';
							elseif ($extension == 'jpg') $enc['type'] = 'image/jpeg';
							elseif ($extension == 'png') $enc['type'] = 'image/png';
							else $enc['type'] = 'unknown/' . $extension;
							$output['posts'][$i]['enclosures'][$label] = $enc;
						}
					}

					// output the raw metadata
					$output['posts'][$i]['meta'] = get_post_custom();

					// check for a 'meta' item named poster in case there is no other image set
					// if (empty($output['posts'][$i]['image']) && ! empty($output['posts'][$i]['meta']['poster']))
					// 	$output['posts'][$i]['image'] = array($output['posts'][$i]['meta']['poster'][0]);

					// output the categories
					foreach(get_the_category() as $cat) {
						$output['posts'][$i]['categories'][] = $cat->cat_name;
					}
					$i++;
				}
				// handled by query variables now
				// if (isset($_REQUEST['count']) && $_REQUEST['count'] == $i) {
				// 	break;
				// }
			}
			$posts_returned = $i;
		}
		$output['count'] = $wp_query->post_count;
		$output['count_total'] = $wp_query->found_posts;
		$output['pages'] = $wp_query->max_num_pages;
		$output['query_string'] = $query_string;
		$json = json_encode($output);

		// // cache the output to file
		// $cachedir = WP_CONTENT_DIR . '/cache/page_enhanced/' . $_SERVER['SERVER_NAME'];
		// $filename = $cachedir . $_SERVER['REQUEST_URI'] . ".json";
		//
		// $dirname = dirname($filename);
		//
		// // recursively create directory and then save the file.
		// if (! file_exists($dirname)) mkdir($dirname, 0755, true);
		// if (file_exists($dirname))
		// {
		// 	//file_put_contents($filename, $json);
		// }
		
		if (isset($_REQUEST['debug']))
		{
			print_r($_REQUEST . "\n");
			print_r($query_string . "\n");
			print_r($wp_query . "\n");
			print_r($output . "\n");
			die();
		}
		
		die ( $json );
	} else {
		return;
	}
}


/**
 * Hilfsfunktion, die alle HTML Tags und deren Inhalt aus einem String entfernt
 *
 * @param (string) $text  Zu durchsuchender String
 * @param (array) $tags  HTML Tags, die ausgelassen werden sollen
 */
function jmapp_strip_tags_content_simply_json($text, $tags = '') {
	preg_match_all('/<(.+?)[\s]*\/?[\s]*>/si', trim($tags), $tags);
	$tags = array_unique($tags[1]);
	if(is_array($tags) and count($tags) > 0) {
		return preg_replace('@<(?!(?:'. implode('|', $tags) .')\b)(\w+)\b.*?>.*?</\1>@si', '', $text);
} else {
return preg_replace('@<(\w+)\b.*?>.*?</\1>@si', '', $text);
	}
	return $text;
	}


	// query is passed by reference
	function jmapp_simply_json_custom_query($query)
	{
	// if (is_admin() || ! $query->is_main_query()) return;

	if (isset($_REQUEST['json'] ))
	{
	// eliminate password protected posts
	$query->set('has_password', FALSE);

	// this is the default, but if a user is logged in
	// private posts will be included as well
	// setting it manually here makes private posts never show up
	// $query->set('post_status', 'publish');


	// posts to retrieve by default
	$count = 20;
	if (isset($_REQUEST['posts_per_page'])) $count = $_REQUEST['posts_per_page'];
	if (isset($_REQUEST['count'])) $count = $_REQUEST['count'];
	$query->set('posts_per_page',$count);

	// post offset
	if (isset($_REQUEST['offset'])) $query->set('offset',$_REQUEST['offset']);
	if (isset($_REQUEST['page'])) $query->set('offset',((int)$_REQUEST['page'] - 1) * $count);

	// post types
	$query->query['post_type'] = 'any';
	if (isset($_REQUEST['post_type'])) $query->set('post_type',$_REQUEST['post_type']);
	if (isset($_REQUEST['orderby'])) $query->set('orderby',$_REQUEST['orderby']);
	if (isset($_REQUEST['order'])) $query->set('order',$_REQUEST['order']);

	// multiple post ids
	if (isset($_REQUEST['ids']))
	{
	$ids = $_REQUEST['ids'];
	if (!is_array($ids)) $ids = explode(',', $ids);
	if (!empty($ids))
	{
	$query->set('page_id',NULL);
	$query->set('post_type', 'any');
	$query->set('post__in', $ids);
	$query->set('ignore_sticky_posts',True);
	}
	}

	// Wordpress Picks up the 'p' query directly (for individual posts), but we need to
	// reset the post_type to override any accidental post_type settings above
	if (isset($_REQUEST['p'])) $query->set('post_type','any');

	// query by simple search
	// To handle searching, simply use the 's' query variable directly.

	// query by meta values
	if (isset($_REQUEST['meta_query'])) $query->set('meta_query',$_REQUEST['meta_query']);

	// do debug
	if (isset($_REQUEST['debug']))
	{
	print_r($query);
	print_r($_REQUEST);
	die();
	}
	}
	// not needed... the variable is passed by reference
	// return $query;
	}
	// some plugins for some reason hijack the number of posts
	// we use a high priority to make sure our request gets processed at the end
	add_action('pre_get_posts', 'jmapp_simply_json_custom_query', 9999);


	// this runs before the other query filter, but it is much more sensitive
	function jmapp_simply_json_check_post_type($request)
	{
	// post categories should be specified on command line with category_name=whatever
	// however, for backwards compatibility with older apps, we leave this code here
	if (isset($_REQUEST['category']))
	{
	$request['category_name'] = $_REQUEST['category'];
	$request['post_type'] = 'any';
	}
	return $request;
	}
	add_action('request', 'jmapp_simply_json_check_post_type');

	/**
	* Wordpress Actions
	*/
	// add_action('template_redirect' , 'template_redirect_simply_json', 0);
	add_action('wp' , 'jmapp_simply_json', 0);