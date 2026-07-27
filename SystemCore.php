<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
     * COURSES
     */
    public function getCourses() {
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
        // Smart check: Only start a new transaction if one isn't already running from the CSV importer
        $inTransaction = $this->db->inTransaction();
        if (!$inTransaction) {
            $this->db->beginTransaction();
        }
        
        try {
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

            if (!is_array($program_ids)) $program_ids = [$program_ids];
            $stmt_cp = $this->db->prepare("INSERT INTO course_programs (course_id, program_id) VALUES (?, ?)");
            foreach ($program_ids as $pid) {
                if ($pid) $stmt_cp->execute([$course_id, $pid]);
            }
            
            if (!$inTransaction) {
                $this->db->commit();
            }
            return $course_id;
        } catch (\Exception $e) {
            if (!$inTransaction) {
                $this->db->rollBack();
            }
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

    public function getSpaceUtilization() {
        $query = "
            SELECT r.name, COUNT(t.id) as used_slots
            FROM rooms r
            LEFT JOIN timetable t ON r.id = t.room_id
            GROUP BY r.id, r.name
            ORDER BY used_slots DESC
        ";
        return $this->db->query($query)->fetchAll();
    }

    public function updatePlacement($id, $room_id, $day_of_week, $time_slot_id) {
        $stmt = $this->db->prepare("UPDATE timetable SET room_id = :rid, day_of_week = :day, time_slot_id = :tid WHERE id = :id");
        return $stmt->execute(['rid' => $room_id, 'day' => $day_of_week, 'tid' => $time_slot_id, 'id' => $id]);
    }

    /**
     * BULK CSV IMPORT ENGINE (Upgraded for robustness and explicit errors)
     */
    private function findId($table, $column, $value) {
        if (!$value) return null;
        // Upgraded to ILIKE for case-insensitive matching
        $stmt = $this->db->prepare("SELECT id FROM $table WHERE $column ILIKE ? LIMIT 1");
        $stmt->execute([trim($value)]);
        return $stmt->fetchColumn();
    }

    public function processCSVImport($type, $tmpFile) {
        // PHP 8.1+ natively detects line endings, removing deprecated ini_set to prevent HTML warnings
        
        $handle = fopen($tmpFile, 'r');
        if (!$handle) throw new \Exception("Could not open the uploaded CSV file.");
        
        fgetcsv($handle); // Skip header row
        $successCount = 0;
        $rowNum = 1;

        $this->db->beginTransaction();
        try {
            while (($data = fgetcsv($handle)) !== FALSE) {
                $rowNum++;
                // Check if row is empty or parsed weirdly (e.g., semicolons instead of commas)
                if (empty(array_filter($data))) continue; 
                if (count($data) === 1 && strpos($data[0], ';') !== false) {
                    throw new \Exception("Delimiter Error: Your CSV is using semicolons instead of commas. Please save as a standard comma-separated CSV.");
                }

                if ($type === 'rooms') {
                    if (count($data) < 3) throw new \Exception("Row $rowNum: Expected at least 3 columns for Rooms (Name, Capacity, Type). Found " . count($data));
                    $this->addRoom(trim($data[0]), (int)$data[1], trim($data[2]));
                    $successCount++;
                } 
                elseif ($type === 'lecturers') {
                    if (count($data) < 1) throw new \Exception("Row $rowNum: Expected at least 1 column for Lecturers (Name).");
                    $this->addLecturer(trim($data[0]), trim($data[1] ?? ''));
                    $successCount++;
                }
                elseif ($type === 'courses') {
                    if (count($data) < 8) throw new \Exception("Row $rowNum: Expected 8 columns for Courses. Found " . count($data));
                    
                    $code = trim($data[0]);
                    $title = trim($data[1]);
                    $dur = (int)$data[2];
                    $prac = (int)$data[3] === 1;
                    $stu = (int)$data[4];
                    
                    // Smart Lookups with explicit errors if not found
                    $lvlId = $this->findId('levels', 'name', trim($data[5]));
                    if (!$lvlId) throw new \Exception("Row $rowNum ($code): Level '" . trim($data[5]) . "' not found in database.");

                    $lecId = null;
                    if (!empty(trim($data[6]))) {
                        $lecId = $this->findId('lecturers', 'name', trim($data[6]));
                        if (!$lecId) throw new \Exception("Row $rowNum ($code): Lecturer '" . trim($data[6]) . "' not found. Make sure you upload Lecturers first.");
                    }
                    
                    $progCodes = explode(',', trim($data[7] ?? ''));
                    $progIds = [];
                    foreach ($progCodes as $pcode) {
                        $pcode = trim($pcode);
                        if (empty($pcode)) continue;
                        $pid = $this->findId('programs', 'code', $pcode);
                        if ($pid) $progIds[] = $pid;
                        else throw new \Exception("Row $rowNum ($code): Program Code '$pcode' not found in database.");
                    }

                    if (!empty($progIds) && $lvlId) {
                        $this->addCourse($code, $title, $dur, $prac, $stu, $progIds, $lvlId, $lecId);
                        $successCount++;
                    }
                }
            }
            $this->db->commit();
            fclose($handle);
            return $successCount;
        } catch (\Exception $e) {
            $this->db->rollBack();
            fclose($handle);
            throw $e;
        }
    }
}
?>
