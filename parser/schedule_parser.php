<?php


class ScheduleParser{
    
    protected $contents = '';
    protected $eventId = '';
    protected $scheduleDay = '';
    protected $itemCounter = 1;
    protected $eventDates = array();
    protected $dayDetails = array();
    protected $speakerOrder = array();
    protected $schedule = array();
    

    /**
     * parseSchedule function - main public function
     * that creates the schedule object
     *
     * @param string $event_id 
     * @param string $dates 
     * @param string $file 
     * @return void
     * @author Carter Schoenfeld
     */
    public function parseSchedule($event_id, $dates, $file){
        
        $this->eventId = $event_id;
        $this->eventDates = $dates; 
        $this->contents = file_get_contents($file);
        $this->scheduleDay = $this->eventDates['start_date'];
        
        $this->splitText();
        $this->parseDailyDetails(); 
        
        return $this->schedule;
        
    }
    
    /**
     * splitText - Takes the text from the file and creates 
     * an array for each scheduled day
     *
     * @return void
     * @author Carter Schoenfeld
     */
    private function splitText(){
        
        return $this->dayDetails = preg_split('/##(\s+\w+){1,}/', $this->contents);
        
    }
    
    /**
     * parseDailyDetails loops through each daily array and
     * starts to build the schedule 
     *
     * @return void
     * @author Carter Schoenfeld
     */
    private function parseDailyDetails(){

        foreach($this->dayDetails as $d){
            if(strlen($d) == 0){ continue;} // this kinda sucks.  Wish PREG_SPLIT_NO_EMPTY would have worked
            
            $d = $this->stripDayTitle($d);
            
            $this->parseSingleDayDetails($d);
            
        }
        
    }
    
    /**
     * stripDayTitle-removes the text that says ##Day... from the string
     *
     * @param string $d 
     * @return void
     * @author Carter Schoenfeld
     */
    private function stripDayTitle($d){
        return preg_replace('/##(\s+\w+){1,}/', '', $d);
    }
    
    /**
     * parseSingleDayDetails - splits the string into sections
     * (Fixtures, Other Items, Speakers) and keeps track of the
     * workshop day.
     *
     * @param string $str 
     * @return void
     * @author Carter Schoenfeld
     */
    private function parseSingleDayDetails($str){
        $array = preg_split("/#(\s+\w+){1,}/", $str);
        
        $this->parseSection($array);
        $this->cleanUpSchedule();
        $this->scheduleDay = date('Y-m-d', strtotime($this->scheduleDay . ' + 1 day'));
        $this->itemCounter = 1;
        
    }
    
    /**
     * parseSections loops through the arrays and
     *
     * @param string $array 
     * @return void
     * @author Carter Schoenfeld
     */
    private function parseSection($array){
        foreach($array as $a){
            
            if(strlen($a) == 0){ continue;}
            
            
            if(strpos($a, 'Fixture')){
                
                $this->parseItems($a);
                
            } else if(strpos($a, 'Other')){
                
                $this->parseItems($a);
                
            } else if(strpos($a,'Name')){
                
                $this->organizeScheduleDay();
                $this->parseSpeakers($a);
                
            }

        }
    }
    
    /**
     * parseItems - builds each non-lecture schedule item and adds it to the 
     * schedule array
     *
     * @param string $a 
     * @return void
     * @author Carter Schoenfeld
     */
    private function parseItems($a){
        $items = preg_split("/\n/", $a);
        unset($items[0]);
        foreach($items as $i){
            if(strlen($i) <=1){ continue;}
            
            $details = explode('|', $i);
            if(count($details) == 2){
                $start = $this->createTimestamp($details[0]);
                $text = $this->setItemText($details[1]);
                $showEndTime = $this->showEndTime($text);
                
                $this->schedule[$this->scheduleDay][$this->itemCounter]['scheduleID'] = '';
                $this->schedule[$this->scheduleDay][$this->itemCounter]['startTime'] = $start;
                $this->schedule[$this->scheduleDay][$this->itemCounter]['scheduleType'] = 'General';   
                $this->schedule[$this->scheduleDay][$this->itemCounter]['scheduleValue'] = $text;
                $this->schedule[$this->scheduleDay][$this->itemCounter]['showEndTime'] = $showEndTime;
                    
            } else{
                $start = $this->createTimestamp($details[0]); 
                $end = $this->createTimestamp($details[1]); 
                $type = strpos(strtolower($details[2]), 'lunch') ? 'Break' : 'General';
                $text = $this->setItemText($details[2]);
                $showEndTime = $this->showEndTime($text);
                $duration = $this->calcDuration($start, $end);
                
                $this->schedule[$this->scheduleDay][$this->itemCounter]['scheduleID'] = '';
                $this->schedule[$this->scheduleDay][$this->itemCounter]['startTime'] = $start;
                $this->schedule[$this->scheduleDay][$this->itemCounter]['scheduleType'] = $type;
                $this->schedule[$this->scheduleDay][$this->itemCounter]['scheduleValue'] = $text;
                $this->schedule[$this->scheduleDay][$this->itemCounter]['endTime'] = $end;
                $this->schedule[$this->scheduleDay][$this->itemCounter]['duration'] = $duration;
                $this->schedule[$this->scheduleDay][$this->itemCounter]['showEndTime'] = $showEndTime;
                
            }
            
            $this->itemCounter++;
        }
        
    }
    
    /**
     * organizeScheduleDay - places all the schedule items in
     * order by startTime.  This helps when looking for gaps to
     * place speakers
     *
     * @return void
     * @author Carter Schoenfeld
     */
    private function organizeScheduleDay(){
        
        foreach($this->schedule[$this->scheduleDay] as $key => $item){
            $startTime[$key] = $item['startTime']; 
        }
        
        array_multisort($startTime, SORT_ASC, $this->schedule[$this->scheduleDay]);
        
        $this->checkDurations();
    }
    
    /**
     * checkDurations - loops through each schedule item and 
     * sets a duration time if one has not been set.
     *
     * @return void
     * @author Carter Schoenfeld
     */
    private function checkDurations(){
        $day = $this->schedule[$this->scheduleDay];
        
        for($i = 0; $i < count($day); $i++){
            if(array_key_exists('duration',$day[$i])){continue;}
            
            if($i == count($day) - 1){ //if it's the last item in the array
                $day[$i]['duration'] = 0;
                $day[$i]['endTime'] = $day[$i]['startTime'];
            } else {
                $nextStart = $day[$i+1]['startTime'];
                $day[$i]['duration'] = $this->calcDuration($day[$i]['startTime'], $nextStart);
                $day[$i]['endTime'] = $nextStart;
            }
        }
        
        $this->schedule[$this->scheduleDay] = $day;
        
    }
    
    
    private function parseSpeakers($a){
        $speakers = preg_split("/\n/", $a);
        unset($speakers[0]);
        $counter = 0;
        
        foreach($speakers as $s){
            if(strlen($s) <= 1){continue;}
            $details = explode('|', $s);
            $this->findAvailableSlot($details);
        }
        
    }
    
    private function findAvailableSlot($details){
        $day = $this->schedule[$this->scheduleDay];
        $lectureTime = (int)$details[0];
        $totalTime = $this->getTotalLectureTime($details);
        
        $total = count($day);
        
        for($i = 0; $i < $total; $i++){
            $forceAdd = false;
            $currentEnd = $day[$i]['endTime'];
            $nextStart = $day[$i+1]['startTime'];
            $availableTime = $this->calcDuration($currentEnd, $nextStart);
            
            if($i == ($total -1 )){
                
                $currentEnd = $day[$i - 1]['endTime'];
                $nextStart = $day[$i]['startTime'];
                $forceAdd = true;
            }
            if($lectureTime > $availableTime && !$forceAdd){continue;}
            
            //creating lecture item
            $speakerId = $this->getSpeakerId($details[1]);
            $duration = trim($details[0]);
            $start = $currentEnd;
            $end = date('Y-m-d H:i:s', strtotime($currentEnd . ' + ' . $details[0]. ' minutes'));
            if(is_int($speakerId)){
                $type = 'Lecture';
                $text = $speakerId . '&';
            } else {
                $type = 'Lecture';
                $text = $speakerId;
            }
            
            
            
            $lecture[0]['scheduleID'] = '';
            $lecture[0]['startTime'] = $start;
            $lecture[0]['scheduleType'] = $type;
            $lecture[0]['scheduleValue'] = trim($text);
            $lecture[0]['endTime'] = $end;
            $lecture[0]['duration'] = $duration;
            $lecture[0]['showEndTime'] = 'Yes';
                        
            $lecture = $this->addBreaks($details, $lecture, $end, $nextStart, $day[$i+1], $i+1);
                 
                      
            $this->schedule[$this->scheduleDay] = array_merge($this->schedule[$this->scheduleDay], $lecture);
            
            
            
            
            $this->organizeScheduleDay();
            break;
        }
    }
    
    /**
     * getTotalLectureTime calculates the total amount of 
     * minutes in a Speaker Entry including lecture times,
     * breaks, and discussions
     *
     * @param array $d 
     * @return int
     * @author Carter Schoenfeld
     */
    private function getTotalLectureTime($d){
        $total = 0;
        
        for($i = 0; $i< count($d); $i=$i+2){
            $total = $total + (int)trim($d[$i]);
        }
        
        return $total;
    }
    
    /**
     * createTimeStamp - sets the AM/PM on the time and returns a date
     * string with a 24hr time stamp.
     *
     * @param string $time 
     * @return void
     * @author Carter Schoenfeld
     */
    private function createTimestamp($time){
        
        return date('Y-m-d H:i:s', strtotime($this->scheduleDay . ' ' . $time));
    }
    
    
    /**
     * AddBreaks
     *
     * @param array $details 
     * @param array $lecture 
     * @param string $end 
     * @param string $nextStart 
     * @param array $day 
     * @param int $counter 
     * @return array
     * @author Carter Schoenfeld
     */
    private function addBreaks($details, $lecture, $end, $nextStart,$day, $counter){
        $idx=1;
        
        
        
        for($i = 2; $i < count($details); $i = $i+2){
            if(trim($details[$i]) != 0){
                $breakStart = $end;
                $breakEnd = date('Y-m-d H:i:s', strtotime($end . ' + ' . $details[$i]. 'min'));
                $breakType = strpos(strtolower($details[$i +1]),'break') ? 'Break' : 'General';
                $breakText = trim($details[$i+1]);
                $breakDuration = (int)trim($details[$i]);
                
                $lecture[$idx]['scheduleID'] = '';
                $lecture[$idx]['startTime'] = $breakStart;
                $lecture[$idx]['scheduleType'] = $breakType;
                $lecture[$idx]['scheduleValue'] = $breakText;
                $lecture[$idx]['endTime'] = $breakEnd;
                $lecture[$idx]['duration'] = $breakDuration;
                $lecture[$idx]['showEndTime'] = 'Yes';
                
                
                if($lecture[$idx]['endTime'] > $day['startTime']){
                    $time = $this->calcDuration($lecture[$idx]['endTime'], $nextStart);
                    $this->adjustScheduleTimes($counter, $time);
                }
                
                $end = $breakEnd;
            }
            $idx++;
        }
        
        
        
        return $lecture;
    }
    
    /**
     * setItemText - returns a trimmed and encoded string
     *
     * @param string $text 
     * @return void
     * @author Carter Schoenfeld
     */
    private function setItemText($text){
        return htmlentities(trim($text), ENT_QUOTES, 'UTF-8'); 
    }
    
    private function showEndTime($text){
        return strpos(strtolower($text), 'shuttle') === false ? 'Yes' : 'No'; 
    }
    
    /**
     * calcDuration - determines and returns the time duration bewteen the 
     * start and end datetimes.
     *
     * @param string $start 
     * @param string $end 
     * @return void
     * @author Carter Schoenfeld
     */
    private function calcDuration($start, $end){
        $s = new DateTime($start);
        $e = new DateTime($end);
        
        $diff = $s->diff($e);
        
        if($diff->format('%h') > 0){
            return $diff->format('%i') + ($diff->format('%h') * 60);
        } else {
            return $diff->format('%i');
        }
    }
    
    /**
     * getSpeakerId - gets the PEOPLE_id of speaker by last_name and EVENT_id
     *
     * @param string $name 
     * @return void
     * @author Carter Schoenfeld
     */
    private function getSpeakerId($name){
        return trim($name);
        
    }
    
    private function cleanUpSchedule(){
        
        $timeShift = 0 ;
        for($i = 0; $i < count($this->schedule[$this->scheduleDay]); $i++){
            if($this->schedule[$this->scheduleDay][$i]['endTime'] == $this->schedule[$this->scheduleDay][$i + 1]['startTime']){continue;}
            if($i == count($this->schedule[$this->scheduleDay]) - 1){continue;}
            
            if($this->schedule[$this->scheduleDay][$i]['scheduleType'] == 'Break' && 
            $this->schedule[$this->scheduleDay][$i + 1]['scheduleType'] == 'Break'){
                $this->removeBreak($i);
                $this->cleanupSchedule();
                break;
            }
            
            $timeShift = $this->calcDuration($this->schedule[$this->scheduleDay][$i]['endTime'], $this->schedule[$this->scheduleDay][$i + 1]['startTime']);
            
            // $this->adjustScheduleTimes($i + 1, $timeShift);
        }
    }
    
    private function removeBreak($i){
        if($this->schedule[$this->scheduleDay][$i]['duration'] < $this->schedule[$this->scheduleDay][$i + 1]['duration']){
            unset($this->schedule[$this->scheduleDay][$i]);
        } else {
            unset($this->schedule[$this->scheduleDay][$i + 1]);
        }
    } 
    
    private function adjustScheduleTimes($idx, $time){
        
        for($i = $idx; $i < count($this->schedule[$this->scheduleDay]); $i++){
            $start = date('Y-m-d H:i:s', strtotime($this->schedule[$this->scheduleDay][$i]['startTime'] . ' + ' . $time. ' minutes'));
            $end = date('Y-m-d H:i:s', strtotime($this->schedule[$this->scheduleDay][$i]['endTime'] . ' + ' . $time. ' minutes'));
            $this->schedule[$this->scheduleDay][$i]['startTime'] =  $start;
            $this->schedule[$this->scheduleDay][$i]['endTime'] = $end;
        }
    }  
    

}


?>