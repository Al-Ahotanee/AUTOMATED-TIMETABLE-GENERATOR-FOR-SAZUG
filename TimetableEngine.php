<?php
require_once __DIR__ . '/config.php';

class TimetableEngine {
    private $db;
    
    private $courses = [];
    private $rooms = [];
    private $timeSlots = [];
    private $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    private $lecturerUnavailability = []; 
    
    private $roomMatrix = [];       
    private $lecturerMatrix = [];   
    private $levelMatrix = [];      
    
    private $placements = [];

    public function __construct() {
        $this->db = getDBConnection();
    }

    public function generate() {
        try {
            $this->db->beginTransaction();

            // 1. Clear previously generated unlocked timetable slots (Uppercase FALSE for strict Postgres compatibility)
            $this->db->exec("DELETE FROM timetable WHERE is_locked = FALSE");

            // 2. Load all necessary data into memory
            $this->loadData();

            // 3. Sort courses to optimize placement (Hardest to place first: Longest duration, then largest class)
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
                'message' => 'Timetable generated successfully.',
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
        $allowSaturdays = $stmt->fetchColumn();
        if ($allowSaturdays === 'true') {
            $this->days[] = 'Saturday';
        }

        $stmt = $this->db->query("SELECT * FROM time_slots ORDER BY order_index ASC");
        $this->timeSlots = $stmt->fetchAll();

        $stmt = $this->db->query("SELECT * FROM rooms ORDER BY capacity ASC");
        $this->rooms = $stmt->fetchAll();

        $stmt = $this->db->query("SELECT * FROM courses");
        $this->courses = $stmt->fetchAll();

        $stmt = $this->db->query("SELECT lecturer_id, day_of_week, time_slot_id FROM lecturer_unavailability");
        $unavailability = $stmt->fetchAll();
        foreach ($unavailability as $u) {
            $this->lecturerUnavailability[$u['lecturer_id']][$u['day_of_week']][$u['time_slot_id']] = true;
        }

        $this->roomMatrix = [];
        $this->lecturerMatrix = [];
        $this->levelMatrix = [];
        $this->placements = [];
        
        // Re-populate matrices with locked timetable slots (Uppercase TRUE)
        $stmt = $this->db->query("SELECT t.course_id, t.room_id, t.day_of_week, t.time_slot_id, c.lecturer_id, c.level_id 
                                  FROM timetable t 
                                  JOIN courses c ON t.course_id = c.id 
                                  WHERE t.is_locked = TRUE");
        $lockedSlots = $stmt->fetchAll();
        
        foreach ($lockedSlots as $slot) {
            $this->roomMatrix[$slot['room_id']][$slot['day_of_week']][$slot['time_slot_id']] = $slot['course_id'];
            $this->levelMatrix[$slot['level_id']][$slot['day_of_week']][$slot['time_slot_id']] = $slot['course_id'];
            if ($slot['lecturer_id']) {
                $this->lecturerMatrix[$slot['lecturer_id']][$slot['day_of_week']][$slot['time_slot_id']] = $slot['course_id'];
            }
        }
    }

    private function scheduleCourse($course) {
        $duration = (int) $course['duration_hours'];
        $students = (int) $course['students_count'];
        $isPractical = filter_var($course['is_practical'], FILTER_VALIDATE_BOOLEAN);

        foreach ($this->days as $day) {
            foreach ($this->rooms as $room) {
                if ($room['capacity'] < $students) continue; 

                $isLab = in_array($room['room_type'], ['Laboratory', 'Computer Lab']);
                if ($isPractical && !$isLab) continue;
                if (!$isPractical && $isLab) continue;

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
        $consecutiveLecturerHours = 0;
        
        if ($lecturerId) {
            for ($i = $startIndex - 1; $i >= 0; $i--) {
                $prevSlotId = $this->timeSlots[$i]['id'];
                if (isset($this->lecturerMatrix[$lecturerId][$day][$prevSlotId])) {
                    $consecutiveLecturerHours++;
                } else {
                    break;
                }
            }
        }

        for ($i = 0; $i < $duration; $i++) {
            $slot = $this->timeSlots[$startIndex + $i];
            $slotId = $slot['id'];

            // Robust boolean checking for breaks
            if (filter_var($slot['is_break'], FILTER_VALIDATE_BOOLEAN)) {
                return false;
            }

            if (isset($this->roomMatrix[$room['id']][$day][$slotId])) return false;
            if (isset($this->levelMatrix[$levelId][$day][$slotId])) return false;

            if ($lecturerId) {
                if (isset($this->lecturerMatrix[$lecturerId][$day][$slotId])) return false;
                if (isset($this->lecturerUnavailability[$lecturerId][$day][$slotId])) return false;
                $consecutiveLecturerHours++;
            }
        }
        
        if ($lecturerId) {
            for ($i = $startIndex + $duration; $i < count($this->timeSlots); $i++) {
                 $nextSlotId = $this->timeSlots[$i]['id'];
                 if (isset($this->lecturerMatrix[$lecturerId][$day][$nextSlotId])) {
                     $consecutiveLecturerHours++;
                 } else {
                     break;
                 }
            }
            if ($consecutiveLecturerHours > 3) return false;
        }

        return true;
    }

    private function commitPlacement($course, $room, $day, $startIndex, $duration) {
        $lecturerId = $course['lecturer_id'];
        $levelId = $course['level_id'];

        for ($i = 0; $i < $duration; $i++) {
            $slotId = $this->timeSlots[$startIndex + $i]['id'];

            $this->roomMatrix[$room['id']][$day][$slotId] = $course['id'];
            $this->levelMatrix[$levelId][$day][$slotId] = $course['id'];
            if ($lecturerId) {
                $this->lecturerMatrix[$lecturerId][$day][$slotId] = $course['id'];
            }

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

        // Omit the `is_locked` column to rely safely on the DEFAULT FALSE setting from the schema
        $stmt = $this->db->prepare("
            INSERT INTO timetable (course_id, room_id, day_of_week, time_slot_id) 
            VALUES (:course_id, :room_id, :day_of_week, :time_slot_id)
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
