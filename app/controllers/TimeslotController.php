<?php
class TimeslotController extends Controller {

    private $userModel;
    private $versionModel;
    private $timetableModel;
    private $tsModel;
    private $registrationModel;
    private $universityModel;
    private $schoolModel;
    private $programModel;
    private $departmentModel;
    private $courseModel;
    private $classModel;
    private $courseProgramModel;

    public function __construct() {
        
        $this->userModel = $this->loadModel("User");
        $this->versionModel = $this->loadModel("Timetable_Version");
        $this->timetableModel = $this->loadModel("Timetable");
        $this->tsModel = $this->loadModel("Timeslot");
        $this->registrationModel = $this->loadModel("Registration");
        $this->universityModel = $this->loadModel("University");
        $this->schoolModel = $this->loadModel("School");
        $this->programModel = $this->loadModel("Program");
        $this->departmentModel = $this->loadModel("Department");
        $this->courseModel = $this->loadModel("Course");
        $this->classModel = $this->loadModel("Classroom");
        $this->courseProgramModel = $this->loadModel("Course_Program");
    }

    public function determine_role($email){
        $role = $this->userModel->getRole($email);
        return $role['role'];
    }

    public function view_timetable(){

        $this->setJsonHeaders();

        $email = $_SESSION['email'] ?? null;
        if (!$email) {
            http_response_code(401);
            echo json_encode(['error' => 'User not authenticated']);
            return;
        }

        $program_name = $_GET['program'] ?? $_POST['program'] ?? null;
        $year = $_GET['year'] ?? $_POST['year'] ?? null;
        $rollback = $_GET['rollback'] ?? $_POST['rollback'] ?? 0;

        if ($year !== null) {
            $year = (int)$year;
        }

        if(!$email){
            http_response_code(400);
            echo json_encode(['error'=>'Email is required']);
            return;
        }

        $role = $this->determine_role($email);

        try {
            if ($role == 'admin' && $rollback == 0){
                $user_id = $this->userModel->getUser($email);
                $user_id  = $user_id['user_id'] ?? null;
                if (!$user_id) {
                    http_response_code(404);
                    echo json_encode(['error'=>'User not found']);
                    return;
                }
                $current_year = date('Y');
                
                $program_id = $this->programModel->getProgramByName($program_name);
                $program_id = $program_id['program_id'] ?? null;

                if (!$program_id) {
                    http_response_code(404);
                    echo json_encode(['error'=>'Program not found']);
                    return;
                }

                $version = $this->versionModel->getVersion($user_id, $current_year, $program_id, $year);
                if ($version){
                    $version = $version['version_id'];
                }

                if (!$version) {
                    http_response_code(404);
                    echo json_encode(['error'=>'Version not found']);
                    return;
                }

                $entries = $this->timetableModel->getTimetableByPidYear($program_id, $year, $version);

                foreach ($entries as &$entry) {
                    $slot_id = $entry['slot_id'];
                    $slot_data = $this->tsModel->getTimeslot($slot_id);

                    $lecturer_id = $entry['lecturer_id'];
                    $lecturer = $this->userModel->getUserName($lecturer_id);

                    $entry['start_time'] = $slot_data['start_time'];
                    $entry['end_time'] = $slot_data['end_time'];
                    $entry['day_of_week'] = $slot_data['day_of_week'];
                    $entry['lecturer_name'] = $lecturer['name'];

                    unset($entry['slot_id']);
                }
                
                echo json_encode([
                    'status' => 'success',
                    'role' => 'admin',
                    'entries' => $entries,
                ]);
            
            } else if ($role == 'admin' && $rollback == 1){
                $user_id = $this->userModel->getUser($email);
                $user_id  = $user_id['user_id'];
                $current_year = date('Y');
                
                $program_id = $this->programModel->getProgramByName($program_name);
                $program_id = $program_id['program_id'];

                $version = $this->versionModel->getVersion($user_id, $current_year, $program_id, $year);
                if ($version){
                    $version = $version['version_id'];
                }
                $previous_id = $this->versionModel->rollback($version);
                if ($previous_id){
                    $previous_id = $previous_id['previous_version_id'];
                }

                $entries = $this->timetableModel->getTimetableByPidYear($program_id, $year, $previous_id);

                foreach ($entries as &$entry) {
                    $slot_id = $entry['slot_id'];
                    $slot_data = $this->tsModel->getTimeslot($slot_id);

                    $lecturer_id = $entry['lecturer_id'];
                    $lecturer = $this->userModel->getUserName($lecturer_id);

                    // Append the slot data to the entry
                    $entry['start_time'] = $slot_data['start_time'];
                    $entry['end_time'] = $slot_data['end_time'];
                    $entry['day_of_week'] = $slot_data['day_of_week'];
                    $entry['lecturer_name'] = $lecturer['name'];

                    unset($entry['slot_id']);
                }

                
                echo json_encode([
                    'status' => 'success',
                    'role' => 'admin',
                    'entries' => $entries,
                ]);
                
            } else if ($role == 'lecturer') {
                $user_id = $this->userModel->getUser($email);
                $user_id  = $user_id['user_id'];
                $school = $this->userModel->getSchool($email);
                $school  = $school['school_id'];
                $admin = $this->userModel->getAdminBySid($school);
                $admin  = $admin['user_id'];
                $current_year = date('Y');
                $entries = $this->timetableModel->getTimetableByLid($user_id);

                foreach ($entries as &$entry) {
                    $slot_id = $entry['slot_id'];
                    $slot_data = $this->tsModel->getTimeslot($slot_id);

                    $lecturer_id = $entry['lecturer_id'];
                    $lecturer = $this->userModel->getUserName($lecturer_id);

                    // Append the slot data to the entry
                    $entry['start_time'] = $slot_data['start_time'];
                    $entry['end_time'] = $slot_data['end_time'];
                    $entry['day_of_week'] = $slot_data['day_of_week'];
                    $entry['lecturer_name'] = $lecturer['name'];

                    unset($entry['program_id'], $entry['slot_id'], $entry['lecturer_id'], $entry['version_id']);
                }
                
                echo json_encode([
                    'status' => 'success',
                    'role' => 'lecturer',
                    'entries' => $entries,
                ]);

            } else if ($role == 'student'){
                $user_id = $this->userModel->getUser($email);
                $user_id  = $user_id['user_id'];
                $school = $this->userModel->getSchool($email);
                $school  = $school['school_id'];
                $admin = $this->userModel->getAdminBySid($school);
                $admin  = $admin['user_id'];
                $current_year = date('Y');
                $courses = $this->registrationModel->getAllRegistrationCoursesForYear($user_id, $current_year);
                $entries = [];
                foreach ($courses as $course){
                    $temp = $this->timetableModel->getTimetableByCourseCode($course['course_code']);
                    $entries = array_merge($entries, $temp);
                }

                foreach ($entries as &$entry) {
                    $slot_id = $entry['slot_id'];
                    $slot_data = $this->tsModel->getTimeslot($slot_id);

                    $lecturer_id = $entry['lecturer_id'];
                    $lecturer = $this->userModel->getUserName($lecturer_id);

                    // Append the slot data to the entry
                    $entry['start_time'] = $slot_data['start_time'];
                    $entry['end_time'] = $slot_data['end_time'];
                    $entry['day_of_week'] = $slot_data['day_of_week'];
                    $entry['lecturer_name'] = $lecturer['name'];

                    unset($entry['program_id'], $entry['slot_id'], $entry['lecturer_id'], $entry['version_id']);
                }
                
                echo json_encode([
                    'status' => 'success',
                    'role' => 'student',
                    'entries' => $entries,
                ]);

            } else {
                http_response_code(404);
                echo json_encode(['error'=>'User not in database']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error'=>'Internal Server Error']);
        }
        

    }

    public function saveEntries(){

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

        $entries = $input['entries'] ?? null;

        if (!$entries){
            http_response_code(400);
            echo json_encode(['error'=>'Cannot save empty data']);
            return;
        }
            
        try {
            $user_id = $this->userModel->getUser($email);
            $current_year = date('Y');

            $program = $input['program_name'];
            $program_id = $this->programModel->getProgramByName($program);
            $program_id = $program_id['program_id'];

            $course_year = $input['year'];

            // Get previous version using user_id, program_id and course_year
            $previous = $this->versionModel->getVersion($user_id['user_id'], $current_year, $program_id, $course_year) ?? null;


            if ($previous) {
                $previous = $previous['version_id'];
                $this->versionModel->update($previous, 0);
            }

            // Add new version
            $this->versionModel->addVersion($current_year, $user_id['user_id'], $previous, $program_id, $course_year);
            

            // Immediately fetch it back
            $new_version = $this->versionModel->getVersion($user_id['user_id'], $current_year, $program_id, $course_year);
            if (!$new_version || !isset($new_version['version_id'])) {
                throw new Exception("Version not found after insert");
            }

            $new_version_id = $new_version['version_id'];

            // Insert timetable entries
            foreach ($entries as $entry) {
                $this->addEntry($entry['course_id'], $program_id, $entry['start_time'], $entry['end_time'], $entry['day_of_week'], $entry['lecturer_id'], $entry['room_id'], $new_version_id);
            }

            echo json_encode([
                'status' => 'success',
                'message' => 'Entries saved successfully',
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }

    }

    public function addEntry($code, $program_id, $start, $end, $day, $lecturer, $room, $version){
        $slot_id = $this->tsModel->getTimeslotByDse($day, $start, $end);
        if(!$slot_id){
            $this->tsModel->addTimeslot($day, $start, $end);
            $slot_id = $this->tsModel->getTimeslotByDse($day, $start, $end);
        }
        $this->timetableModel->addTimetable($code, $program_id, $lecturer, $room, $slot_id['slot_id'], $version);
    }

    public function getSchools(){

        $this->setJsonHeaders();

        $university = $_GET['university'] ?? $_POST['university'] ?? null;

        if(!$university){
            echo json_encode(['error' => 'No university provided']);
            return;
        }
        $university_id = $this->universityModel->getUniversityByName($university);
        $university_id = $university_id['university_id'];
        $schools = $this->schoolModel->getAllSchools($university_id);

        echo json_encode([
                'status' => 'success',
                'schools' => $schools,
        ]);
    }

    public function getPrograms(){

        $this->setJsonHeaders();

        $school = $_GET['school'] ?? $_POST['school'] ?? null;

        if(!$school){
            echo json_encode(['error' => 'No school provided']);
            return;
        }
        //$school_id = $this->schoolModel->getSchoolByName($school);
        //$school_id = $school_id['school_id'];
        $departments = $this->departmentModel->getDepartmentBySid($school);

        $programs = [];
        foreach ($departments as $department) {
            $department = $department['department_id'];
            $temp = $this->programModel->getProgramByDid($department);
            //$programs [] = $temp;
            $programs = array_merge($programs, $temp);
        }

        echo json_encode([
                'status' => 'success',
                'programs' => $programs,
        ]);
    }

    public function getCourses(){

        $this->setJsonHeaders();

        $program = $_GET['program'] ?? $_POST['program'] ?? null;
        $year = $_GET['year'] ?? $_POST['year'] ?? null;

        if(!$program || !$year){
            echo json_encode(['error' => 'no data recieved']);
            return;
        }

        //$program_id = $this->programModel->getProgramByName($program);
        //$program_id = $program_id['program_id'];
        $courses = $this->courseProgramModel->getCourseByPidYear($program, $year);

        echo json_encode([
            'status'=>'success',
            'courses'=>$courses,
        ]);
    }

    public function getLecturers(){

        $this->setJsonHeaders();

        $email = $_SESSION['email'] ?? null;
        if (!$email) {
            http_response_code(401);
            echo json_encode(['error' => 'User not authenticated']);
            return;
        }

        $school = $this->userModel->getSchool($email);
        $school = $school['school_id'];

        if(!$school){
            echo json_encode(['error' => 'No school provided']);
            return;
        }
        //$school_id = $this->schoolModel->getSchoolByName($school);
        //$school_id = $school_id['school_id'];
        $lecturers = $this->userModel->getLecturerBySid($school);

        echo json_encode([
                'status' => 'success',
                'lecturers' => $lecturers,
        ]);
    }

    public function getClassrooms(){
        $this->setJsonHeaders();

        $email = $_SESSION['email'] ?? null;
        if (!$email) {
            http_response_code(401);
            echo json_encode(['error' => 'User not authenticated']);
            return;
        }

        $school = $this->userModel->getSchool($email);
        $school = $school['school_id'];

        if(!$school){
            echo json_encode(['error' => 'No school provided']);
            return;
        }
        $school_id = $this->schoolModel->getSchoolByName($school);

        $classes = $this->classModel->getAllClassrooms($school_id);

        echo json_encode([
            'status'=>'success',
            'classes'=>$classes,
        ]);
    }

    public function lockClassrooms(){
        $this->setJsonHeaders();

        $email = $_SESSION['email'] ?? null;
        if (!$email) {
            http_response_code(401);
            echo json_encode(['error' => 'User not authenticated']);
            return;
        }

        $school = $_GET['school'] ?? $_POST['school'] ?? null;

        if(!$school){
            echo json_encode(['error' => 'No data provided']);
            return;
        }
        $school_id = $this->schoolModel->getSchoolByName($school);

        $this->classModel->classroomLock($school_id, 1);
        echo json_encode(['status'=>'success']);

        $result = sendMail(
            $email, 
            "mushongomananetu@gmail.com", 
            "Nanetu", 
            "Classroom lock", 
            $school, 
            "All classes belonging to the $school have been locked"
        );

        if ($result['success']) {
            echo $result['message'];
        } else {
            echo "Error: " . $result['message'];
        }

        /*
        $recipients = $this->userModel->getAdminsForMail('admin');

        foreach($recipients as $recipient){
            if($recipient['email'] == $email) continue;
            sendMail(
                    $email, 
                    $recipient['email'], 
                    $recipient['name'],
                    "Classroom lock",
                    $school,
                    "All classes belonging to the $school have been locked"
                );
        }

        */
    }

    public function releaseClassrooms(){
        $this->setJsonHeaders();

        $email = $_SESSION['email'] ?? null;
        if (!$email) {
            http_response_code(401);
            echo json_encode(['error' => 'User not authenticated']);
            return;
        }

        $school = $_GET['school'] ?? $_POST['school'] ?? null;

        if(!$school){
            echo json_encode(['error' => 'No data provided']);
            return;
        }
        $school_id = $this->schoolModel->getSchoolByName($school);

        $this->classModel->classroomLock($school_id, 0);

        echo json_encode(['status'=>'success']);

        $result = sendMail(
            $email, 
            "mushongomananetu@gmail.com", 
            "Nanetu", 
            "Classroom lock", 
            $school, 
            "All classes belonging to the $school have been locked"
        );

        if ($result['success']) {
            echo $result['message'];
        } else {
            echo "Error: " . $result['message'];
        }

        /*
        $recipients = $this->userModel->getAdminsForMail('admin');

        foreach($recipients as $recipient){
            if($recipient['email'] == $email) continue;
            sendMail(
                    $email, 
                    $recipient['email'], 
                    $recipient['name'],
                    "Classroom lock",
                    $school,
                    "All classes belonging to the $school have been unlocked"
                );
        }
        */
    }
}
?>