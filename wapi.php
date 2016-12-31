<?
require_once('../../tocr_common_files/misc.php');
require_once('../../tocr_common_files/classTOCRDB.php');

// Get instance of DB class
$db = tocrDB::connect(array("dbname"=>"speed_quiz", "user"=>"speed_quiz", "password"=>"speed_quiz") );
$DEBUG = true;

$ajax = GetRequestData("ajax", _STR_, 3);
if($DEBUG && $ajax) error_log("ajax: {$ajax}");

if(isset($ajax)) {
	switch($ajax) {
	case "GetTopTenTimes": // 
		JSON_GetTopTenTimes($db, $DEBUG );
		break;
	case "RecordResult": // 
		RecordResult($db, $DEBUG );
		break;
	default:
		print json_encode(array("<h1 style='color: red;'>AJAX FAIL"));
		if($DEBUG) error_log("AJAX FAIL");
		break;
	}

	if($DEBUG) { error_log("{$ajax} call time: "); }
	
	return;
}

function JSON_GetTopTenTimes($db, $debug) {
	$name = GetRequestData("quiz_name", _STR_, 1, false);
	$num = GetRequestData("num_questions", _NUM_, 1, false);
	if($name && $num) {
		$sql = "SELECT id, quiz_name, quizee_name, completion_time, num_questions, quiz_taken_ts, rank() OVER (PARTITION BY quiz_name ORDER BY completion_time) FROM quiz_results WHERE quiz_name = $1 AND num_questions = $2";
		$res = $db->db_query($sql, true, array($name, $num) );
		$r = pg_fetch_all($res);
		if($debug) { error_log(__function__ . json_encode(array("quiz_name" => $quiz_name, "num_questions" => $num, $r) ) ); }
		print json_encode($r);
	}
}

function RecordResult($db, $debug) {
	$quiz_name = GetRequestData("quiz_name", _STR_, 1, false);
	$quizee_name = GetRequestData("quizee_name", _STR_, 1, false); 
	$t = GetRequestData("completion_time", _STR_, 1, false); 
	$n = GetRequestData("num_questions", _NUM_, 1, false); 
	if($quiz_name && $quizee_name && $t && $n) {
		$db->db_query("BEGIN;", false );
		$sql = "INSERT INTO quiz_results (quiz_name, quizee_name, num_questions, completion_time) VALUES ($1, $2, $3, $4) RETURNING id;";
		$res = $db->db_query($sql, false, array($quiz_name, $quizee_name, $n, $t) );
		$o = pg_fetch_object($res);
		if($o->id > 0) { 
			$db->db_query("COMMIT;"); 
			print(json_encode($o->id) );
			return true;
		}
	}
	
	// By default
	print json_encode(false);
	return false;
	
}


?>
