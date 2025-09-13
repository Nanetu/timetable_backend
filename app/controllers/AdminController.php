<?php

class AdminController extends Controller
{
    private $userModel;
    private $classModel;
    private $courseModel;
    private $courseProgramModel;
    private $programModel;
    private $departmentModel;

    public function __construct()
    {
        $this->userModel = $this->loadModel("User");
        $this->classModel = $this->loadModel("Classroom");
        $this->courseModel = $this->loadModel("Course");
        $this->courseProgramModel = $this->loadModel("Course_Program");
        $this->programModel = $this->loadModel("Program");
        $this->departmentModel = $this->loadModel("Department");
    }

    public function getAllPrograms()
    {
        $this->setJsonHeaders();

        if (!isset($_SESSION['email'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unable to authenticate user']);
            return;
        }

        $email = $_SESSION['email'];
        $role = $this->userModel->getRole($email);

        if ($role['role'] != 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            return;
        }

        $programs = $this->programModel->getAllPrograms();

        echo json_encode([
            'status' => 'success',
            'programs' => $programs
        ]);
    }

    public function fetchAllCourses(){
    $this->setJsonHeaders();

    $email = $_SESSION['email'] ?? null;
    if (!$email) {
        http_response_code(401);
        echo json_encode(['error' => 'Unable to authenticate user']);
        return;
    }

    $role = $this->userModel->getRole($email);

    if ($role['role'] != 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        return;
    }

    $school = $this->userModel->getSchool($email);
    if (!$school) {
        http_response_code(400);
        echo json_encode(['error' => 'Unable to determine user school']);
        return;
    }

    $departments = $this->departmentModel->getDepartmentBySid($school['school_id']);
    $departmentIds = array_map(fn($dept) => $dept['department_id'], $departments);

    $programs = $this->programModel->getProgramsByDepartmentIds($departmentIds);
    $programIds = array_map(fn($prog) => $prog['program_id'], $programs);

    // Step 1: Get all courses for the school's programs
    $courses = $this->courseProgramModel->getAllCourses($programIds);
    $courseCodes = array_column($courses, 'course_code');

    // Step 2: For each course, get all programs sharing it (from any school)
    $allSharedCourses = [];
    foreach ($courseCodes as $code) {
        $sharedPrograms = $this->courseProgramModel->getProgramsByCourseCode($code);

        foreach ($sharedPrograms as $sp) {
            // create a unique key per program_id + year
            $key = $sp['program_id'] . '-' . $sp['year'];

            if (!isset($allSharedCourses[$code])) {
                $allSharedCourses[$code] = [
                    'course_code' => $code,
                    'programs' => []
                ];
            }

            if (!isset($allSharedCourses[$code]['programs'][$key])) {
                $allSharedCourses[$code]['programs'][$key] = [
                    'program_id'   => $sp['program_id'],
                    'program_name' => $sp['program_name'],
                    'year'         => $sp['year']
                ];
            }
        }
    }

    // Add course names + finalize program list
    $courseNames = $this->courseModel->getCourseNames($courseCodes);
    foreach ($allSharedCourses as $code => &$data) {
        $data['course_name'] = $courseNames[$code] ?? 'Unknown';
        // reset programs to a simple array (remove associative keys)
        $data['programs'] = array_values($data['programs']);
        $data['shared'] = count($data['programs']) > 1 ? 'yes' : 'no';
    }

    echo json_encode([
        'status' => 'success',
        'courses' => array_values($allSharedCourses)
    ]);
}


    public function addClass()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
        }

        if (!isset($_SESSION['email'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unable to authenticate user']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $class = $data['id'] ?? null;
        $capacity = $data['capacity'] ?? null;
        $locked = $data['locked'] ?? 0;

        if (!$class || !$capacity) {
            http_response_code(400);
            echo json_encode(['error' => 'School ID, class, and capacity are required']);
            return;
        }

        if ($capacity != is_numeric($capacity) || $capacity <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Capacity must be a positive number']);
            return;
        }

        $email = $_SESSION['email'] ?? null;
        if (!$email) {
            http_response_code(401);
            echo json_encode(['error' => 'Unable to authenticate user']);
            return;
        }

        $role = $this->userModel->getRole($email);

        if ($role['role'] != 'admin') {
            http_response_code(401);
            echo json_encode(['error' => 'Acess denied']);
            return;
        }

        $school = $this->userModel->getSchool($email);
        $school_id = $school['school_id'];

        $this->classModel->addClassroom($class, $school_id, $capacity, $locked);
        $classrooms = $this->classModel->getClassrooms();

        echo json_encode([
            'status' => 'success',
            'classrooms' => $classrooms
        ]);
    }

    public function addCourse()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        if (!isset($_SESSION['email'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unable to authenticate user']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $programs = $data['programs'] ?? null;
        $course = $data['course'] ?? null;
        $code = $data['code'] ?? null;

        if (!$programs || !$course || !$code) {
            http_response_code(400);
            echo json_encode(['error' => 'Course name, code and programs are required']);
            return;
        }

        $existingCourse = $this->courseModel->getCourseByCode($code);
        if ($existingCourse) {
            http_response_code(400);
            echo json_encode(['error' => 'Course with this code already exists']);
            return;
        }

        $this->courseModel->addCourse($code, $course);
        $version = $this->courseModel->getVersion($code);


        foreach ($programs as $program => $year) {
            $program_id = $this->programModel->getProgramByName($program);
            $this->courseProgramModel->addCourseProgram($code, $program_id['program_id'], $year, $version['course_version']);
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Course added successfully'
        ]);
    }

    public function deleteClass() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        if (!isset($_SESSION['email'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unable to authenticate user']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $class = $data['class'] ?? null;

        $email = $_SESSION['email'] ?? null;

        $role = $this->userModel->getRole($email);

        if ($role['role'] != 'admin') {
            http_response_code(401);
            echo json_encode(['error' => 'Acess denied']);
            return;
        }

        $school = $this->userModel->getSchool($email);
        $admin_school_id = $school['school_id'];

        $school_id = $this->classModel->getSchoolId($class);
        $school_id = $school_id['school_id'];

        if ($school_id != $admin_school_id) {
            echo json_encode([
                'status' => 'error',
                'message' => 'You do not have permission to edit this class'
            ]);
            return;
        }

        $this->classModel->delete($class);

        $classrooms = $this->classModel->getClassrooms();

        echo json_encode([
            'status' => 'success',
            'classrooms' => $classrooms
        ]);
    }

    function deleteCourse() {
         if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        if (!isset($_SESSION['email'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unable to authenticate user']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $course = $data['course'] ?? null;

        $this->courseModel->delete($course);

        echo json_encode([
            'status' => 'success',
            'message' => 'Course deleted successfully'
        ]);
    }

    public function editCourse(){
         if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        if (!isset($_SESSION['email'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unable to authenticate user']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $course = $data['code'] ?? null;
        $name = $data['name'] ?? null;
        $programs = $data['programs'] ?? null;

        if(!$course){
            http_response_code(400);
            echo json_encode(['error' => 'Course code']);
            return;
        }
        if($name){
            $this->courseModel->update($course, $name);
        }

        if(!$programs){
            return;
        }

        foreach($programs as $program=>$year){
            $program_id = $this->programModel->getProgramByName($program);
            $this->courseProgramModel->update($course, $program_id['program_id'], $year);
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Course updated successfully'
        ]);
    }

    public function editClass(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        if (!isset($_SESSION['email'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unable to authenticate user']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $class_id = $data['id'] ?? null;
        $capacity = $data['capacity'] ?? null;

        if (!$class_id || !$capacity) {
            http_response_code(400);
            echo json_encode(['error' => 'Class ID and capacity are required']);
            return;
        }

        if (!is_numeric($capacity) || $capacity <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Capacity must be a positive number']);
            return;
        }

        $email = $_SESSION['email'] ?? null;
        if (!$email) {
            http_response_code(401);
            echo json_encode(['error' => 'Unable to authenticate user']);
            return;
        }

        $role = $this->userModel->getRole($email);

        if ($role['role'] != 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            return;
        }

        
        $school = $this->userModel->getSchool($email);
        $admin_school_id = $school['school_id'];

        $school_id = $this->classModel->getSchoolId($class_id);
        $school_id = $school_id['school_id'];

        if ($school_id != $admin_school_id) {
            echo json_encode([
                'status' => 'error',
                'message' => 'You do not have permission to edit this class'
            ]);
            return;
        }

        $this->classModel->updateCapacity($class_id, $capacity);

        $classrooms = $this->classModel->getClassrooms();

        echo json_encode([
            'status' => 'success',
            'message' => 'Classroom capacity updated successfully',
            'classrooms' => $classrooms
        ]);
    }

}