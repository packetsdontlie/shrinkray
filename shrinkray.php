<?php

/*
date: 2013-dec/2014-jan
beasmaster: REDACTED
help: REDACTED
svn: http://REDACTED/shrinkray/?root=svn
readme: http://REDACTED/README.md?root=svn&view=log
overview: given a file, try progressive steps to reduce the information
*/

// derived
$how = $_SERVER['REQUEST_METHOD'];

// steped in as needed
$ax=isset($_REQUEST['ax']) ? $_REQUEST['ax'] : null;
$mode=isset($_REQUEST['mode']) ? $_REQUEST['mode'] : null;
$srpath=isset($_REQUEST['srpath']) ? $_REQUEST['srpath'] : null;
$max=isset($_REQUEST['max']) ? $_REQUEST['max'] : null;

// target size
$shrinkray_max_size=7340032;


// response codes
$shrinkray_status_ok='HTTP/1.1 200 OK';
$shrinkray_status_shrunk_tags='HTTP/1.1 230 Shrunk via tags';
$shrinkray_status_shrunk_dedup='HTTP/1.1 235 Shurnk via duplication';
$shrinkray_status_shrunk_punctuation='HTTP/1.1 239 Shrunk via punctuation';
$shrinkray_status_shrunk_numbers='HTTP/1.1 240 Shrunk via numbers';
$shrinkray_status_shrunk_lowercase='HTTP/1.1 245 Shrunk via lowercase';
$shrinkray_status_shrunk_header='HTTP/1.1 250 Shrunk via header';
$shrinkray_status_error='HTTP/1.1 400 Bad Request';
$shrinkray_status_gone='HTTP/1.1 410 Gone';
$shrinkray_status_precondition_failed='HTTP/1.1 412 Precondition Failed';
$shrinkray_status_too_large='HTTP/1.1 413 Request Entity Too Large';
$shrinkray_status_internal_error='HTTP/1.1 500 Internal Server Error';
$shrinkray_status_redirect='Location: /shrinkray/';

// array magic to find uniq values
function shrinkray_dedup($input_string) {
    $start_array=explode(" ", $input_string);
    # $dedup_array=array_flip(array_merge(array_flip($start_array))); // faster than array_unique
	$dedup_array=array_merge(array_flip(array_flip($start_array))); // faster than array_unique
    $output_string=implode(" ", $dedup_array);
	return $output_string;
}

// evaluate the string, did it work? if so, output
function shrinkray_eval($input_string, $response_status) {
	global $shrinkray_max_size;
	if (strlen($input_string) < $shrinkray_max_size ) {
		shrinkray_response($response_status); // set HTTP headers before delivering data!
		print $input_string;
		exit;
	}
}


// handle all HTTP headers
function shrinkray_response($shrinkray_status, $shrinkray_message, $shrinkray_exit = NULL) {
    header($shrinkray_status);
    if (!empty($shrinkray_message)) {
         echo $shrinkray_message;
    }
    if (!empty($shrinkray_exit)){
         exit($shrinkray_exit);
    }
}

switch ($ax) {
	case "help":
		print "This is a webservice for shrinking documents.  It accepts the ax of help, lim, uq, ht, sr.  Find out more http://REDACTED/docs/README.md?root=svn&view=log";
		exit;
		break;
	case "lim":
		print $shrinkray_max_size;
		exit;
		break;
	case "sr":
		if (empty($srpath)) {
		   shrinkray_response($shrinkray_status_error, "did not recieve GET with srpath", 1);
		   exit;
		}
		if (is_readable($srpath)) {
		    $source_string=file_get_contents($srpath, true);
		} else {
			shrinkray_response($shrinkray_status_gone, "document does not exist", 0);
			exit;
		}
		if (strlen($source_string) > $shrinkray_max_size) {
			// step - strip HTML
			$working_string=strip_tags($source_string);
			shrinkray_eval($working_string, $shrinkray_status_shrunk_tags);
			
			// step - remove duplicate tokens
			shrinkray_eval(shrinkray_dedup($working_string), $shrinkray_status_shrunk_dedup);
			
			// step - strip punctuation
			$working_string=preg_replace("/[\p{P}\p{S}\p{Zp}]/", " ", $working_string);
			$working_string=preg_replace("/\s\s+/", " ", $working_string);
				// reference: http://us1.php.net/manual/en/regexp.reference.unicode.php
				// \p{P} 	punctuation class
				// \p{S} 	symbol class
				// \p{N}	number class
				// was $working_string=preg_replace("/[^a-zA-Z0-9\s]+/", " ", $working_string);
			shrinkray_eval(shrinkray_dedup($working_string), $shrinkray_status_shrunk_punctuation);
			
			// step - strip numbers
			$working_string=preg_replace("/\p{N}/", " ", $working_string);
			$working_string=preg_replace("/\s\s+/", " ", $working_string);
			shrinkray_eval(shrinkray_dedup($working_string), $shrinkray_status_shrunk_numbers);
			
			// step - lower case
			shrinkray_eval(shrinkray_dedup(strtolower($working_string)), $shrinkray_status_shrunk_lowercase);
			
			// step - head, switch back to source string, remove HTML tags again
			$working_string=substr(strip_tags($source_string), 0, ($shrinkray_max_size - 100));
			$working_string=preg_replace("/\s\s+/", " ", $working_string);
			shrinkray_eval($working_string.'...', $shrinkray_status_shrunk_header);
			
			// Ugh, it didn't work and really there's not a lot of ways to get to this step
			shrinkray_response($shrinkray_status_too_large, "tried all the tricks, still too large", 1);
			
		} else {
			shrinkray_response($shrinkray_status_precondition_failed, "document does not need shrinking", 0);
		}
		break;
	default:
	    shrinkray_response($shrinkray_status_redirect, "bye bye", 0);
	    break;
}
?>
