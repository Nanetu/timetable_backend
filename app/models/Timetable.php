<?php

class Timetable{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function getTimetableByPidYear($pid, $year, $version){
        $this->db->query("SELECT t.table_id, t.course_code, t.slot_id, t.room_id, t.lecturer_id, t.event_id 
                        FROM timetable t 
                        JOIN timetable_version v 
                        ON t.version_id = v.version_id 
                        WHERE t.program_id = :pid 
                        AND v.year = :year 
                        AND t.version_id = :version");
        $this->db->bind(':pid', $pid);
        $this->db->bind(':year', $year);
        $this->db->bind(':version', $version);
        $this->db->execute();
        return $this->db->results();
    }

    public function getSlotsByLid($lid){
        $this->db->query("SELECT t.slot_id, t.event_id FROM timetable t JOIN timetable_version v ON t.version_id = v.version_id WHERE t.lecturer_id = :lid AND v.is_active = 1");
        $this->db->bind(':lid', $lid);
        $this->db->execute();
        return $this->db->results();
    }

    public function getSlotsByRoom($room_id){
        $this->db->query("SELECT t.slot_id, t.event_id FROM timetable t JOIN timetable_version v ON t.version_id = v.version_id WHERE t.room_id = :room_id AND v.is_active = 1");
        $this->db->bind(':room_id', $room_id);
        $this->db->execute();
        return $this->db->results();
    }

    public function addTimetable($code, $cv, $lid, $room, $slot, $version, $event){
        $this->db->query("INSERT INTO timetable(course_code, course_version, lecturer_id, room_id, slot_id, version_id, event_id) VALUES (:code, :cv, :lid, :room, :slot, :version, :event_id)");
        $this->db->bind(':code', $code);
        $this->db->bind(':cv', $cv);
        $this->db->bind(':lid', $lid);
        $this->db->bind(':room', $room);
        $this->db->bind(':slot', $slot);
        $this->db->bind(':version', $version);
        $this->db->bind(':event_id', $event);
        $this->db->execute();
    }

    public function update($id, $code, $pid, $lid, $room, $slot, $version, $event){
        $this->db->query("UPDATE timetable SET course_code = :code, program_id = :pid, lecturer_id = :lid, room_id = :room, slot_id = :slot, version_id = :version, event_id = :event_id WHERE timetable_id = :id");
        $this->db->bind(':code', $code);
        $this->db->bind(':id', $id);
        $this->db->bind(':room', $room);
        $this->db->bind(':pid', $pid);
        $this->db->bind(':slot', $slot);
        $this->db->bind(':version', $version);
        $this->db->bind(':lid', $lid);
        $this->db->bind(':event_id', $event);
        $this->db->execute();
    }

    public function getTimetableByCourseCode($code){
        $this->db->query("SELECT t.*
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
        $this->db->query("SELECT t.* FROM timetable t
            JOIN timetable_version v ON t.version_id = v.version_id
            WHERE t.lecturer_id = :lid
            AND v.is_active = 1
        ");
        $this->db->bind(':lid', $lid);
        $this->db->execute();
        return $this->db->results();
    }

    public function getElementsForRoomClash($room_id, $slot){
        $this->db->query("SELECT t.* 
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
        $this->db->query("SELECT t.*
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

    public function getTimetableByCourseVersions($course_versions) {
        if (empty($course_versions)) return [];
        $placeholders = implode(',', array_fill(0, count($course_versions), '?'));
        $sql = "SELECT t.*, v.is_active FROM timetable t JOIN timetable_version v ON t.version_id = v.version_id WHERE t.course_version IN ($placeholders) AND v.is_active = 1";
        $this->db->query($sql);
        foreach ($course_versions as $i => $cv) {
            $this->db->bind(($i+1), $cv);
        }
        $this->db->execute();
        return $this->db->results();
    }

    public function getTimetableByCourseVersionsAndVersion($course_versions, $version_id) {
        if (empty($course_versions)) return [];
        $placeholders = implode(',', array_fill(0, count($course_versions), '?'));
        $sql = "SELECT * FROM timetable 
                WHERE version_id = ? AND course_version IN ($placeholders)";
        
        $this->db->query($sql);
        $this->db->bind(1, $version_id);
        foreach ($course_versions as $i => $cv) {
            $this->db->bind($i + 2, $cv);
        }
        $this->db->execute();
        return $this->db->results();
    }

    public function deleteTimetableById($id) {
        $this->db->query("DELETE FROM timetable WHERE timetable_id = :id");
        $this->db->bind(':id', $id);
        $this->db->execute();
    }

}

?>

