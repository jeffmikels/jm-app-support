<?php
/** CUSTOM POST TYPES */
function jmapp_custom_post_types()
{
	jmapp_register_notifications();	
	jmapp_register_prayer_walks();
}
add_action( 'init', 'jmapp_custom_post_types' );


/*
META BOXES FOR POST TYPES
*/

?>