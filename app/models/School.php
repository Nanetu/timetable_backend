<?php

class School{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function getAllSchools($uid){
        $this->db->query("SELECT * FROM school WHERE university_id = :uid");
        $this->db->bind(':uid', $uid);
        $this->db->execute();
        return $this->db->results();
    }

    public function getSchoolByName($name){
        $this->db->query("SELECT school_id FROM school WHERE school_name = :name");
        $this->db->bind(':name', $name);
        $this->db->execute();
        return $this->db->result();
    }

    public function addSchool($name, $uid){
        $this->db->query("INSERT INTO school(school_name, university_id) VALUES (:name, :uid)");
        $this->db->bind(':name', $name);
        $this->db->bind(':uid', $uid);
        $this->db->execute();
    }

    public function update($id, $name, $uid){
        $this->db->query("UPDATE school SET school_name = :name, university_id = :uid WHERE school_id:id");
        $this->db->bind(':name', $name);
        $this->db->bind(':id', $id);
        $this->db->bind(':uid', $uid);
        $this->db->execute();
    }

    public function delete($id){
        $this->db->query("DELETE FROM school WHERE school_id:id");
        $this->db->bind(':id', $id);
        $this->db->execute();
    }
}

?>