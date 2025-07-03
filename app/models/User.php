<?php

class User{
    private $db;

    public function __construct()
    {
        require_once 'app\core\Database.php';
        $this->db = new Database();
    }

    public function getUser($email){
        $this->db->query("SELECT * FROM user WHERE email = :email");
        $this->db->bind(':email', $email);
        $this->db->execute();
        return $this->db->result();
    }

    public function getUserName($uid){
        $this->db->query("SELECT name FROM user WHERE user_id = :uid");
        $this->db->bind(':uid', $uid);
        $this->db->execute();
        return $this->db->result();
    }

    public function getUserByName($name){
        $this->db->query("SELECT user_id FROM user WHERE name = :name");
        $this->db->bind(':name', $name);
        $this->db->execute();
        return $this->db->result();
    }

    public function getRole($email){
        $this->db->query("SELECT * FROM user WHERE email = :email");
        $this->db->bind(':email', $email);
        $this->db->execute();
        return $this->db->result();
    }

    public function getUsersByRole($role){
        $this->db->query("SELECT user_id, email FROM user WHERE role = :role");
        $this->db->bind(':role', $role);
        $this->db->execute();
        return $this->db->results();
    }

    public function getAdminsForMail($role){
        $this->db->query("SELECT name, email FROM user WHERE role = :role");
        $this->db->bind(':role', $role);
        $this->db->execute();
        return $this->db->results();
    }

    public function getSchool($email){
        $this->db->query("SELECT school_id FROM user WHERE email = :email");
        $this->db->bind(':email', $email);
        $this->db->execute();
        return $this->db->result();
    }

    public function getAdminBySid($school_id){
        $this->db->query("SELECT user_id FROM user WHERE school_id = :school_id AND role = 'admin'");
        $this->db->bind(':school_id', $school_id);
        $this->db->execute();
        return $this->db->result();
    }

    public function getLecturerBySid($school_id){
        $this->db->query("SELECT user_id, name FROM user WHERE school_id = :school_id AND role = 'lecturer'");
        $this->db->bind(':school_id', $school_id);
        $this->db->execute();
        return $this->db->results();
    }

    public function addUser($name, $password, $email, $sid, $role){
        $this->db->query("INSERT INTO user(name, password, email, school_id, role) VALUES (:name, :password, :email, :sid, :role)");
        $this->db->bind(':name', $name);
        $this->db->bind(':password', $password);
        $this->db->bind(':email', $email);
        $this->db->bind(':sid', $sid);
        $this->db->bind(':role', $role);
        $this->db->execute();
    }

    public function updateName($uid, $name){
        $this->db->query("UPDATE user SET name = :name WHERE user_id = :uid");
        $this->db->bind(':uid', $uid);
        $this->db->bind(':name', $name);
        $this->db->execute();
    }

    public function updateEmail($uid, $email){
        $this->db->query("UPDATE user SET email = :email WHERE user_id = :uid");
        $this->db->bind(':uid', $uid);
        $this->db->bind(':email', $email);
        $this->db->execute();
    }

    public function updateRole($email, $role){
        $this->db->query("UPDATE user SET role = :role WHERE email = :email");
        $this->db->bind(':email', $email);
        $this->db->bind(':role', $role);
        $this->db->execute();
    }

    public function forgotPassword($newpass, $user){
        $this->db->query("UPDATE user SET password = :newpass WHERE user_id = :user");
        $this->db->bind(':newpass', $newpass);
        $this->db->bind(':user', $user);
        $this->db->execute();
    }

    public function delete($uid){
        $this->db->query("DELETE * FROM user WHERE user_id:uid");
        $this->db->bind(':uid', $uid);
        $this->db->execute();
    }
}

?>