<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Require the database configuration
require_once __DIR__ . '/config.php';

class SystemCore {
    private $db;

    public function __construct() {
        $this->db = getDBConnection();
    }

    /**
     * AUTHENTICATION
     */
    public function login($username, $password) {
        $stmt = $this->db->prepare("SELECT id, password_hash FROM users WHERE username = :username LIMIT 1");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['username'] = $username;
            return true;
        }
        return false;
    }

    public function logout() {
        session_unset();
        session_destroy();
    }

    public function isLoggedIn() {
        return isset($_SESSION['admin_id']);
    }

    public function requireAuth() {
        if (!$this->isLoggedIn()) {
            header("Location: login.php");
            exit;
        }
    }

    /**
     * SETTINGS
     */
    public function getSettings() {
        $stmt = $this->db->query("SELECT setting_key, setting_value FROM settings");
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }

    public function updateSetting($key, $value) {
        $stmt = $this->db->prepare("UPDATE settings SET setting_value = :value WHERE setting_key = :key");
        return $stmt->execute(['value' => $value, 'key' => $key]);
    }

    /**
     * TIME SLOTS, PROGRAMS, LEVELS
     */
    public function getTimeSlots() {
        $stmt = $this->db->query("SELECT * FROM time_slots ORDER BY order_index ASC");
        return $stmt->fetchAll();
    }

    public function getPrograms() {
        $stmt = $this->db->query("SELECT * FROM programs ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    public function getLevels() {
        $stmt = $this->db->query("SELECT * FROM levels ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    /**
     * ROOMS
     */
    public function getRooms() {
        $stmt = $this->db->query("SELECT * FROM rooms ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    public function addRoom($name, $capacity, $room_type) {
        $stmt = $this->db->prepare("INSERT INTO rooms (name, capacity, room_type) VALUES (:name, :capacity, :room_type) RETURNING id");
        $stmt->execute(['name' => $name, 'capacity' => $capacity, 'room_type' => $room_type]);
        return $stmt->fetchColumn();
    }

    public function updateRoom($id, $name, $capacity, $room_type) {
        $stmt = $this->db->prepare("UPDATE rooms SET name = :name, capacity = :capacity, room_type = :room_type WHERE id = :id");
        return $stmt->execute(['name' => $name, 'capacity' => $capacity, 'room_type' => $room_type, 'id' => $id]);
    }

    public function deleteRoom($id) {
        $stmt = $this->db->prepare("DELETE FROM rooms WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * LECTURERS
     */
    public function getLecturers() {
        $stmt = $this->db->query("SELECT * FROM lecturers ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    public function addLecturer($name, $email) {
        $stmt = $this->db->prepare("INSERT INTO lecturers (name, email) VALUES (:name, :email) RETURNING id");
        $stmt->execute(['name' => $name, 'email' => $email]);
        return $stmt->fetchColumn();
    }

    public function updateLecturer($id, $name, $email) {
        $stmt = $this->db->prepare("UPDATE lecturers SET name = :name, email = :email WHERE id = :id");
        return $stmt->execute(['name' => $name, 'email' => $email, 'id' => $id]);
    }

    public function deleteLecturer($id) {
        $stmt = $this->db->prepare("DELETE FROM lecturers WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * COURSES (Upgraded for Cross-Disciplinary Tagging)
     */
    public function getCourses() {
        // Updated to aggregate multiple programs into a single string for the UI
        $query = "
            SELECT c.*, l.name as level_name, lec.name as lecturer_name, 
                   STRING_AGG(p.code, ', ') as program_codes
            FROM courses c
            LEFT JOIN levels l ON c.level_id = l.id
            LEFT JOIN lecturers lec ON c.lecturer_id = lec.id
            LEFT JOIN course_programs cp ON c.id = cp.course_id
            LEFT JOIN programs p ON cp.program_id = p.id
            GROUP BY c.id, l.name, lec.name
            ORDER BY c.code ASC
        ";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll();
    }

    public function addCourse($code, $title, $duration, $is_practical, $students, $program_ids, $level_id, $lecturer_id) {
        $this->db->beginTransaction();
        try {
            // 1. Insert Course (program_id removed from base table)
            $stmt = $this->db->prepare("
                INSERT INTO courses (code, title, duration_hours, is_practical, students_count, level_id, lecturer_id) 
                VALUES (:code, :title, :duration, :is_prac, :students, :lid, :lecid) RETURNING id
            ");
            $stmt->execute([
                'code' => $code, 'title' => $title, 'duration' => $duration, 
                'is_prac' => $is_practical ? 'true' : 'false', 'students' => $students, 
                'lid' => $level_id, 'lecid' => $lecturer_id ?: null
            ]);
            $course_id = $stmt->fetchColumn();

            // 2. Insert Cross-Disciplinary Tags safely (Fallback for Phase 1 API compatibility)
            if (!is_array($program_ids)) $program_ids = [$program_ids];
            
            $stmt_cp = $this->db->prepare("INSERT INTO course_programs (course_id, program_id) VALUES (?, ?)");
            foreach ($program_ids as $pid) {
                if ($pid) $stmt_cp->execute([$course_id, $pid]);
            }

            $this->db->commit();
            return $course_id;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function deleteCourse($id) {
        $stmt = $this->db->prepare("DELETE FROM courses WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * TIMETABLE & DRAFT MANAGEMENT
     */
    public function getTimetable($status = null) {
        $query = "
            SELECT t.id, t.day_of_week, t.time_slot_id, t.is_locked, t.status,
                   c.code as course_code, c.title as course_title, c.duration_hours,
                   r.name as room_name, r.capacity as room_capacity,
                   l.name as lecturer_name,
                   lev.name as level_name
            FROM timetable t
            JOIN courses c ON t.course_id = c.id
            JOIN rooms r ON t.room_id = r.id
            LEFT JOIN lecturers l ON c.lecturer_id = l.id
            LEFT JOIN levels lev ON c.level_id = lev.id
            " . ($status ? "WHERE t.status = :status" : "") . "
            ORDER BY t.day_of_week, t.time_slot_id
        ";
        
        $stmt = $this->db->prepare($query);
        if ($status) $stmt->execute(['status' => $status]);
        else $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function publishTimetable() {
        return $this->db->query("UPDATE timetable SET status = 'Published' WHERE status = 'Draft'");
    }

    public function clearUnlockedTimetable() {
        return $this->db->query("DELETE FROM timetable WHERE is_locked = FALSE");
    }

    /**
     * ANALYTICS (Prep for Phase 2/3)
     */
    public function getLecturerWorkload() {
        $query = "
            SELECT l.name, SUM(c.duration_hours) as total_hours 
            FROM timetable t
            JOIN courses c ON t.course_id = c.id
            JOIN lecturers l ON c.lecturer_id = l.id
            GROUP BY l.id, l.name
            ORDER BY total_hours DESC
        ";
        return $this->db->query($query)->fetchAll();
    }
}
?>
