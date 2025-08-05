<?php

class Course_Program{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function getCourseByPidYear($pid, $year){
        $this->db->query("SELECT course_code FROM course_program WHERE program_id = :pid and year = :year");
        $this->db->bind(':pid', $pid);
        $this->db->bind(':year', $year);
        $this->db->execute();
        return $this->db->results();
    }

    public function getCourseByYear($code, $pid){
        $this->db->query("SELECT year FROM course WHERE course_code = :code AND program_id = :pid");
        $this->db->bind(':code', $code);
        $this->db->bind(':pid', $pid);
        $this->db->execute();
        return $this->db->results();
    }

    public function addCourseProgram($code, $pid, $year, $version){
        $this->db->query("INSERT INTO course(course_code, program_id, year, course_version) VALUES (:code, :pid, :year, :version)");
        $this->db->bind(':code', $code);
        $this->db->bind(':pid', $pid);
        $this->db->bind(':year', $year);
        $this->db->bind(':version', $version);
        $this->db->execute();
    }

    public function delete($id){
        $this->db->query("DELETE FROM course WHERE course_code = :id");
        $this->db->bind(':id', $id);
        $this->db->execute();
    }

    public function getCourseVersionsByProgramYear($program_id, $year) {
        $this->db->query("SELECT course_version FROM course_program WHERE program_id = :program_id AND year = :year");
        $this->db->bind(':program_id', $program_id);
        $this->db->bind(':year', $year);
        $this->db->execute();
        return $this->db->results();
    }

    public function getCourseByCode($code) {
        $this->db->query("SELECT course_version FROM course WHERE course_code = :code");
        $this->db->bind(':code', $code);
        $this->db->execute();
        return $this->db->result();
    }
}

?>