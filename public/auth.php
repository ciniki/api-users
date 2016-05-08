<?php
//
// Description
// -----------
// This method will authenticate a user and return an auth_token
// to be used for future API calls.  Either a username or 
// email address can be used to authenticate.
//
// Info
// ----
// publish:			yes
//
// Arguments
// ---------
// api_key:
// email:			The email address to be authenticated.  The email
//					address or username must be sent.
//
// username:	 	The username to be authenticated.  The username can be
//					an email address or username, they are both unique
//					in the database.
//
// password: 		The password to be checked with the username.
//
// Example Return
// --------------
// <rsp stat="ok" business="3">
//		<auth token="0123456789abcdef0123456789abcdef" id="42" perms="1" avatar_id="192" />
// </rsp>
//
function ciniki_users_auth(&$ciniki) {
	if( (!isset($ciniki['request']['args']['username'])
		|| !isset($ciniki['request']['args']['email']))
		&& !isset($ciniki['request']['args']['password']) 
		&& !isset($ciniki['request']['auth_token'])
		&& !isset($ciniki['request']['args']['user_selector'])
		&& !isset($ciniki['request']['args']['user_token'])
		) {
		//
		// This return message should be cryptic so people
		// can't use the error code to determine what went wrong
		//
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'8', 'msg'=>'Invalid password'));
	}

	//
	// Check access 
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'checkAccess');
	$rc = ciniki_users_checkAccess($ciniki, 0, 'ciniki.users.auth', 0);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	if( isset($ciniki['request']['args']['user_selector']) && $ciniki['request']['args']['user_selector'] != '' 
        && isset($ciniki['request']['args']['user_token']) && $ciniki['request']['args']['user_token'] != '' 
        ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'sessionTokenStart');
		$rc = ciniki_core_sessionTokenStart($ciniki, $ciniki['request']['args']['user_selector'], $ciniki['request']['args']['user_token']);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$version = $rc['version'];
		$auth = $rc['auth'];
    } else if( isset($ciniki['request']['auth_token']) && $ciniki['request']['auth_token'] != '' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'sessionOpen');
		$rc = ciniki_core_sessionOpen($ciniki);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$version = $rc['version'];
		$auth = $rc['auth'];
	} else {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'sessionStart');
		$rc = ciniki_core_sessionStart($ciniki, $ciniki['request']['args']['username'], $ciniki['request']['args']['password']);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$version = $rc['version'];
		$auth = $rc['auth'];
	}

	//
	// Get any UI settings for the user
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
	$rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_user_details', 'user_id', $ciniki['session']['user']['id'], 'ciniki.users', 'settings', 'ui');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['settings']) ) {
		$auth['settings'] = $rc['settings'];
	} else {
		$auth['settings'] = array();
	}

    //
    // Check if a user token should be setup
    //
    if( isset($ciniki['request']['args']['rm']) && $ciniki['request']['args']['rm'] == 'yes' ) {
        $user_token = '';
        $chars = 'abcefghijklmnopqrstuvwxyzABCEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        for($i=0;$i<20;$i++) {
            $user_token .= substr($chars, rand(0, strlen($chars)-1), 1);
        }
        $user_token = sha1($user_token);

        //
        // Check for cookie user_selector
        //
        if( isset($_COOKIE['_UTS']) && $_COOKIE['_UTS'] != '' ) {
            $user_selector = $_COOKIE['_UTS'];
        } else {
            //
            // Create a new user token
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
            $rc = ciniki_core_dbUUID($ciniki, 'ciniki.users');
            if( $rc['stat'] == 'ok' ) { 
                $user_selector = $rc['uuid'];
            }
        }

        if( isset($user_selector) ) {
            $dt = new DateTime('now', new DateTimezone('UTC'));
            $dt->add(new DateInterval('P1M'));
            $strsql = "INSERT INTO ciniki_user_tokens (user_id, selector, token, date_added) VALUES ("
                . "'" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "', "
                . "'" . ciniki_core_dbQuote($ciniki, $user_selector) . "', "
                . "'" . ciniki_core_dbQuote($ciniki, $user_token) . "', "
                . "UTC_TIMESTAMP())";
            $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.users');
            if( $rc['stat'] != 'ok' ) {
                error_log('AUTH-ERR: ' . print_r($rc, true));
            } else {
                $auth['user_selector'] = $user_selector;
                $auth['user_token'] = $user_token;
//                setcookie('_UTS', $user_selector, time()+(10*60*60*24*30), '/manager');
 //               setcookie('_UTK', $user_selector, time()+(10*60*60*24*30), '/manager');
            }
        }
    }

	//
	// If the user is not a sysadmin, check if they only have access to one business
	//
	if( ($ciniki['session']['user']['perms'] & 0x01) == 0 ) {
		$strsql = "SELECT DISTINCT ciniki_businesses.id, name "
			. "FROM ciniki_business_users, ciniki_businesses "
			. "WHERE ciniki_business_users.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
			. "AND ciniki_business_users.status = 1 "
			. "AND ciniki_business_users.business_id = ciniki_businesses.id "
			. "AND ciniki_businesses.status < 60 "	// Allow suspended businesses to be listed, so user can login and update billing/unsuspend
			. "ORDER BY ciniki_businesses.name "
			. "LIMIT 2"
			. "";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'business');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['business']) ) {
			return array('stat'=>'ok', 'auth'=>$auth, 'business'=>$rc['business']['id']);
		}
	}	

	return array('stat'=>'ok', 'version'=>$version, 'auth'=>$auth);
}
?>
