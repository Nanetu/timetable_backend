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

    public function fetchAllCourses()
    {
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

        // ⚡️ Need to adjust this model so it returns:
        // [
        //   course_code => 'CS101',
        //   program_id  => 5,
        //   year        => 1
        // ]
        $courses = $this->courseProgramModel->getAllCourses($programIds);

        // ⚡️ Need to adjust courseModel->getCourseNames to fetch full details by codes
        $courseNames = $this->courseModel->getCourseNames(array_column($courses, 'course_code'));

        // Group courses by course_code
        $grouped = [];
        foreach ($courses as $c) {
            $code = $c['course_code'];

            if (!isset($grouped[$code])) {
                $grouped[$code] = [
                    'course_code' => $code,
                    'course_name' => $courseNames[$code] ?? 'Unknown',
                    'shared'      => 'no', // default
                    'programs'    => []
                ];
            }

            $grouped[$code]['programs'][] = [
                'program_id' => $c['program_id'],
                'year'       => $c['year']
            ];

            // If a course belongs to more than one program/year → mark as shared
            if (count($grouped[$code]['programs']) > 1) {
                $grouped[$code]['shared'] = 'yes';
            }
        }

        echo json_encode([
            'status' => 'success',
            'courses' => array_values($grouped)
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
        $classrooms = $this->classModel->getAllClassrooms($school_id);

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
        $programs = $data['programs'] ?? null; // expected: [program_id => year, ...]
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

        $this->courseModel->addCourse($course, $code);
        $version = $this->courseModel->getVersion($code);

        foreach ($programs as $program_id => $year) {
            $this->courseProgramModel->addCourseProgram($code, $program_id, $year, $version);
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Course added successfully'
        ]);
    }
}
