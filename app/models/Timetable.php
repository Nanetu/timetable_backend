<?php

class Timetable{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function getTimetableByPidYear($pid, $year, $version){
        $this->db->query("SELECT t.table_id, t.course_code, t.slot_id, t.room_id, t.lecturer_id FROM timetable t JOIN timetable_version v ON t.program_id = v.program_id WHERE t.program_id = :pid AND v.year = :year AND t.version_id = :version");
        $this->db->bind(':pid', $pid);
        $this->db->bind(':year', $year);
        $this->db->bind(':version', $version);
        $this->db->execute();
        return $this->db->results();
    }

    public function getSlotsByLid($lid){
        $this->db->query("SELECT t.slot_id FROM timetable t JOIN timetable_version v ON t.version_id = v.version_id WHERE t.lecturer_id = :lid AND v.is_active = 1");
        $this->db->bind(':lid', $lid);
        $this->db->execute();
        return $this->db->results();
    }

    public function getSlotsByRoom($room_id){
        $this->db->query("SELECT t.slot_id FROM timetable t JOIN timetable_version v ON t.version_id = v.version_id WHERE t.room_id = :room_id AND v.is_active = 1");
        $this->db->bind(':room_id', $room_id);
        $this->db->execute();
        return $this->db->results();
    }

    public function addTimetable($code, $pid, $lid, $room, $slot, $version){
        $this->db->query("INSERT INTO timetable(course_code, program_id, lecturer_id, room_id, slot_id, version_id) VALUES (:code, :pid, :lid, :room, :slot, :version)");
        $this->db->bind(':code', $code);
        $this->db->bind(':pid', $pid);
        $this->db->bind(':lid', $lid);
        $this->db->bind(':room', $room);
        $this->db->bind(':slot', $slot);
        $this->db->bind(':version', $version);
        $this->db->execute();
    }

    public function update($id, $code, $pid, $lid, $room, $slot, $version){
        $this->db->query("UPDATE timetable SET course_code = :code, program_id = :pid, lecturer_id = :lid, room_id = :room, slot_id = :slot, version_id = :version WHERE timetable_id = :id");
        $this->db->bind(':code', $code);
        $this->db->bind(':id', $id);
        $this->db->bind(':room', $room);
        $this->db->bind(':pid', $pid);
        $this->db->bind(':slot', $slot);
        $this->db->bind(':version', $version);
        $this->db->bind(':lid', $lid);
        $this->db->execute();
    }

    public function getTimetableByCourseCode($code){
        $this->db->query("SELECT *
            FROM timetable t
            JOIN timetable_version v ON t.version_id = v.version_id
            WHERE t.course_code = :code
            AND v.is_active = 1
        ");
        $this->db->bind(':code', $code);
        $this->db->execute();
        return $this->db->results();
    }

    public function getTimetableByLid($lid){
        $this->db->query("SELECT * FROM timetable t
            JOIN timetable_version v ON t.version_id = v.version_id
            WHERE t.lecturer_id = :lid
            AND v.is_active = 1
        ");
        $this->db->bind(':lid', $lid);
        $this->db->execute();
        return $this->db->results();
    }

    public function getElementsForRoomClash($room_id, $slot){
        $this->db->query("SELECT * 
            FROM timetable t
            JOIN timetable_version v ON t.version_id = v.version_id
            WHERE t.room_id = :room_id 
            AND t.slot_id = :slot
            AND v.is_active = 1
        ");
        $this->db->bind(':room_id', $room_id);
        $this->db->bind(':slot', $slot);
        $this->db->execute();
        return $this->db->results();
    }

    public function getElementsForLecturerClash($lid, $slot){
        $this->db->query("SELECT * 
            FROM timetable t
            JOIN timetable_version v ON t.version_id = v.version_id
            WHERE t.lecturer_id = :lid 
            AND t.slot_id = :slot
            AND v.is_active = 1
        ");
        $this->db->bind(':lid', $lid);
        $this->db->bind(':slot', $slot);
        $this->db->execute();
        return $this->db->results();
    }

}

/*
public function delete($code, $lid, $room, $slot, $version, $id){
        $this->db->query("SELECT table_id FROM timetable WHERE course_code = :code AND lecturer_id = :lid AND room_id = :room AND slot_id = :slot AND version_id = :version");
        $this->db->bind(':code', $code);
        $this->db->bind(':room', $room);
        $this->db->bind(':slot', $slot);
        $this->db->bind(':version', $version);
        $this->db->execute();
        $table_id = $this->db->result();

        $this->db->query("UPDATE timetable SET is_delete = :id WHERE table_id = :table_id");
        $this->db->bind(':id', $id);
        $this->db->execute();
    }
*/

?>

