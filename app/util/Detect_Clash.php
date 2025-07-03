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

        $slot_id = $this->tsModel->getTimeslotByDse($entry['day'], $entry['start'], $entry['end']);

        if (!$slot_id){
            echo json_encode([
                'status'=>'success',
                'type'=>'none',
                'message'=>'No clashing events were found'
            ]);
            return;
        }
        $slot_id = $slot_id['slot_id'];

        $test_room = $this->timetableModel->getElementsForRoomClash($entry['room_id'], $slot_id);
        $test_lecturer = $this->timetableModel->getElementsForLecturerClash($entry['lecturer_id'], $slot_id);
        if(!$test_room && !$test_lecturer){
            echo json_encode([
                'status'=>'success',
                'type'=>'none',
                'message'=>'No clashing events were found'
            ]);
        } else if(!$test_room && $test_lecturer){
            echo json_encode([
                'status'=>'failure',
                'type'=>'lecturer',
                'message'=>'The selected lecturer already has a session during this timeslot.'
            ]);
        } else if($test_room && !$test_lecturer){
            echo json_encode([
                'status'=>'failure',
                'type'=>'classroom',
                'message'=>'The selected classroom already has a session during this timeslot.'
            ]);
        } else {
            echo json_encode([
                'status'=>'failure',
                'type'=>'both',
                'message'=>'Clash detected for both the lecturer and classroom in that timeslot'
            ]);
        }

    }
}

?>