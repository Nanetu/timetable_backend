<?php

class Department{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function addDepartment($name, $sid){
        $this->db->query("INSERT INTO department(department_name, school_id) VALUES (:name, :sid)");
        $this->db->bind(':name', $name);
        $this->db->bind(':sid', $sid);
        $this->db->execute();
    }

    public function update($id, $name, $sid){
        $this->db->query("UPDATE department SET department_name = :name, school_id = :sid WHERE department_id = :id");
        $this->db->bind(':name', $name);
        $this->db->bind(':id', $id);
        $this->db->bind(':sid', $sid);
        $this->db->execute();
    }

    public function getDepartmentBySid($sid){
        $this->db->query("SELECT department_id FROM department WHERE school_id = :sid");
        $this->db->bind(':sid', $sid);
        $this->db->execute();
        return $this->db->results();
    }

    public function delete($id){
        $this->db->query("DELETE FROM department WHERE department_id = :id");
        $this->db->bind(':id', $id);
        $this->db->execute();
    }
}

?>