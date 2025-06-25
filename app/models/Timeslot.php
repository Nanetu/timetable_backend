<?php

class Timeslot{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function getTimeslot($sid){
        $this->db->query("SELECT day_of_week, start_time, end_time FROM timeslot WHERE slot_id = :sid");
        $this->db->bind(':sid', $sid);
        $this->db->execute();
        return $this->db->result();
    }

    public function addTimeslot($day, $start, $end){
        $this->db->query("INSERT INTO timeslot(day_of_week, start_time, end_time) VALUES (:day, :start, :end)");
        $this->db->bind(':day', $day);
        $this->db->bind(':start', $start);
        $this->db->bind(':end', $end);
        $this->db->execute();
    }

    public function update($id, $day, $start, $end){
        $this->db->query("UPDATE timeslot SET day_of_week = :day, start_time = :start, end_time = :end WHERE timeslot_id = :id");
        $this->db->bind(':day', $day);
        $this->db->bind(':id', $id);
        $this->db->bind(':start', $start);
        $this->db->bind(':end', $end);
        $this->db->execute();
    }

    public function getTimeslotByDse($day, $start, $end){
        $this->db->query("SELECT slot_id FROM timeslot WHERE day_of_week = :day AND start_time = :start AND end_time = :end");
        $this->db->bind(':day', $day);
        $this->db->bind(':start', $start);
        $this->db->bind(':end', $end);
        $this->db->execute();
        return $this->db->result();
    }

    public function delete($id){
        $this->db->query("DELETE FROM timeslot WHERE timeslot_id:id");
        $this->db->bind(':id', $id);
        $this->db->execute();
    }
}

?>