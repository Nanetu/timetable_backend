<?php
Class DetectClash extends Controller {
    private $userModel;
    private $tsModel;
    private $timetableModel;
    private $versionModel;

    public function __construct() {
        $this->userModel = $this->loadModel("User");
        $this->tsModel = $this->loadModel("Timeslot");
        $this->timetableModel = $this->loadModel("Timetable");
        $this->versionModel = $this->loadModel("Timetable_Version");
    }

    function compareTimes($arr, $entry){
        foreach ($arr as $slot) {
            $slot_id = $slot['slot_id'];
            $slot_details = $this->tsModel->getTimeslot($slot_id);
            if (!$slot_details) {
                continue;
            }

            // Check if the day_of_week and time range overlaps
            if ($slot_details['day_of_week'] == $entry['day_of_week']) {
                $existing_start = strtotime($slot_details['start_time']);
                $existing_end = strtotime($slot_details['end_time']);
                $new_start = strtotime($entry['start_time']);
                $new_end = strtotime($entry['end_time']);

                if (($new_start < $existing_end) && ($new_end > $existing_start)) {
                    // Check event_id
                    if ($slot['event_id'] == $entry['id']) {
                        // Same event, not a clash
                        continue;
                    }
                    return true;
                }
            }
        }
        return false;
    }

    function checkClashes(){

        if($_SERVER['REQUEST_METHOD'] !== 'POST' ){
            http_response_code(405);
            echo json_encode(['error'=>'Method not allowed']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        $email = $_SESSION['email'];
        if (!$email){
            http_response_code(400);
            echo json_encode(['error'=>'User not authenticated']);
            return;
        }

        $entry = $input['entry'] ?? null;

        if (!$entry){
            http_response_code(400);
            echo json_encode(['error'=>'No entry provided']);
            return;
        }

        $Lslost = $this->timetableModel->getSlotsByLid($entry['lecturer_id']);
        $Rslots = $this->timetableModel->getSlotsByRoom($entry['room_id']);
        $slots = array_merge($Lslost, $Rslots);
        if (empty($slots)){
            echo json_encode([
                'status'=>'success',
                'type'=>'none',
                'message'=>'No clashing events were found'  
            ]);
            return;
        }

        $Lclash = $this->compareTimes($Lslost, $entry);
        $Rclash = $this->compareTimes($Rslots, $entry);

        if ($Lclash && $Rclash){
            $message = 'Clash detected with both lecturer and room';
            $type = 'both';
        } else if ($Lclash) {
            $message = 'Clash detected with lecturer';
            $type = 'lecturer';
        } else if ($Rclash) {
            $message = 'Clash detected with room';
            $type = 'room';
        } else {
            echo json_encode([
                'status'=>'success',
                'type'=>'none',
                'message'=>'No clashing events were found'
            ]);
            return;
        }

        echo json_encode([
            'status' => 'failure',
            'type' => $type,
            'message' => $message
        ]);

    }
}

?>