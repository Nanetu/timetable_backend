<?php

class University{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function getAllUniversities(){
        $this->db->query("SELECT * FROM university");
        $this->db->execute();
        return $this->db->results();
    }

    public function addUniversity($name){
        $this->db->query("INSERT INTO university(university_name) VALUES (:name)");
        $this->db->bind(':name', $name);
        $this->db->execute();
    }

    public function update($id, $name){
        $this->db->query("UPDATE university SET university_name = :name WHERE university_id:id");
        $this->db->bind(':name', $name);
        $this->db->bind(':id', $id);
        $this->db->execute();
    }

    public function getUniversityByName($name){
        $this->db->query("SELECT university_id FROM university WHERE university_name = :name");
        $this->db->bind(':name', $name);
        $this->db->execute();
        return $this->db->result();
    }

    public function delete($id){
        $this->db->query("DELETE FROM university WHERE university_id = :id");
        $this->db->bind(':id', $id);
        $this->db->execute();
    }
}

?>