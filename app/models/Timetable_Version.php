<?php

class Timetable_Version{
    private $db;

    public function __construct()
    {
        //require_once 'app\core\Database.php';
        $this->db = new Database();
    }

    public function getAllChanges(){
        $this->db->query("SELECT * FROM timetable_version");
        $this->db->execute();
        return $this->db->results();
    }

    public function getVersion($user, $a_year, $pid, $year){  // Should I check is_active yet?
        $this->db->query("SELECT * FROM timetable_version
                            WHERE created_by = :user
                            AND academic_year = :a_year
                            AND program_id = :pid
                            AND year = :year
                            AND is_active = 1
                            ORDER BY created_at DESC
                            LIMIT 1;
                        ");
        $this->db->bind(':user', $user);
        $this->db->bind(':a_year', $a_year);
        $this->db->bind(':pid', $pid);
        $this->db->bind(':year', $year);
        $this->db->execute();
        return $this->db->result();
    }


    // modify add version to have year and program_id dont forget
    public function addVersion($a_year, $cb, $pvid, $pid, $year){
        $this->db->query("INSERT INTO timetable_version (academic_year, created_at, created_by, previous_version_id, is_active, program_id, year)
                            VALUES (:a_year, NOW(), :cb, :pvid, 1, :pid, :year);
                        ");
        $this->db->bind(':a_year', $a_year);
        $this->db->bind(':cb', $cb);
        $this->db->bind(':pvid', $pvid);
        $this->db->bind(':pid', $pid);
        $this->db->bind(':year', $year);
        $this->db->execute();
    }

    public function update($version, $ia){
        $this->db->query("UPDATE timetable_version SET is_active = :ia WHERE version_id = :version");
        $this->db->bind(":ia", $ia);
        $this->db->bind(':version', $version);
        $this->db->execute();
    }

    public function rollback($version_id) {
        $this->db->query("SELECT previous_version_id FROM timetable_version WHERE version_id = :version_id LIMIT 1");
        $this->db->bind(":version_id", $version_id);
        $this->db->execute();
        return $this->db->result();
}
}

?>