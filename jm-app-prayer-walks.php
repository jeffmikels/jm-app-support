<?php
add_action( 'recompute_prayer_walk_stats', 'jmapp_compute_prayer_walk_stats');

function jmapp_add_prayer_walk_capabilities($install=TRUE)
{
	global $wp_roles;

	$funcname = 'add_cap';
	if (!$install) $funcname = 'remove_cap';
	
	// According to http://justintadlock.com/archives/2010/07/10/meta-capabilities-for-custom-post-types
	// we should never map edit_type, read_type, or delete_type since these are meta capabilities
	// if(!$install) $wp_roles->$funcname('administrator', 'read_prayer_walk');
	// if(!$install) $wp_roles->$funcname('administrator', 'edit_prayer_walk');
	// if(!$install) $wp_roles->$funcname('administrator', 'delete_prayer_walk');
	
	// administrators get everything
	$wp_roles->$funcname('administrator', 'publish_prayer_walks');
	$wp_roles->$funcname('administrator', 'edit_prayer_walks');
	$wp_roles->$funcname('administrator', 'edit_published_prayer_walks');
	$wp_roles->$funcname('administrator', 'edit_private_prayer_walks');
	$wp_roles->$funcname('administrator', 'edit_others_prayer_walks');
	$wp_roles->$funcname('administrator', 'delete_prayer_walks');
	$wp_roles->$funcname('administrator', 'delete_published_prayer_walks');
	$wp_roles->$funcname('administrator', 'delete_private_prayer_walks');
	$wp_roles->$funcname('administrator', 'delete_others_prayer_walks');
	
	// jmapp users get some
	$wp_roles->$funcname(JMAPP_USER_ROLE, 'edit_prayer_walks');
	$wp_roles->$funcname(JMAPP_USER_ROLE, 'publish_prayer_walks');
	$wp_roles->$funcname(JMAPP_USER_ROLE, 'edit_published_prayer_walks');
	$wp_roles->$funcname(JMAPP_USER_ROLE, 'edit_private_prayer_walks');
	$wp_roles->$funcname(JMAPP_USER_ROLE, 'delete_prayer_walks');
	$wp_roles->$funcname(JMAPP_USER_ROLE, 'delete_published_prayer_walks');
	$wp_roles->$funcname(JMAPP_USER_ROLE, 'delete_private_prayer_walks');

	// make sure jmapp users never get some capabilities
	// $wp_roles->remove_cap(JMAPP_USER_ROLE, 'read_prayer_walk');
	$wp_roles->remove_cap(JMAPP_USER_ROLE, 'edit_others_prayer_walks');
	$wp_roles->remove_cap(JMAPP_USER_ROLE, 'delete_others_prayer_walks');
}

// will be called from jm-app-custom-types.php
function jmapp_register_prayer_walks()
{
	register_post_type( 'prayer_walk', array
	(
		'labels' => array
		(
			'name' => __( 'Prayer Walks' ),
			'singular_name' => __( 'Prayer Walk' ),
			'add_new' => __( 'Add New Prayer Walk' ),
			'add_new_item' => __( 'Add New Prayer Walk' ),
			'edit_item' => __( 'Edit Prayer Walk' ),
			'new_item' => __( 'New Prayer Walk' ),
			'view_item' => __( 'View Prayer Walk' ),
			'search_items' => __( 'Search Prayer Walks' ),
			'not_found' => __( 'No Prayer Walks found' ),
			'not_found_in_trash' => __( 'No Prayer Walks found in Trash' ),
		),
		'description' => __( 'A prayer walk is a user-specific list of GPS coordinates recording the path they have taken on their prayer walk.', 'en'),
		'public' => false,
		'has_archive' => false,
		'rewrite' => array('slug' => 'prayer_walk'),
		'taxonomies' => array(),
		'publicly_queryable' => true,
		'exclude_from_search' => false,
		'menu_position' => 20,
		'menu_icon' => 'dashicons-location',
		'show_ui' => true,
		'show_in_rest' => true,
		'rest_base' => 'prayer_walks',
		'supports' => array('title', 'author', 'thumbnail', 'custom-fields'),
		'map_meta_cap' => true,
		'capability_type' => ['prayer_walk', 'prayer_walks'],
		// 'capabilities' => array(
		// 	'edit_post' => JMAPP_PW_CAP,
		// 	'edit_posts' => 'edit_posts',
		// 	'edit_others_posts' => 'edit_others_posts',
		// 	'publish_posts' => 'publish_posts',
		// 	'read_post' => 'read_post',
		// 	'read_private_posts' => 'read_private_posts',
		// 	'delete_post' => 'delete_post'
		// ),
	));
}

// add_filter( 'map_meta_cap', 'jmapp_prayer_walk_meta_cap', 10, 4 );
function jmapp_prayer_walk_meta_cap( $caps, $cap, $user_id, $args )
{
	// the singular capabilities should be computed on a per item basis
	
	/* If editing, deleting, or reading a movie, get the post and post type object. */
	if ( 'edit_prayer_walk' == $cap || 'delete_prayer_walk' == $cap || 'read_prayer_walk' == $cap ) {
		$post = get_post( $args[0] );
		$post_type = get_post_type_object( $post->post_type );

		/* Set an empty array for the caps. */
		$caps = array();
	}

	if ( 'edit_prayer_walk' == $cap ) {
		if ( $user_id == $post->post_author )
			$caps[] = $post_type->cap->edit_prayer_walks;
		else
			$caps[] = $post_type->cap->edit_others_prayer_walks;
	}

	elseif ( 'delete_prayer_walk' == $cap ) {
		if ( $user_id == $post->post_author )
			$caps[] = $post_type->cap->delete_prayer_walks;
		else
			$caps[] = $post_type->cap->delete_others_prayer_walks;
	}

	elseif ( 'read_prayer_walk' == $cap ) {
		// we disable reading
		
		// if ( 'private' != $post->post_status )
		// 	$caps[] = 'read';
		// elseif ( $user_id == $post->post_author )
		// 	$caps[] = 'read';
		// else
		// 	$caps[] = $post_type->cap->read_private_posts;
	}

	/* Return the capabilities required by the user. */
	return $caps;
}

add_action('admin_init','jmapp_prayer_walk_meta_init');
function jmapp_prayer_walk_meta_init() {
	add_meta_box( 'jmapp_prayer_walk_metabox', 'Prayer Walk Details', 'jmapp_prayer_walk_metabox', 'prayer_walk', 'normal','high');
	
	// add a callback function to save any data a user enters in
	add_action('save_post','jmapp_prayer_walk_meta_save',999);
}
function jmapp_prayer_walk_metabox()
{
	// prayer walks contain title, description (content), author, post_thumbnail from default fields
	// prayer walks have custom fields: marker
	// each marker holds: timestamp, lat, long, note, type (automatic or manual)
	global $post;
	$pw = jmapp_sanitize_prayer_walk($post);
	
	// print_r($pw);
	// print_r($markers);
	// fix marker times for when the app sent in milliseconds!
	// foreach ($markers as $key=>$marker)
	// {
	// 	if ($marker['timestamp'] > 1569423501) {
	// 		$markers[$key]['timestamp'] = $markers[$key]['timestamp'] / 1000;
	// 	}
	// }
	// update_post_meta($post->ID, 'marker', $markers);
	
	// meta HTML code
	echo '<style>.form_group {margin:20px 0;}.jmapp-metabox th,.jmapp-metabox td {text-align:left;}.jmapp-metabox label{font-weight:bold;font-size:1.1em;}</style>';
	
	echo '<div class="jmapp-metabox">';
	
	echo '<div class="form_group">';
	echo '<label for="description">Description</label><br />';
	echo '<input type="text" style="width:100%;" name="description" id="description" value="' . $pw['description'] . '" /><br />';
	echo '<small>Enter a description for this prayer walk.</small>';
	echo '</div>';
	

	echo '<style>.jmapp-prayer-walk-map {margin-top:20px;}</style>';
	echo '<div class="jmapp-prayer-walk-map"><img style="width:100%;height:auto;" src="'.$pw['map_url'].'" /><br /><small>Prayer walk paths are red when they are new and turn blue as they age.</small></div>';
	

	echo '<h3>Markers</h3>';
	echo '<p><small>Markers can only be added through the JSON API.</small></p>';
	echo date_timezone_get();
	echo '<table style="width:100%;">';
	echo '<tr><th>delete</th><th>stop</th><th>time</th><th>location</th><th>note</th><th>type</th></tr>';
	
	$gmt_offset = get_option( 'gmt_offset' );
	foreach ($pw['markers'] as $key=>$marker)
	{
		$timestamp = $marker['timestamp'] + $gmt_offset * 60 * 60;
		$time = date('Y-m-d h:i:s', $timestamp);
		echo '<tr>';
		echo '<td><input type="checkbox" name="deletes[]" value="'.$key.'" /></td>';
		echo '<td>' . $key . '</td>';
		echo '<td>' . $time . '</td>';
		echo '<td>' . $marker['lat'] . ',' . $marker['long'] . '</td>';
		echo '<td>' . $marker['note'] . '</td>';
		echo '<td>' . $marker['type'] . '</td>';
		echo '</tr>';
		// echo '<h4>Marker ' . $key . '</h4>';
		// echo '<div class="form_group">';
		// echo '<label for="marker[][timestamp]">Timestamp</label><br />';
		// echo '<input type="text" style="width:100%;" name="marker[][timestamp]" id="marker-'.$key.'-timestamp" value="' . $marker['timestamp'] . '" /><br />';
		// echo '<small>UTC timestamp of this marker.</small>';
		// echo '</div>';
		//
		// echo '<div class="form_group">';
		// echo '<label for="marker[][lat]">Latitude</label><br />';
		// echo '<input type="text" style="width:100%;" name="marker[][lat]" id="marker-'.$key.'-lat" value="' . $marker['lat'] . '" /><br />';
		// echo '<small>Latitude of this marker.</small>';
		// echo '</div>';
		//
		// echo '<div class="form_group">';
		// echo '<label for="marker[][long]">Longitude</label><br />';
		// echo '<input type="text" style="width:100%;" name="marker[][long]" id="marker-'.$key.'-long" value="' . $marker['long'] . '" /><br />';
		// echo '<small>Longitude of this marker.</small>';
		// echo '</div>';
		//
		// echo '<div class="form_group">';
		// echo '<label for="marker[][note]">Note</label><br />';
		// echo '<input type="text" style="width:100%;" name="marker[][note]" id="marker-'.$key.'-note" value="' . $marker['note'] . '" /><br />';
		// echo '<small>Note of this marker.</small>';
		// echo '</div>';
		//
		// echo '<div class="form_group">';
		// echo '<label for="marker[][type]">Type</label><br />';
		// echo '<select style="width:100%;" name="marker[][type]" id="marker-'.$key.'-type"/>';
		// echo '<option ' . ($marker['type'] == 'manual') ? 'selected="selected" ' : '' . 'value="manual">Manual</option>';
		// echo '<option ' . ($marker['type'] == 'automatic') ? 'selected="selected" ' : '' . 'value="automatic">Automatic</option>';
		// echo '</select>';
		// echo '<small>Type of this marker.</small>';
		// echo '</div>';
	}
	echo '</table>';
	echo '<input type="hidden" name="jmapp_prayer_walk_meta_noncename" value="' . wp_create_nonce(__FILE__) . '" />';
	// echo '<textarea>';
	// print_r($pw['snapped_markers']);
	// echo '</textarea>';
	echo '</div><!-- jmapp-metabox -->';
}

function jmapp_prayer_walk_meta_save($post_id)
{
	// save the description field into the content field.

	// Bail if we're doing an auto save
	if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if( wp_is_post_revision($post_id) ) return;
	
	// authentication checks
	// make sure data came from our meta box
	if (! isset($_POST['jmapp_prayer_walk_meta_noncename']) || !wp_verify_nonce($_POST['jmapp_prayer_walk_meta_noncename'],__FILE__)) return $post_id;

	// check user permissions
	if (!current_user_can(JMAPP_PW_CAP, $post_id) && !current_user_can('edit_posts', $post_id)) return $post_id;
	
	// only pay attention to posts generated by this plugin
	if ($_POST['post_type'] != 'prayer_walk') return $post_id;
	
	// if we are here, save the data.

	// first, save the description
	// unhook this function so it doesn't loop infinitely
	remove_action('save_post', 'jmapp_prayer_walk_meta_save',999);

	// update the post, which calls save_post again
	wp_update_post([
		'ID' => $post_id,
		'post_content' => $_POST['description']
	]);
	
	// update the post meta by deleting selected markers
	$markers = get_post_meta($post_id, 'markers', true);
	if (array_key_exists('deletes', $_POST))
	{
		foreach ($_POST['deletes'] as $key)
		{
			// if (!empty($markers[$key])) delete_post_meta($post_id,'marker',$markers[$key]);
			if (!empty($markers[$key])) unset($markers[$key]);
		}
		// update the post meta but re-index the values
		update_post_meta($post_id, 'markers', array_values($markers));
	}
	// re-hook this function
	add_action('save_post', 'jmapp_prayer_walk_meta_save',999);
	wp_schedule_single_event(time(), 'recompute_prayer_walk_stats');
}

function jmapp_prayer_walk_snap_to_roads_if_needed($pw)
{
	$id = $pw['ID'];
	$markers = get_post_meta($id,'markers',true);
	$snapped_markers = get_post_meta($id, 'snapped_markers',true);
	$should_snap = get_post_meta($id,'should_snap',true);
	// $should_snap = true;
	if (empty($markers)) return [];
	if (!empty($snapped_markers) && !$should_snap == 1) return $snapped_markers;
	
	delete_post_meta($id, 'should_snap');
	add_post_meta($id, 'should_snap', 0, true);
	
	$jmapp_options = get_option('jmapp_options');
	if (!empty($jmapp_options['google_services_api_key'])) $gkey = $jmapp_options['google_services_api_key'];
	if (empty($gkey)) return [];
	
	
	// google only allows a maximum of 100 data points per snap request
	// so we by default select every (modulo)th markers, while including all the ones marked 'manual'
	$markers = array_values($markers);
	$thinned_markers = $markers;
	$select_modulo = 1;
	while(count($thinned_markers) >= 100)
	{
		$select_modulo += 1;
		$thinned_markers = [];
		foreach($markers as $key=>$marker)
		{
			if (($key % $select_modulo) == 0 || $marker['type'] == 'manual') $thinned_markers[] = $marker;
		}
	}
	
	$path_pairs = [];
	foreach($thinned_markers as $key=>$marker)
	{
		$path_pairs[] = implode(',', [$marker['lat'], $marker['long']]);
	}
	
	$path_string = implode('|', $path_pairs);
	$snapped_url = 'https://roads.googleapis.com/v1/snapToRoads?interpolate=true&path=' . $path_string . '&key=' . $gkey;
	$res = file_get_contents($snapped_url);
	if (empty($res)) return [];
	
	$data = json_decode($res, TRUE);
	if (empty($data['snappedPoints'])) return [];
	
	$snapped_markers = [];
	foreach ($data['snappedPoints'] as $point)
	{
		$snap = ['type'=>'snapped'];
		if (array_key_exists('originalIndex', $point))
		{
			$snap = $thinned_markers[$point['originalIndex']];
		}
		$snap['lat'] = (float)$point['location']['latitude'];
		$snap['long'] = (float)$point['location']['longitude'];
		$snapped_markers[] = $snap;
	}
	
	delete_post_meta($id, 'snapped_markers');
	add_post_meta($id, 'snapped_markers', $snapped_markers, true);
	return $snapped_markers;
}

// takes a sanitized prayer walk and returns a google map style path
function jmapp_prayer_walk_path($pw, $force_polyline=FALSE)
{
	// google does not recommend using polylines for paths that are snapped to roads
	$use_polyline = $force_polyline;
	$markers = $pw['snapped_markers'];
	if (empty($markers))
	{
		$markers = $pw['markers'];
		$use_polyline = TRUE;
	}
	
	// prepare the location pairs from submitted markers
	$path_string = '';
	$path_pairs = [];
	$path_points = [];
	$map_markers = [];
	$oldest = time();
	
	// reindex the markers first
	$markers = array_values($markers);
	
	if (count($markers) > 0)
	{
		$manual_count = 0;
		for ($key = 0; $key < count($markers); $key++)
		{
			$marker = $markers[$key];
			$point = [ $marker['lat'], $marker['long'] ];
			$path_points[] = ['y' => $point[0], 'x' => $point[1]];

			$loc = implode(',', $point);
			$path_pairs[] = $loc;
		
			// add a marker at the beginning of the prayer walk and at the end
			// $marker_string = 'color:0x0000ff|label:'.$key. '|' . $loc;
			// if ($marker['type'] != 'automatic' || $key == 0 || $key == count($markers) - 1) $map_markers[] = $marker_string;
		
			// add markers only for manual markers
			if ($marker['type'] == 'manual')
			{
				$marker_string = 'color:0x0000ff|label:'.$manual_count. '|' . $loc;
				$map_markers[] = $marker_string;
				$manual_count += 1;
			}
		
			// snapped markers don't have timestamp information
			if ($marker['type'] != 'snapped')
			{
				$mtime = (int)$marker['timestamp'];
				if ($mtime < $oldest) $oldest = $mtime;
			}
		}
	
		// generate color based on the age of the markers
		// color of today paths should be all red
		// color of paths older than 365 days should be all blue
		$agepct = (time() - $oldest) / (60*60*24*365);
		if ($agepct > 1) $agepct = 1;
		$rval = (int)(255 * (1 - $agepct));
		$bval = (int)(255 * $agepct);
		$color = sprintf("0x%02x%02x%02x", $rval, 0, $bval);
	
		// assemble the path string
		$path_style = 'color:' . $color;
		$path_string .= $path_style . '|';
		if ($use_polyline) $path_string .= 'enc:' . gPolyline($path_points);
		else $path_string .= implode('|', $path_pairs);
	}

	$retval = [
		'path_string'=> $path_string,
		'path_pairs'=> $path_pairs,
		'path_points'=> $path_points,
		'map_markers'=> $map_markers,
		'oldest'=> $oldest,
		'color'=> $color,
	];
	// print_r($retval['path_string'] . "\n");
	// print_r($pw);
	// print_r($retval);
	return $retval;
}

// takes a sanitized prayer walk object as argument;
function jmapp_prayer_walk_map_url($pw, $region_only = FALSE)
{
	// google maps static api documentation
	// https://developers.google.com/maps/documentation/maps-static/dev-guide
	
	// google maps snap to roads documentation
	// https://developers.google.com/maps/documentation/roads/snap
	
	// get the api key
	$jmapp_options = get_option('jmapp_options');
	if (!empty($jmapp_options['google_services_api_key'])) $gkey = $jmapp_options['google_services_api_key'];
	if (empty($gkey)) return '';
	// $jeffs_gkey = 'AIzaSyDnyqIbsnbTAwwcq9Y9BTslCrHmqCrOklA';
	
	if ($region_only)
	{
		$map_url = 'https://maps.googleapis.com/maps/api/staticmap?size=640x400&scale=2&path=' . urlencode($path_string) .'&key='.$gkey;
	}
	
	$path_data = jmapp_prayer_walk_path($pw);
	$path_string = $path_data['path_string'];
	$map_markers = $path_data['map_markers'];
	
	$map_url = 'https://maps.googleapis.com/maps/api/staticmap?size=640x400&scale=2&path=' . urlencode($path_string) .'&key='.$gkey;
	
	// append the marker pins to each url
	foreach ($map_markers as $pin)
	{
		$map_url .= '&markers=' . $pin;
	}
	
	return $map_url;
}

function jmapp_prayer_walks_big_map_url($boundaries=NULL)
{
	/* the boundaries array looks like this
	
	[topleftlat,topleftlng,bottomrightlat,bottomrightlng]
	
	or just a string
	
	topleftlat,topleftlng,bottomrightlat,bottomrightlng
	
	*/

	// get the api key
	$jmapp_options = get_option('jmapp_options');
	if (!empty($jmapp_options['google_services_api_key'])) $gkey = $jmapp_options['google_services_api_key'];
	if (empty($gkey)) return '';
	
	// get all the prayer walks
	$pws = jmapp_get_prayer_walk();
	
	$path_strings = [];
	$visible = '';
	foreach ($pws as $pw)
	{
		// check to see that the prayer walk is within the boundaries
		$allowed = true;
		if ($boundaries != NULL && !empty($boundaries))
		{
			if (!is_array($boundaries))
			{
				$boundaries = explode(',',$boundaries);
			}
			$visible = implode(',', [$boundaries[0], $boundaries[1]]) . '|' . implode(',', [$boundaries[2], $boundaries[3]]);
			foreach ($pw['markers'] as $marker)
			{
				$toofarnorth = $marker['lat']  > $boundaries[0];
				$toofarsouth = $marker['lat']  < $boundaries[2];
				$toofarwest  = $marker['long'] < $boundaries[1];
				$toofareast  = $marker['long'] > $boundaries[3];
				if ($toofarnorth || $toofarsouth || $toofarwest || $toofareast)
				{
					$allowed = false;
					// print_r($marker);
					// print('outside: ' . $boundaries);
				}
			}
		}
		if (!$allowed) continue;
		
		// polylines take up less request data
		$force_polyline = TRUE;
		$path_data = jmapp_prayer_walk_path($pw, $force_polyline);
		if (!empty($path_data['path_string'])) $path_strings[] = $path_data['path_string'];
	}

	// construct massive map
	if (empty($visible))
		$map_url = 'https://maps.googleapis.com/maps/api/staticmap?size=640x400&scale=2';
	else
		$map_url = 'https://maps.googleapis.com/maps/api/staticmap?size=640x400&scale=2&visible=' . $visible;
	foreach ($path_strings as $path_string)
	{
		$map_url .= '&path=' . urlencode($path_string);
	}
	$map_url .= '&key='.$gkey;
	return $map_url;
}

function jmapp_get_prayer_walk($post_id=NULL, $user_id=NULL, $withstats=FALSE)
{
	$items = [];
	$args = ['post_type' => 'prayer_walk', 'post_status'=>['publish','private'], 'numberposts' => -1];
	if ($post_id !== NULL) $args['p'] = $post_id;
	if ($user_id !== NULL) $args['author'] = $user_id;

	$posts = get_posts($args);
	
	if (!is_array($posts)) return FALSE;
	
	if ($post_id != NULL || $user_id != NULL) $withstats = TRUE;
	foreach ($posts as $post)
	{
		$pw = jmapp_sanitize_prayer_walk($post, $withstats); // this will add the markers, map_url, and stats
		$items[] = $pw;
	}
	
	if ($post_id !== NULL) {
		if (count($items) == 0) return FALSE;
		$pw = $items[0];
		return $pw;
	}
	return $items;
}

function jmapp_sanitize_prayer_walk($post, $withstats = FALSE)
{
	$ts = strtotime($post->post_date_gmt);
	$markers = get_post_meta($post->ID, 'markers', true);
	if (empty($markers)) $markers = [];
	
	// fix the datatype of the markers
	$timestamps = [];
	$fixed_markers = [];
	foreach ($markers as $key=>$marker)
	{
		$marker['timestamp'] = (float)$marker['timestamp'];
		$marker['lat'] = (float)$marker['lat'];
		$marker['long'] = (float)$marker['long'];
		
		if (in_array($marker['timestamp'], $timestamps)) continue;
		$timestamps[] = $marker['timestamp'];
		$fixed_markers[] = $marker;
	}
	$markers = $fixed_markers;
	
	// sort the markers
	usort($markers, function($a, $b) {
		if ($a['timestamp'] == $b['timestamp']) return 0;
		return ($a['timestamp'] < $b['timestamp']) ? -1 : 1;
	});
	
	$pw = [
		'ID' => $post->ID,
		'id' => $post->ID,
		'author' => (int)$post->post_author,
		'description' => $post->post_content,
		'title' => $post->post_title,
		'date' => date('c', $ts),
		'timestamp' => $ts,
		'guid' => $post->guid,
		'stats' => [],
		'markers' => $markers,
	];
	
	if ($withstats) $pw['stats'] = jmapp_compute_single_prayer_walk_stats($pw);
	
	// this function will do checks to see if the api needs to be called again
	// don't snap to roads on prayer walks unless an hour has passed since the last marker;
	$last_marker = end($markers);
	if (time() - (int)$last_marker['timestamp'] > 60*60)
		$pw['snapped_markers'] = jmapp_prayer_walk_snap_to_roads_if_needed($pw);
	else
		$pw['snapped_markers'] == [];
	
	$pw['map_url'] = jmapp_prayer_walk_map_url($pw, TRUE);
	return $pw;
}

function jmapp_get_prayer_walk_stats($user_id = NULL, $recompute = FALSE)
{
	$stats = get_option( 'prayer_walk_stats' );
	if ($stats === FALSE || $recompute) $stats = jmapp_compute_prayer_walk_stats();
	
	// remove all user stats except for current user if specified
	$retval = [];
	$retval['computed'] = $stats['computed'];
	$retval['distance_unit'] = 'miles';
	$retval['time_unit'] = 'seconds';
	$retval['distance'] = $stats['distance'];
	$retval['time'] = $stats['time'];
	$retval['walk_count'] = $stats['walks'];
	$retval['user_count'] = count($stats['users']);
	
	if ($user_id != NULL && !empty($stats['users'][$user_id]))
	{
		$retval['current_user'] = $stats['users'][$user_id];
	}
	return $retval;
}

function jmapp_compute_single_prayer_walk_stats($pw)
{
	// compute stats
	$last_marker = NULL;
	$pwstats = ['distance' => 0, 'time' => 0, 'start'=>0, 'end' => 0, 'id'=>$pw['id']];
	foreach ($pw['markers'] as $marker)
	{
		if ($last_marker === NULL || empty($pwstats['start']))
		{
			$pwstats['start'] = $marker['timestamp'];
		}
		else
		{
			$pwstats['distance'] += jmapp_compute_distance($marker, $last_marker);
			$pwstats['time'] += $marker['timestamp'] - $last_marker['timestamp'];
		}
		$last_marker = $marker;
	}
	if ($last_marker != NULL) $pwstats['end'] = $last_marker['timestamp'];
	# fix types
	$pwstats['distance'] = (float)$pwstats['distance'];
	$pwstats['time'] = (int)$pwstats['time'];
	$pwstats['start'] = (int)$pwstats['start'];
	$pwstats['end'] = (int)$pwstats['end'];
	return $pwstats;
}

function jmapp_compute_prayer_walk_stats()
{
	$stats = ['computed'=>time(),'distance' => 0, 'time' => 0, 'walks' => 0, 'users' => []];
	$pws = jmapp_get_prayer_walk();
	$stats['walks'] = count($pws);
	foreach ($pws as $pw)
	{
		$user_id = $pw['author'];
		$pwstats = jmapp_compute_single_prayer_walk_stats($pw);
		
		if (empty($stats['users'][$user_id])) $stats['users'][$user_id] = [
			'user_id' => $user_id,
			'distance' => 0.0,
			'time' => 0,
			'walks' => [],
		];
		
		$stats['users'][$user_id]['walks'][] = $pwstats;
		$stats['users'][$user_id]['distance'] += (float)$pwstats['distance'];
		$stats['users'][$user_id]['time'] += $pwstats['time'];
		$stats['distance'] += $pwstats['distance'];
		$stats['time'] += $pwstats['time'];
	}
	update_option('prayer_walk_stats', $stats, FALSE);
	return $stats;
}

// where a and b are 'markers' containing 'lat' and 'long'
function jmapp_compute_distance($a, $b)
{
	// equirectangular projection from https://www.movable-type.co.uk/scripts/latlong.html
	$R = 3959; // average radius of the Earth in miles
	// convert to radians
	$alat = M_PI * $a['lat'] / 180;
	$along = M_PI * $a['long'] / 180;
	$blat = M_PI * $b['lat'] / 180;
	$blong = M_PI * $b['long'] / 180;
	
	$x = ($blong - $along) * cos(($alat + $blat) / 2);
	$y = $blat - $alat;
	$d = $R * sqrt($x*$x + $y*$y);
	return $d;
}

/* GOOGLE MAP HELPER CALCULATION FUNCTIONS */
function py2Round($value)
{
	// Google's polyline algorithm uses the same rounding strategy as Python 2, which is different from JS for negative values
	return floor(abs($value + 0.5)) * ($value >= 0 ? 1 : -1);
}

function gPolylineEncode( $current, $previous)
{
	$newCurrent = py2Round($current * 100000);
	$newPrevious = py2Round($previous * 100000);
	$coordinate = $newCurrent - $newPrevious;
	$coordinate = $coordinate << 1;
	if ($newCurrent - $newPrevious < 0)
	{
		$coordinate = ~$coordinate;
	}
	$output = '';
	while ($coordinate >= 0x20) {
		$output .= chr((0x20 | ($coordinate & 0x1f)) + 63);
		$coordinate  = $coordinate >> 5;
	}
	$output .= chr($coordinate + 63);
	return $output;
}

/*
 * Encodes the given [latitude, longitude] coordinates array.
 *
 * @param {Array.<Array.<Number>>} coordinates
 * @param {Number} precision
 * @returns {String}
 */
function gPolyline($points) {
	if (empty($points)) {
		return '';
	}
	// $points = pathSimplify($points, 0.1, true);
	
	// output the first point
	$output = gPolylineEncode($points[0]['y'], 0) . gPolylineEncode($points[0]['x'], 0);
	
	// output the offsets
	for ($i = 1; $i < count($points); $i++) {
		$cur = $points[$i];
		$prev = $points[$i - 1];
		$output .= gPolylineEncode($cur['y'], $prev['y']);
		$output .= gPolylineEncode($cur['x'], $prev['x']);
	}
	return $output;
}

/*
 * path simplification routines from https://mourner.github.io/simplify-js/ 
 * and https://github.com/AKeN/simplify-php
*/
function pathSimplify($points, $tolerance = 1, $highestQuality = false) {
	if (count($points) < 2) return $points;
	$sqTolerance = $tolerance * $tolerance;
	if (!$highestQuality) {
		$points = simplifyRadialDistance($points, $sqTolerance);
	}
	$points = simplifyDouglasPeucker($points, $sqTolerance);

	return $points;
}


function getSquareDistance($p1, $p2) {
	$dx = $p1['x'] - $p2['x'];
	$dy = $p1['y'] - $p2['y'];
	return $dx * $dx + $dy * $dy;
}


function getSquareSegmentDistance($p, $p1, $p2) {
	$x = $p1['x'];
	$y = $p1['y'];

	$dx = $p2['x'] - $x;
	$dy = $p2['y'] - $y;

	if ($dx !== 0 || $dy !== 0) {

		$t = (($p['x'] - $x) * $dx + ($p['y'] - $y) * $dy) / ($dx * $dx + $dy * $dy);

		if ($t > 1) {
			$x = $p2['x'];
			$y = $p2['y'];

		} else if ($t > 0) {
			$x += $dx * $t;
			$y += $dy * $t;
		}
	}

	$dx = $p['x'] - $x;
	$dy = $p['y'] - $y;

	return $dx * $dx + $dy * $dy;
}


function simplifyRadialDistance($points, $sqTolerance) { // distance-based simplification
	
	$len = count($points);
	$prevPoint = $points[0];
	$newPoints = array($prevPoint);
	$point = null;
	

	for ($i = 1; $i < $len; $i++) {
		$point = $points[$i];

		if (getSquareDistance($point, $prevPoint) > $sqTolerance) {
			array_push($newPoints, $point);
			$prevPoint = $point;
		}
	}

	if ($prevPoint !== $point) {
		array_push($newPoints, $point);
	}

	return $newPoints;
}


// simplification using optimized Douglas-Peucker algorithm with recursion elimination
function simplifyDouglasPeucker($points, $sqTolerance) {

	$len = count($points);

	$markers = array_fill ( 0 , $len - 1, null);
	$first = 0;
	$last = $len - 1;

	$firstStack = array();
	$lastStack  = array();

	$newPoints  = array();

	$markers[$first] = $markers[$last] = 1;

	while ($last) {

		$maxSqDist = 0;

		for ($i = $first + 1; $i < $last; $i++) {
			$sqDist = getSquareSegmentDistance($points[$i], $points[$first], $points[$last]);

			if ($sqDist > $maxSqDist) {
				$index = $i;
				$maxSqDist = $sqDist;
			}
		}

		if ($maxSqDist > $sqTolerance) {
			$markers[$index] = 1;

			array_push($firstStack, $first);
			array_push($lastStack, $index);

			array_push($firstStack, $index);
			array_push($lastStack, $last);
		}

		$first = array_pop($firstStack);
		$last = array_pop($lastStack);
	}

	for ($i = 0; $i < $len; $i++) {
		if ($markers[$i]) {
			array_push($newPoints, $points[$i]);
		}
	}

	return $newPoints;
}
