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

    public function getSetting($key) {
        $stmt = $this->db->prepare("SELECT setting_value FROM settings WHERE setting_key = :key");
        $stmt->execute(['key' => $key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : null;
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

    public function getLecturerUnavailability($lecturer_id) {
        $stmt = $this->db->prepare("SELECT day_of_week, time_slot_id FROM lecturer_unavailability WHERE lecturer_id = :lecturer_id");
        $stmt->execute(['lecturer_id' => $lecturer_id]);
        return $stmt->fetchAll();
    }

    public function setLecturerUnavailability($lecturer_id, $day_of_week, $time_slot_id) {
        $stmt = $this->db->prepare("INSERT INTO lecturer_unavailability (lecturer_id, day_of_week, time_slot_id) VALUES (:lid, :day, :tid) ON CONFLICT DO NOTHING");
        return $stmt->execute(['lid' => $lecturer_id, 'day' => $day_of_week, 'tid' => $time_slot_id]);
    }

    public function clearLecturerUnavailability($lecturer_id) {
        $stmt = $this->db->prepare("DELETE FROM lecturer_unavailability WHERE lecturer_id = :lecturer_id");
        return $stmt->execute(['lecturer_id' => $lecturer_id]);
    }

    /**
     * COURSES
     */
    public function getCourses() {
        $query = "
            SELECT c.*, p.name as program_name, l.name as level_name, lec.name as lecturer_name 
            FROM courses c
            LEFT JOIN programs p ON c.program_id = p.id
            LEFT JOIN levels l ON c.level_id = l.id
            LEFT JOIN lecturers lec ON c.lecturer_id = lec.id
            ORDER BY c.code ASC
        ";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll();
    }

    public function addCourse($code, $title, $duration, $is_practical, $students, $program_id, $level_id, $lecturer_id) {
        $stmt = $this->db->prepare("
            INSERT INTO courses (code, title, duration_hours, is_practical, students_count, program_id, level_id, lecturer_id) 
            VALUES (:code, :title, :duration, :is_prac, :students, :pid, :lid, :lecid) RETURNING id
        ");
        $stmt->execute([
            'code' => $code, 'title' => $title, 'duration' => $duration, 
            'is_prac' => $is_practical ? 'true' : 'false', 'students' => $students, 
            'pid' => $program_id, 'lid' => $level_id, 'lecid' => $lecturer_id ?: null
        ]);
        return $stmt->fetchColumn();
    }

    public function deleteCourse($id) {
        $stmt = $this->db->prepare("DELETE FROM courses WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * TIMETABLE
     */
    public function getTimetable() {
        $query = "
            SELECT t.id, t.day_of_week, t.time_slot_id, t.is_locked,
                   c.code as course_code, c.title as course_title, c.duration_hours,
                   r.name as room_name, r.capacity as room_capacity,
                   l.name as lecturer_name,
                   lev.name as level_name
            FROM timetable t
            JOIN courses c ON t.course_id = c.id
            JOIN rooms r ON t.room_id = r.id
            LEFT JOIN lecturers l ON c.lecturer_id = l.id
            LEFT JOIN levels lev ON c.level_id = lev.id
            ORDER BY t.day_of_week, t.time_slot_id
        ";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll();
    }

    public function clearUnlockedTimetable() {
        // Clears generated slots that haven't been manually pinned/locked by the admin
        return $this->db->query("DELETE FROM timetable WHERE is_locked = false");
    }
}
?>
