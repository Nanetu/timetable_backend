<?php

class AuthController extends Controller {
    private $userModel;
    private $schoolModel;

    public function __construct(){
        $this->userModel = $this->loadModel("User");
        $this->schoolModel = $this->loadModel("School");
    }

    public function signup(){
        if($_SERVER['REQUEST_METHOD'] !== 'POST'){
            http_response_code(405);
            echo json_encode(['error'=>'Method not allowed here']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        $email = $input['email'] ?? null;
        $name = $input['name'] ?? null;
        $password = $input['password'] ?? null;
        $confirm = $input['confirm_password'] ?? null;
        $school = $input['school'] ?? null;

        if(!$email){
            http_response_code(400);
            echo json_encode(['error'=>'Email is required']);
            return;
        } else if(!$password){
            http_response_code(400);
            echo json_encode(['error'=>'Password is required']);
            return;
        } else if(!$confirm){
            http_response_code(400);
            echo json_encode(['error'=>'Confirm password is required']);
            return;
        } else if(!$name){
            http_response_code(400);
            echo json_encode(['error'=>'Name is required']);
            return;
        } else if(!$school){
            http_response_code(400);
            echo json_encode(['error'=>'School is required']);
            return;
        } else if($password != $confirm){
            http_response_code(422);
            echo json_encode(['error'=>'Passwords do not match']);
            return;
        }

        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $school_id = $this->schoolModel->getSchoolByName($school);
            $school_id = $school_id['school_id'];

            $this->userModel->addUser($name, $hash, $email, $school_id);
            echo json_encode(['status'=>'Success, pending approval']);

            // now send notification to admin and redirect to login
        } catch(Exception $e){
            http_response_code(500);
            echo json_encode(['error'=> $e->getMessage()]);
        } 
    }

    public function login(){

        if($_SERVER['REQUEST_METHOD'] !== 'POST'){
            http_response_code(405);
            echo json_encode(['error'=>'Method not allowed here']);
            return;
        }

        $max_attempts = 3;
        $lockout_time = 900;
        $ip = $_SERVER['REMOTE_ADDR'];
        $current_time = time();

        if(!isset($_SESSION['login_attempts'])){
            $_SESSION['login_attempts'] = [];
        }

        $_SESSION['login_attempts'] = array_filter($_SESSION['login_attempts'], function($attempt) use ($current_time, $lockout_time){
            return ($current_time - $attempt['time']) < $lockout_time;
        });

        $ip_attempts = array_filter($_SESSION['login_attempts'], function($attempt) use($ip){
            return $attempt['ip'] === $ip;
        });

        if (count($ip_attempts) >= $max_attempts){
            $oldest_attempt = min(array_column($ip_attempts, 'time'));
            $time_remaining = $lockout_time - ($current_time-$oldest_attempt);

            http_response_code(429);
            echo json_encode([
                'error'=>'Too many login attempts. Try again in '.ceil($time_remaining/60).' minutes.'
            ]);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        $email = $input['email'] ?? null;
        $password = $input['password'] ?? null;

        if(!$email || !$password){
            http_response_code(400);
            echo json_encode(['error'=>'Cannot validate empty data']);
            return;
        }

        $role = $this->userModel->getRole($email);

        if ($role['role'] == 'pending') {
            echo json_encode(['error' => 'Your account is pending approval']);
            return;
        }


        try {
            $user = $this->userModel->getUser($email);

            if(!$user){
                $_SESSION['login_attempts'][] = [
                    'ip' => $ip,
                    'time' => $current_time,
                    'email' => $email
                ];
                http_response_code(401);
                echo json_encode(['error'=>'Invalid credentials']);
                return;
            }

            if(password_verify($password, $user['password'])){
                $_SESSION['email'] = $email;
                $_SESSION['role'] = $user['role'];


                echo json_encode([
                    'success' => true,
                    'user' => [
                        'email' => $email,
                        'name' => $user['name'],
                        'role' => $user['role']
                    ]
                ]);


                $_SESSION['login_attempts'] = array_filter($_SESSION['login_attempts'], function($attempt) use ($ip) {
                    return $attempt['ip'] !== $ip;
                });
            } else{
                http_response_code(401);
                echo json_encode(['error'=>'Invalid Credentials']);
            }

        } catch(Exception $e){
            http_response_code(500);
            echo json_encode(['error'=> $e->getMessage()]);
        }
    }

    public function logout(){
        try {

            $_SESSION = [];

            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(), 
                    '', 
                    time() - 42000,
                    $params["path"], 
                    $params["domain"],
                    $params["secure"], 
                    $params["httponly"]
                );
            }

            session_destroy();
            echo json_encode(['status'=>'success']);
        } catch(Exception $e){
            http_response_code(500);
            echo json_encode(['error'=>$e->getMessage()]);
        }
    }

}

?>