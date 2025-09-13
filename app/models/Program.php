<?php

class Program{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function addProgram($name, $did){
        $this->db->query("INSERT INTO program(program_name, department_id) VALUES (:name, :did)");
        $this->db->bind(':name', $name);
        $this->db->bind(':did', $did);
        $this->db->execute();
    }

    public function getAllPrograms(){
        $this->db->query("SELECT program_id, program_name FROM program");
        $this->db->execute();
        return $this->db->results();
    }

    public function update($id, $name, $did){
        $this->db->query("UPDATE program SET program_name = :name, department_id = :did WHERE Program_id = :id");
        $this->db->bind(':name', $name);
        $this->db->bind(':id', $id);
        $this->db->bind(':did', $did);
        $this->db->execute();
    }

    public function getProgramByDid($did){
        $this->db->query("SELECT program_id, program_name FROM program WHERE department_id = :did");
        $this->db->bind(':did', $did);
        $this->db->execute();
        return $this->db->results();
    }

    public function getProgramByName($name){
        $this->db->query("SELECT program_id FROM program WHERE program_name = :name");
        $this->db->bind(':name', $name);
        $this->db->execute();
        return $this->db->result();
    }

    public function getProgramById($id){
        $this->db->query("SELECT program_name FROM program WHERE program_id = :id");
        $this->db->bind(':id', $id);
        $this->db->execute();
        return $this->db->result();
    }


    public function getProgramsByDepartmentIds($departmentIds) {
    if (empty($departmentIds) || !is_array($departmentIds)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($departmentIds), '?'));
    $query = "SELECT program_id, program_name FROM program WHERE department_id IN ($placeholders)";
    $this->db->query($query);

    foreach ($departmentIds as $index => $did) {
        $this->db->bind(($index + 1), $did);
    }

    $this->db->execute();
    return $this->db->results();
}

    public function delete($id){
        $this->db->query("DELETE FROM program WHERE program_id = :id");
        $this->db->bind(':id', $id);
        $this->db->execute();
    }
}
?>