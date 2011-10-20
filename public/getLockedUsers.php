<?php
//
// Description
// -----------
// Returns the user accounts which are locked.
//
// Info
// ----
// Status: 		beta
//
// Arguments
// ---------
// api_key:
// auth_token:
//
// Returns
// -------
// <users>
//		<user id='1' email='' firstname='' lastname='' display_name='' perms=''/>
// </users>
//
function ciniki_users_getLockedUsers($ciniki) {
	//
	// Check access 
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/checkAccess.php');
	$rc = ciniki_users_checkAccess($ciniki, 0, 'ciniki.users.getLockedUsers', 0);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Query for the business users
	//
	$strsql = "SELECT id, email, firstname, lastname, display_name, perms FROM users "
		. "WHERE (status = 10 OR login_attempts > 7 ) ORDER BY lastname, firstname ";
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbRspQuery.php');
	return ciniki_core_dbRspQuery($ciniki, $strsql, 'users', 'users', 'user', array('stat'=>'ok', 'users'=>array()));
}
?>
