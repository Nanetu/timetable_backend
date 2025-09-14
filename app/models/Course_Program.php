
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

    public function getProgramsByCourseCode($courseCode) {
        $this->db->query("SELECT cp.program_id, cp.year, p.program_name
                          FROM course_program cp
                          JOIN program p ON cp.program_id = p.program_id
                          WHERE cp.course_code = :code");
        $this->db->bind(':code', $courseCode);
        $this->db->execute();
        return $this->db->results();
    }

    public function getCoursesByProgramIds($programIds) {
        if (empty($programIds) || !is_array($programIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($programIds), '?'));
        $query = "SELECT c.course_code, c.course_name, cp.program_id, cp.year
                FROM course_program cp
                JOIN course c ON cp.course_code = c.course_code
                WHERE cp.program_id IN ($placeholders)";
        $this->db->query($query);

        foreach ($programIds as $index => $pid) {
            $this->db->bind(($index + 1), $pid);
        }

        $this->db->execute();
        return $this->db->results();
    }

    public function addCourseProgram($code, $pid, $year, $version){
        $this->db->query("INSERT INTO course_program(course_code, program_id, year, course_version) VALUES (:code, :pid, :year, :version)");
        $this->db->bind(':code', $code);
        $this->db->bind(':pid', $pid);
        $this->db->bind(':year', $year);
        $this->db->bind(':version', $version);
        $this->db->execute();
    }

    public function updateYear($code, $pid, $year){
        $this->db->query("UPDATE course_program SET year = :year WHERE course_code = :code AND program_id = :pid");
        $this->db->bind(':code', $code);
        $this->db->bind(':pid', $pid);
        $this->db->bind(':year', $year);
        $this->db->execute();
    }

    public function delete($id){
        $this->db->query("DELETE FROM course WHERE course_code = :id");
        $this->db->bind(':id', $id);
        $this->db->execute();
    }

    public function deletePYPairs($code, $pid, $year){
        $this->db->query("DELETE FROM course_program WHERE course_code = :code AND program_id = :pid AND year = :year");
        $this->db->bind(':code', $code);
        $this->db->bind(':pid', $pid);
        $this->db->bind(':year', $year);
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

   public function getAllCourses($programIds) {
    if (empty($programIds) || !is_array($programIds)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($programIds), '?'));
    $query = "SELECT DISTINCT course_code, program_id, year FROM course_program WHERE program_id IN ($placeholders)";
    $this->db->query($query);

    foreach ($programIds as $index => $pid) {
        $this->db->bind(($index + 1), $pid);
    }

    $this->db->execute();
    return $this->db->results();

    }

    public function update($code, $pid, $year){
        $this->db->query("UPDATE course_program SET program_id = :pid, year = :year WHERE course_code = :code");
        $this->db->bind(':code', $code);
        $this->db->bind(':pid', $pid);
        $this->db->bind(':year', $year);
        $this->db->execute();
    }
}

?>