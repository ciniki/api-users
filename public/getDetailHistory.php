<?php
//
// Description
// -----------
// This function will get the history of a field from the core_change_logs table.
// This allows the user to view what has happened to a data element, and if they
// choose, revert to a previous version.
//
// Info
// ----
// Status: 		beta
//
// Arguments
// ---------
// api_key:
// auth_token:
// user_id:					The user ID to get the history detail for.
// field:					The detail key to get the history for.
//
// Returns
// -------
//	<history>
//		<action date="2011/02/03 00:03:00" value="Value field set to" user_id="1" />
//		...
//	</history>
//	<users>
//		<user id="1" name="users.display_name" />
//		...
//	</users>
//
function ciniki_users_getDetailHistory($ciniki) {
	//
	// Find all the required and optional arguments
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'user_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No user specified'), 
		'field'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No field specified'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access 
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/checkAccess.php');
	$rc = ciniki_users_checkAccess($ciniki, 0, 'ciniki.users.getDetailHistory', $args['user_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}


	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbGetChangeLog.php');
	if( $args['field'] == 'user.firstname' ) {
		return ciniki_core_dbGetChangeLog($ciniki, 0, 'users', $args['user_id'] . "-user.firstname", 'firstname', 'users');
	} elseif( $args['field'] == 'user.lastname' ) {
		return ciniki_core_dbGetChangeLog($ciniki, 0, 'users', $args['user_id'] . "-user.firstname", 'lastname', 'users');
	} elseif( $args['field'] == 'user.display_name' ) {
		return ciniki_core_dbGetChangeLog($ciniki, 0, 'users', $args['user_id'] . "-user.firstname", 'display_name', 'users');
	}

	return ciniki_core_dbGetChangeLog($ciniki, 0, 'user_details', $args['user_id'] . "-" . $args['field'], 'detail_value', 'users');
}
?>
