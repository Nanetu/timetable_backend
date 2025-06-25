<?php

class AdminController extends Controller{
    private $userModel;

    public function __construct(){
        $this->userModel = $this->loadModel("User");
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

        $entries = $input['entries'] ?? null;

        if (!$entries){
            http_response_code(400);
            echo json_encode(['error'=>'Cannot save empty data']);
            return;
        }

        foreach($entries as $entry){
            $this->userModel->updateRole($entry['email'], $entry['role']);
        }
        echo json_encode(['status' => 'success']);
    }

}

?>
