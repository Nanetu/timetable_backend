<?php

class Classroom{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function getAllClassrooms($sid){
        $this->db->query("SELECT * FROM classroom WHERE locked !=1 or school_id = :sid");
        $this->db->bind(':sid', $sid);
        $this->db->execute();
        return $this->db->results();
    }

    public function addClassroom($id, $sid, $capacity, $locked){
        $this->db->query("INSERT INTO classroom(room_id, school_id, capacity, locked) VALUES (:id, :sid, :capacity, :locked)");
        $this->db->bind(':id', $id);
        $this->db->bind(':sid', $sid);
        $this->db->bind(':capacity', $capacity);
        $this->db->bind('locked', $locked);
        $this->db->execute();
    }

    public function update($id, $sid, $capacity, $locked){
        $this->db->query("UPDATE classroom SET school_id:sid, capacity:capacity, locked:locked WHERE room_id:id");
        $this->db->bind(':id', $id);
        $this->db->bind(':sid', $sid);
        $this->db->bind(':capacity', $capacity);
        $this->db->bind(':locked', $locked);
        $this->db->execute();
    }

    public function classroomLock($sid, $val){
        $this->db->query("UPDATE classroom SET locked = :val WHERE school_id = :sid");
        $this->db->bind(':sid', $sid);
        $this->db->bind(':val', $val);
        $this->db->execute();
    }

    public function getClassroomByID($id){
        $this->db->query("SELECT * FROM classroom WHERE classroom_id = :id");
        $this->db->bind(':id', $id);
        $this->db->execute();
        return $this->db->result();
    }

    public function delete($id){
        $this->db->query("DELETE FROM classroom WHERE classroom_id = :id");
        $this->db->bind(':id', $id);
        $this->db->execute();
    }
}

?>