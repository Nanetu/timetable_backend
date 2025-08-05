<?php

class AdminController extends Controller{
    private $userModel;
    private $classModel;
    private $courseModel;
    private $courseProgramModel;

    public function __construct(){
        $this->userModel = $this->loadModel("User");
        $this->classModel = $this->loadModel("Classroom");
        $this->courseModel = $this->loadModel("Course");
        $this->courseProgramModel = $this->loadModel("Course_Program");
    }

    public function getPendingUsers(){

        $this->setJsonHeaders();

        $email = $_SESSION['email'] ?? null;
        if (!$email) {
            http_response_code(401);
            echo json_encode(['error' => 'Unable to authenticate user']);
            return;
        }

        $role = $this->userModel->getRole($email);

        if($role['role'] != 'admin'){
            http_response_code(401);
            echo json_encode(['error'=>'Acess denied']);
            return;
        }

        $pending = $this->userModel->getUsersByRole('pending');
        echo json_encode([
            'status'=>'success',
            'users'=>$pending
        ]);
    }

    public function addRoles(){
        if($_SERVER['REQUEST_METHOD'] !== 'POST' ){
            http_response_code(405);
            echo json_encode(['error'=>'Method not allowed']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        $email = $_SESSION['email'];
        if (!$email){
            http_response_code(400);
            echo json_encode(['error'=>'Unable to authenticate user']);
            return;
        }

        $user = $input['userId'] ?? null;
        $role = $input['role'] ?? null;

        if (!$user || !$role){
            http_response_code(400);
            echo json_encode(['error'=>'User ID and role are required']);
            return;
        }

        $this->userModel->updateRole($user, $role);

        echo json_encode(['status' => 'success']);
    }

    public function addClass(){
        if($_SERVER['REQUEST_METHOD'] !== 'POST'){
            http_response_code(405);
            echo json_encode(['error'=>'Method not allowed']);
        }

        if(!isset($_SESSION['email'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unable to authenticate user']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $school_id = $data['school_id'] ?? null;
        $class = $data['class'] ?? null;
        $capacity = $data['capacity'] ?? null;
        $locked = $data['locked'] ?? 0;

        if (!$school_id || !$class || !$capacity) {
            http_response_code(400);
            echo json_encode(['error' => 'School ID, class, and capacity are required']);
            return;
        }

        if($capacity != is_numeric($capacity) || $capacity <= 0){
            http_response_code(400);
            echo json_encode(['error' => 'Capacity must be a positive number']);
            return;
        }

        $this->classModel->addClassroom($class, $school_id, $capacity, $locked);
        $classrooms = $this->classModel->getAllClassrooms($school_id);

        echo json_encode([
            'status' => 'success',
            'classrooms' => $classrooms
        ]);
    }

    public function addCourse(){
        if($_SERVER['REQUEST_METHOD'] !== 'POST'){
            http_response_code(405);
            echo json_encode(['error'=>'Method not allowed']);
            return;
        }

        if(!isset($_SESSION['email'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unable to authenticate user']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $programs = $data['programs'] ?? null;
        $course = $data['course'] ?? null;
        $code = $data['code'] ?? null;

        if (!$programs || !$course) {
            http_response_code(400);
            echo json_encode(['error' => 'School ID and course are required']);
            return;
        }

        $existingCourse = $this->courseModel->getCourseByCode($code);
        if ($existingCourse) {
            http_response_code(400);
            echo json_encode(['error' => 'Course with this code already exists']);
            return;
        }

        $this->courseModel->addCourse($course, $code);

        $version = $this->courseModel->getVersion($code);

        foreach($programs as $program_id=>$year) {
            $this->courseProgramModel->addCourseProgram($code, $program_id, $year, $version);
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Course added successfully'
        ]);
    }

}

?>
