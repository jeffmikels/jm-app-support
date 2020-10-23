<?php
DEFINE('jmapp_post_KEY', 'jmapp_post_key');
function jmapp_add_post_box() {
		$screens = [ 'post'];
		foreach ( $screens as $screen ) {
			add_meta_box(
			'jmapp_post_box_id',            // Unique ID
			'Post-level App Settings',      // Box title
			'jmapp_post_box_html',          // Content callback, must be of type callable
			$screen                         // Post type
			);
		}
}
add_action( 'add_meta_boxes', 'jmapp_add_post_box' );

function jmapp_post_box_html( $post ) {
	$hide_from_apps = get_post_meta( $post->ID, 'jmapp_post_hide_from_apps', true );
	$hide_from_apple_only = get_post_meta( $post->ID, 'jmapp_post_hide_from_apple_only', true );
	?>
	<label for="jmapp_post_hide_from_apps">
		<input
			type="checkbox"
			name="jmapp_post_hide_from_apps"
			id="jmapp_post_hide_from_apps"
			value=1
			<?php if ($hide_from_apps) echo ' checked' ?>
		>
		Hide Post From All Mobile Apps
	</label>
	<br />
	<label for="jmapp_post_hide_from_apple_only">
		<input
			type="checkbox"
			name="jmapp_post_hide_from_apple_only"
			id="jmapp_post_hide_from_apple_only"
			value=1
			<?php if ($hide_from_apple_only) echo ' checked' ?>
		>
		Hide Post From iOS Apps
	</label>
	<?php
}

function jmapp_post_save_postdata( $post_id ) {
	foreach (['jmapp_post_hide_from_apps', 'jmapp_post_hide_from_apple_only'] as $key) {
		if ( array_key_exists( $key, $_POST ) ) {
			update_post_meta(
				$post_id,
				$key,
				$_POST[$key]
			);
		}
	}
}
add_action( 'save_post', 'jmapp_post_save_postdata' );