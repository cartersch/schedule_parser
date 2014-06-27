<?php
// header('Access-Control-Allow-Origin: *');

// ini_set('display_errors', 'On');
// error_reporting(E_ALL);

require_once('schedule_parser.php');

$sp = new ScheduleParser();

if($_FILES['uploadFile']){
    $eventId = 0;

    $start = date('Y-m-d', time());

    $dates = array(
        'start_date' => $start,
        'end_date' => date('Y-m-d', strtotime( $start . ' + 5 days'))
    );

    $file = $_FILES['uploadFile']['tmp_name'];

    $schedule = $sp->parseSchedule($eventId, $dates, $file);

    //print_r($schedule);

    $schedule = json_encode($schedule);

    echo $schedule;
} else {

}


?>
