<?php

function jmapp_get(&$var, $default=null)
{
	// https://stackoverflow.com/questions/6696425/is-there-a-better-php-way-for-getting-default-value-by-key-from-array-dictionar
	// if we could guarantee people were using php 7, we could do this:
	// return $var ?? $default;
	return isset($var) ? $var : $default;
}

function jmapp_err($s)
{
	add_filter('redirect_post_location', function($location) use ($s) {
		return add_query_arg(['jmapp_err'=>urlencode($s)], $location);
	});
}
function jmapp_msg($s)
{
	add_filter('redirect_post_location', function($location) use ($s) {
		return add_query_arg(['jmapp_msg'=>urlencode($s)], $location);
	});
}

function jmapp_remove_empty_keys($obj)
{
	foreach($obj as $key=>$value)
	{
		if (is_array($value)) $value = jmapp_remove_empty_keys($value);
		if (empty($value)) unset($obj[$key]);
	}
	return $obj;
}

function jmapp_ensure_keys($obj, $key_array)
{
	foreach ($key_array as $key)
	{
		if (!array_key_exists($key, $obj))
		{
			$obj[$key] = '';
		}
	}
	return $obj;
}

function jmapp_sample_menu(){
	return array(
		'default_image_url' => '',
		'drawer_menu' => array('drawer_header_url'=>'','sections'=>array()),
		'home_menu' => array('items' => array())
	);
}

function jmapp_read_menu_file()
{
	if (!file_exists(JMAPP_MENU_PATH)) return jmapp_sample_menu();
	
	$menu = json_decode(file_get_contents(JMAPP_MENU_PATH), TRUE);
	
	foreach(jmapp_sample_menu() as $key=>$value) {
		if (!isset($menu[$key])) $menu[$key] = $value;
	}
	
	$providers = jmapp_get_providers();
	
	// make sure all providers have all possible fields
	foreach($menu['drawer_menu']['sections'] as $sectionkey=>$section)
	{
		foreach($section['items'] as $itemkey=>$item)
		{
			if (empty($item['provider']) || $item['provider'] == 'tabs')
			{
				foreach($item['tabs'] as $tabkey=>$tab)
				{
					$args = $providers[$tab['provider']]['arguments'];
					if (!array_key_exists('arguments',$tab))
					{
						$tab['arguments'] = array();
					}
					$menu['drawer_menu']['sections'][$sectionkey]['items'][$itemkey]['tabs'][$tabkey]['arguments'] = jmapp_ensure_keys($tab['arguments'], $args);
				}
			}
			else
			{
				$args = $providers[$item['provider']]['arguments'];
				if (!array_key_exists('arguments',$item))
				{
					$item['arguments'] = array();
				}
				$menu['drawer_menu']['sections'][$sectionkey]['items'][$itemkey]['arguments'] = jmapp_ensure_keys($item['arguments'], $args);
			}
		}
	}
	return $menu;
}

function jmapp_write_menu_file($menu)
{
	$menu = jmapp_remove_empty_keys($menu);
	return file_put_contents(JMAPP_MENU_PATH, json_encode($menu));
}


function jmapp_ajax($command, $data=[])
{
	if ($command == 'write')
	{
		if (jmapp_write_menu_file($data) === FALSE) jmapp_result('error', 'could not write to file');
		else jmapp_result('success', 'successfully written', $data);
	}
	if ($command == 'read')
	{
		$data = jmapp_read_menu_file();
		jmapp_result('success','menu file read', $data);
	}
}

function jmapp_result($error_or_success, $message, $data='')
{
	header ('Content-type: application/json');
	$result = ['message' => $error_or_success, 'message'=>$message, 'data'=>$data];
	wp_send_json($result);
	// wp_die();
}