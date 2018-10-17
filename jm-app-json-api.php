<?php
/**
 * Forked and modified from the "simply-json" plugin
 * from http://wordpress.org/extend/plugins/simply-json and authored by http://zeamedia.de
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


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
add_action('pre_get_posts', 'jmapp_simply_json_custom_query');


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