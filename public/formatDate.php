<?php
//
// Description
// -----------
// This method will return a date formatted to the users date preferences.
//
// Info
// ----
// publish:         no
//
// Arguments
// ---------
// api_key:
// auth_token:
// date:            The date string to format.
// 
// Returns
// -------
// <rsp stat="ok" date="July 21, 2012" />
//
function ciniki_users_formatDate($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'date'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Date'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'checkAccess');
    $rc = ciniki_users_checkAccess($ciniki, 0, 'ciniki.users.formatDate', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    
    //
    // Get the current time in the users format
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);

    // date_default_timezone_set('America/Toronto');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $strsql = "SELECT DATE_FORMAT(FROM_UNIXTIME('" . strtotime($args['date']) . "'), '" . ciniki_core_dbQuote($ciniki, $date_format) . "') as formatted_date ";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.core', 'date');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $formatted_date = '';
    if( isset($rc['date']['formatted_date']) ) {
        $formatted_date = $rc['date']['formatted_date'];
    }

    //
    // Currently the same list as private/getSettings, but may be alter in the future
    // to only contain settings available
    //
    return array('stat'=>'ok', 'date'=>$formatted_date);
}
?>
