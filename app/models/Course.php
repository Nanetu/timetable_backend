<?php

class Course{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function addCourse($version, $code, $name){
        $this->db->query("INSERT INTO course(course_version, course_code, course_name) VALUES (:version, :code, :name)");
        $this->db->bind(':version', $version);
        $this->db->bind(':code', $code);
        $this->db->bind(':name', $name);
        $this->db->execute();
    }

    public function update($code, $name){
        $this->db->query("UPDATE course SET course_name = :name WHERE course_code = :code");
        $this->db->bind(':code', $code);
        $this->db->bind(':name', $name);
        $this->db->execute();
    }

    public function delete($id){
        $this->db->query("DELETE FROM course WHERE course_code = :id");
        $this->db->bind(':id', $id);
        $this->db->execute();
    }
}


?>