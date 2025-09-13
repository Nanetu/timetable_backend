<?php

class Course
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function addCourse($code, $name)
    {
        $this->db->query("INSERT INTO course(course_code, course_name) VALUES (:code, :name)");
        $this->db->bind(':code', $code);
        $this->db->bind(':name', $name);
        $this->db->execute();
    }

    public function getCourseByCode($code)
    {
        $this->db->query("SELECT course_name FROM course WHERE course_code = :code");
        $this->db->bind(':code', $code);
        $this->db->execute();
        return $this->db->result();
    }

    public function getVersion($code)
    {
        $this->db->query("SELECT course_version FROM course WHERE course_code = :code");
        $this->db->bind(':code', $code);
        $this->db->execute();
        return $this->db->result();
    }

    public function update($code, $name)
    {
        $this->db->query("UPDATE course SET course_name = :name WHERE course_code = :code");
        $this->db->bind(':code', $code);
        $this->db->bind(':name', $name);
        $this->db->execute();
    }

    public function delete($id)
    {
        $this->db->query("DELETE FROM course WHERE course_code = :id");
        $this->db->bind(':id', $id);
        $this->db->execute();
    }

    public function getCourseNames($courseCodes)
    {
        if (empty($courseCodes) || !is_array($courseCodes)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($courseCodes), '?'));
        $query = "SELECT course_code, course_name FROM course WHERE course_code IN ($placeholders)";
        $this->db->query($query);

        foreach ($courseCodes as $index => $code) {
            $this->db->bind(($index + 1), $code);
        }

        $this->db->execute();
        $results = $this->db->results();

        $names = [];
        foreach ($results as $row) {
            $names[$row['course_code']] = $row['course_name'];
        }
        return $names;
    }
}
