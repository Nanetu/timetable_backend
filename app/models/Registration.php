<?php

class Registration{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function getAllRegistrationCoursesForYear($user, $year){
        $this->db->query("SELECT course_code FROM registration WHERE student_id = :user and registration_date = :year");
        $this->db->bind(':user', $user);
        $this->db->bind(':year', $year);
        $this->db->execute();
        return $this->db->results();
    }

    public function addRegistration($sid, $code, $year){
        $this->db->query("INSERT INTO registration(student_id, course_code, registration_date) VALUES (:sid, :code, :year)");
        $this->db->bind(':sid', $sid);
        $this->db->bind(':code', $code);
        $this->db->bind(':year', $year);
        $this->db->execute();
    }

    public function update($id, $year, $code){
        $this->db->query("UPDATE registration SET registration_date = :year WHERE student_id = :id AND course_code = :code");
        $this->db->bind(':year', $year);
        $this->db->bind(':id', $id);
        $this->db->bind(':code', $code);
        $this->db->execute();
    }

    public function delete($id){
        $this->db->query("DELETE FROM registration WHERE registration_id:id");
        $this->db->bind(':id', $id);
        $this->db->execute();
    }
}

?>