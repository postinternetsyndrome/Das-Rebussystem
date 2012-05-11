<?php

require_once "rebus.php";

define('SQLITE_DB', DATAROOT . '/' . NAME . '/db');

function create()
{
    error_log("Not a valid database. Creating a new one!<br>\n.",0); 
    $db = new SQLite3(SQLITE_DB);

    $columns = array();
    $values = array();
    for ($event = 0; $event < count($GLOBALS['events']); ++$event) { 
        $columns[] = "event$event INTEGER";
	$values[] = "NULL";
	error_log("event$event INTEGER<br>\n.",0); 
    }

    $c = "CREATE TABLE rebus (team INTEGER PRIMARY KEY, " . implode(',', $columns) . ")";
    error_log("CREATE TABLE rebus (team INTEGER PRIMARY KEY, " . implode(',', $columns) . ")<br>\n.",0); 
    $db->query($c);

    for ($team = 0; $team < count($GLOBALS['teams']); ++$team) {
	$db->query("INSERT INTO rebus VALUES ($team, " . implode(',', $values) . ")");
	error_log("INSERT INTO rebus VALUES ($team, " . implode(',', $values) . ")<br>\n.",0); 
    }
}

function getDb()
{
    static $db;
    static $teamn;
    static $eventn;
    if (!isset($db)) {
	if (!is_readable(SQLITE_DB)) {
	    error_log("db not readable $db<br>\n.",0); 
	    create();
	}
	$db = new SQLite3(SQLITE_DB);
	$teamn = $db->querySingle("SELECT COUNT(*) FROM rebus");
	$e = $db->querySingle("SELECT * FROM rebus WHERE team=0", true);
	$eventn = count($e) - 1;
    }

    if ($teamn==""){
       $teamn = 0;
    }

    $teams_in_setup = count($GLOBALS['teams']);
    if (count($GLOBALS['teams']) > $teamn) {
	error_log("teams in db is less than in setup:$teamn<$teams_in_setup<br>\n.",0); 
    	$values = array();
	for ($event = 0; $event < count($GLOBALS['events']); ++$event) { 
	    $values[] = "NULL";
	}
	for ($team = $teamn; $team < count($GLOBALS['teams']); ++$team) {
	    $db->query("INSERT INTO rebus VALUES ($team, " . implode(',', $values) . ")");
	    error_log("INSERT INTO rebus VALUES ($team, " . implode(',', $values) . ")",0);
	}
	$teamn = count($GLOBALS['teams']);
    }
    $events_in_setup = count($GLOBALS['events']);
    if (count($GLOBALS['events']) > $eventn) {
    	error_log("events in db are less than in setup:$eventn<$eventss_in_setup<br>\n.",0); 
	for ($event = $eventn; $event < count($GLOBALS['events']); ++$event) { 
	    $db->query("ALTER TABLE rebus ADD COLUMN event$event INTEGER DEFAULT NULL");
	    error_log("ERROR event numbers not matching. Adding event $event.<br>\n",0); 
	}
	$eventn = count($GLOBALS['events']);
    }

    return $db;
}

function convert()
{
    copy(SQLITE_DB, SQLITE_DB . '.old');

    $db = getDb();

    create();

    for ($team = 0; $team < count($GLOBALS['teams']); ++$team) {
	for ($event = 0; $event < count($GLOBALS['events']); ++$event) { 
	    $p = $db->querySingle("SELECT points FROM team$team WHERE event=$event");
	    setPoints($team, $event, $p);
	}
    }
    for ($team = 0; $team < count($GLOBALS['teams']); ++$team) {
	$db->query("DROP TABLE team$team");
    }
}

function setPoints($team, $event, $points)
{
    $db = getDb();

    if ($points == '') {
	$points = 'NULL';
    }

    if ($points != 'NULL' and !is_numeric($points)) {
	return;
    }

     $db->query("UPDATE rebus SET event$event=$points WHERE team=$team");
}

function getPoints($team, $event)
{
    $db = getDb();

    $row = $db->querySingle("SELECT event$event FROM rebus WHERE team=$team");

#    if ($row != 'NULL' and !is_numeric($row)){
#       $GLOBALS['error_messages'] = $GLOBALS['error_messages']."ERROR not a valid return value:$row for event $event team $team<br>\n"; 
#    }
    return $row;
}

function getEventPoints($event, $event2 = null)
{
    for ($team = 0; $team < count($GLOBALS['teams']); ++$team) {
	if (is_null($event2)) {
	    $result[$team] = getPoints($team, $event);
	}
	else {
	    $result[$team] = array(getPoints($team, $event), 
				   getPoints($team, $event2));
	}
    }
    return $result;
}

function getAvgEventPoints($event, $event2 = null)
{
    $result = 0;
    for ($team = 0; $team < count($GLOBALS['teams']); ++$team) {
	if (is_null($event2)) {
	    $result += getPoints($team, $event);
	}
	else {
	  $result += getPoints($team, $event) + getPoints($team, $event2);
	}
    }
    return $result / count($GLOBALS['teams']);
}

function updateEventPoints(&$data, $event)
{
    for ($team = 0; $team < count($GLOBALS['teams']); ++$team) {
	if (!isset($data[$team])) {
	    $data[$team] = 0;
	}
	$data[$team] += getPoints($team, $event);
    }
    
}
?>
