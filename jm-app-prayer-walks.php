<?php


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