<?php
require_once __DIR__ . '/config.php';

class TimetableEngine {
    private $db;
    
    private $courses = [];
    private $rooms = [];
    private $timeSlots = [];
    private $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    private $coursePrograms = []; // New: Holds array of program IDs per course
    private $lecturerUnavailability = []; 
    
    private $roomMatrix = [];       
    private $lecturerMatrix = [];   
    private $programLevelMatrix = []; // Upgraded: Multi-dimensional collision detection    
    
    private $dayLoad = []; // NEW: Tracks how busy each day is for intelligent load balancing
    
    private $placements = [];

    public function __construct() {
        $this->db = getDBConnection();
    }

    public function generate() {
        try {
            $this->db->beginTransaction();

            // 1. Clear previously generated unlocked DRAFT timetable slots 
            $this->db->exec("DELETE FROM timetable WHERE is_locked = FALSE AND status = 'Draft'");

            // 2. Load all necessary data into memory
            $this->loadData();

            // 3. Sort courses to optimize placement (Longest duration first, then largest class)
            usort($this->courses, function($a, $b) {
                if ($a['duration_hours'] == $b['duration_hours']) {
                    return $b['students_count'] <=> $a['students_count'];
                }
                return $b['duration_hours'] <=> $a['duration_hours'];
            });

            $unplacedCourses = [];

            // 4. Attempt to place each course
            foreach ($this->courses as $course) {
                if (!$this->scheduleCourse($course)) {
                    $unplacedCourses[] = $course;
                }
            }

            // 5. Persist the successfully scheduled courses
            $this->savePlacements();

            $this->db->commit();
            
            return [
                'success' => true, 
                'message' => 'Draft Timetable generated successfully.',
                'unplaced' => $unplacedCourses 
            ];
        } catch (\Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function loadData() {
        $stmt = $this->db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'allow_saturdays'");
        $stmt->execute();
        if ($stmt->fetchColumn() === 'true') $this->days[] = 'Saturday';

        // Initialize day load tracking
        foreach ($this->days as $day) {
            $this->dayLoad[$day] = 0;
        }

        $this->timeSlots = $this->db->query("SELECT * FROM time_slots ORDER BY order_index ASC")->fetchAll();
        $this->rooms = $this->db->query("SELECT * FROM rooms ORDER BY capacity ASC")->fetchAll();
        $this->courses = $this->db->query("SELECT * FROM courses")->fetchAll();

        // Load Cross-Disciplinary Tags
        $cp = $this->db->query("SELECT course_id, program_id FROM course_programs")->fetchAll();
        foreach ($cp as $row) {
            $this->coursePrograms[$row['course_id']][] = $row['program_id'];
        }

        $unavailability = $this->db->query("SELECT lecturer_id, day_of_week, time_slot_id FROM lecturer_unavailability")->fetchAll();
        foreach ($unavailability as $u) {
            $this->lecturerUnavailability[$u['lecturer_id']][$u['day_of_week']][$u['time_slot_id']] = true;
        }

        $this->roomMatrix = [];
        $this->lecturerMatrix = [];
        $this->programLevelMatrix = [];
        $this->placements = [];
        
        // Re-populate matrices with locked OR published slots
        $stmt = $this->db->query("SELECT t.course_id, t.room_id, t.day_of_week, t.time_slot_id, c.lecturer_id, c.level_id 
                                  FROM timetable t 
                                  JOIN courses c ON t.course_id = c.id 
                                  WHERE t.is_locked = TRUE OR t.status = 'Published'");
        $lockedSlots = $stmt->fetchAll();
        
        foreach ($lockedSlots as $slot) {
            $cid = $slot['course_id'];
            $day = $slot['day_of_week'];
            $tid = $slot['time_slot_id'];

            $this->roomMatrix[$slot['room_id']][$day][$tid] = $cid;
            if ($slot['lecturer_id']) $this->lecturerMatrix[$slot['lecturer_id']][$day][$tid] = $cid;
            
            // Register locked slot across ALL associated programs
            $pids = $this->coursePrograms[$cid] ?? [];
            foreach($pids as $pid) {
                $this->programLevelMatrix[$pid][$slot['level_id']][$day][$tid] = $cid;
            }

            // Record this slot against the day's load
            $this->dayLoad[$day]++;
        }
    }

    private function scheduleCourse($course) {
        $duration = (int) $course['duration_hours'];
        $students = (int) $course['students_count'];
        $isPractical = filter_var($course['is_practical'], FILTER_VALIDATE_BOOLEAN);

        // 1. INTELLIGENT LOAD BALANCING: Sort days by least busy first
        $sortedDays = $this->days;
        usort($sortedDays, function($a, $b) {
            return $this->dayLoad[$a] <=> $this->dayLoad[$b];
        });

        // 2. BEST-FIT ROOM ALLOCATION: Filter valid rooms and sort by capacity (smallest valid first)
        $validRooms = [];
        foreach ($this->rooms as $room) {
            if ($room['capacity'] < $students) continue; 
            $isLab = in_array($room['room_type'], ['Laboratory', 'Computer Lab']);
            if ($isPractical && !$isLab) continue;
            if (!$isPractical && $isLab) continue;
            $validRooms[] = $room;
        }
        
        usort($validRooms, function($a, $b) {
            return $a['capacity'] <=> $b['capacity'];
        });

        // 3. ATTEMPT PLACEMENT
        foreach ($sortedDays as $day) {
            foreach ($validRooms as $room) {
                $totalSlots = count($this->timeSlots);
                for ($startIndex = 0; $startIndex <= $totalSlots - $duration; $startIndex++) {
                    if ($this->canPlaceAt($course, $room, $day, $startIndex, $duration)) {
                        $this->commitPlacement($course, $room, $day, $startIndex, $duration);
                        return true;
                    }
                }
            }
        }
        return false; 
    }

    private function canPlaceAt($course, $room, $day, $startIndex, $duration) {
        $lecturerId = $course['lecturer_id'];
        $levelId = $course['level_id'];
        $pids = $this->coursePrograms[$course['id']] ?? [];
        $consecutiveLecturerHours = 0;
        
        if ($lecturerId) {
            for ($i = $startIndex - 1; $i >= 0; $i--) {
                $prevSlotId = $this->timeSlots[$i]['id'];
                if (isset($this->lecturerMatrix[$lecturerId][$day][$prevSlotId])) $consecutiveLecturerHours++;
                else break;
            }
        }

        for ($i = 0; $i < $duration; $i++) {
            $slotId = $this->timeSlots[$startIndex + $i]['id'];

            if (filter_var($this->timeSlots[$startIndex + $i]['is_break'], FILTER_VALIDATE_BOOLEAN)) return false;
            if (isset($this->roomMatrix[$room['id']][$day][$slotId])) return false;

            // Cross-Disciplinary Check: Ensure no clash for THIS level across ALL tagged programs
            foreach ($pids as $pid) {
                if (isset($this->programLevelMatrix[$pid][$levelId][$day][$slotId])) return false;
            }

            if ($lecturerId) {
                if (isset($this->lecturerMatrix[$lecturerId][$day][$slotId])) return false;
                if (isset($this->lecturerUnavailability[$lecturerId][$day][$slotId])) return false;
                $consecutiveLecturerHours++;
            }
        }
        
        if ($lecturerId) {
            for ($i = $startIndex + $duration; $i < count($this->timeSlots); $i++) {
                 $nextSlotId = $this->timeSlots[$i]['id'];
                 if (isset($this->lecturerMatrix[$lecturerId][$day][$nextSlotId])) $consecutiveLecturerHours++;
                 else break;
            }
            if ($consecutiveLecturerHours > 3) return false;
        }

        return true;
    }

    private function commitPlacement($course, $room, $day, $startIndex, $duration) {
        $lecturerId = $course['lecturer_id'];
        $levelId = $course['level_id'];
        $pids = $this->coursePrograms[$course['id']] ?? [];

        for ($i = 0; $i < $duration; $i++) {
            $slotId = $this->timeSlots[$startIndex + $i]['id'];

            $this->roomMatrix[$room['id']][$day][$slotId] = $course['id'];
            if ($lecturerId) $this->lecturerMatrix[$lecturerId][$day][$slotId] = $course['id'];
            
            // Map placement across all tagged programs
            foreach ($pids as $pid) {
                $this->programLevelMatrix[$pid][$levelId][$day][$slotId] = $course['id'];
            }

            // Increment load to ensure future courses get pushed to other days!
            $this->dayLoad[$day]++;

            $this->placements[] = [
                'course_id' => $course['id'],
                'room_id' => $room['id'],
                'day_of_week' => $day,
                'time_slot_id' => $slotId
            ];
        }
    }

    private function savePlacements() {
        if (empty($this->placements)) return;

        $stmt = $this->db->prepare("
            INSERT INTO timetable (course_id, room_id, day_of_week, time_slot_id, status) 
            VALUES (:course_id, :room_id, :day_of_week, :time_slot_id, 'Draft')
        ");

        foreach ($this->placements as $placement) {
            $stmt->execute([
                'course_id' => $placement['course_id'],
                'room_id' => $placement['room_id'],
                'day_of_week' => $placement['day_of_week'],
                'time_slot_id' => $placement['time_slot_id']
            ]);
        }
    }
}
?>
