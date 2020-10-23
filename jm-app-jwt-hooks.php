<?php
add_action('jwt_auth_expire','i_jwt_auth_expire');
function i_jwt_auth_expire() {
	return time() + (DAY_IN_SECONDS * 365);
}
?>