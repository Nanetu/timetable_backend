<?php
// App.php
class App {
    private $router;
    
    public function __construct() {
        $this->initializeApp();
        require_once 'Router.php';
        $this->router = new Router();
        $this->setupRoutes();
    }
    
    private function initializeApp() {
        // Set error reporting
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        // Set timezone
        date_default_timezone_set('UTC');
        
        // Start session if needed
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Include necessary files
        $this->autoloadClasses();
    }
    
    private function autoloadClasses() {
        // Basic autoloader for your classes
        spl_autoload_register(function ($className) {
            $paths = [
                'controllers/' . $className . '.php',
                'models/' . $className . '.php',
                'core/' . $className . '.php',
                'config/' . $className . '.php',
            ];
            
            foreach ($paths as $path) {
                if (file_exists($path)) {
                    require_once $path;
                    return;
                }
            }
        });
    }

    
    private function setupRoutes() {
        $this->router->setBasePath('/timetable_backend');

        // Auth Routs
        $this->router->addRoute('POST', '/', 'AuthController', 'login');
        $this->router->addRoute('POST', '/index', 'AuthController', 'login');
        $this->router->addRoute('POST', '', 'AuthController', 'login');
        $this->router->addRoute('POST', '/signup', 'AuthController', 'signup');
        $this->router->addRoute('POST', '/logout', 'AuthController', 'logout');

        // Timetable Routes
        $this->router->addRoute('GET', '/timetable', 'TimeslotController', 'view_timetable');
        $this->router->addRoute('POST', '/timetable', 'TimeslotController', 'view_timetable');
        $this->router->addRoute('POST', '/timetable/save', 'TimeslotController', 'saveEntries');
        $this->router->addRoute('GET', '/timetable/schools', 'TimeslotController', 'getSchools');
        $this->router->addRoute('GET', '/timetable/programs', 'TimeslotController', 'getPrograms');
        $this->router->addRoute('GET', '/timetable/courses', 'TimeslotController', 'getCourses');
        $this->router->addRoute('GET', '/timetable/lecturers', 'TimeslotController', 'getLecturers');
        $this->router->addRoute('GET', '/timetable/classes', 'TimeslotController', 'getClassrooms');
        $this->router->addRoute('GET', '/timetable/check_lock', 'TimeslotController', 'checkLock');
        $this->router->addRoute('GET', '/timetable/lock', 'TimeslotController', 'lockClassrooms');
        $this->router->addRoute('GET', '/timetable/release', 'TimeslotController', 'releaseClassrooms');

        // Admin Routes
        $this->router->addRoute('POST', '/add_class', 'AdminController', 'addClass');
        $this->router->addRoute('POST', '/add_course', 'AdminController', 'addCourse');
        $this->router->addRoute('POST', '/programs', 'AdminController', 'getAllPrograms');
        $this->router->addRoute('GET', '/courses', 'AdminController', 'fetchAllCourses');

        // Clash Detection Routes
        $this->router->addRoute('POST', '/clash_detect', 'DetectClash', 'checkClashes');
        $this->router->addRoute('POST', '/free_slots', 'SuggestedSlots', 'findSlots');
    }
    
    public function run() {
        try {
            $this->router->handleRequest();
        } catch (Exception $e) {
            // Handle errors gracefully
            if (ini_get('display_errors')) {
                echo "Application Error: " . $e->getMessage();
            } else {
                echo "An error occurred. Please try again later.";
            }
        }
    }
    
    // Helper method to include your database config
    public function loadConfig($configFile) {
        $configPath = "app/config/{$configFile}.php";
        if (file_exists($configPath)) {
            require_once $configPath;
        }
    }
}
?>