<?php
require_once __DIR__ . '/config.php';

class TimetableEngine {
    private $db;
    
    // In-memory data for fast algorithm processing
    private $courses = [];
    private $rooms = [];
    private $timeSlots = [];
    private $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    private $lecturerUnavailability = []; // Format: [lecturer_id][day][slot_id] = true
    
    // Tracking matrices to prevent double-booking
    private $roomMatrix = [];       // [room_id][day][slot_id] = course_id
    private $lecturerMatrix = [];   // [lecturer_id][day][slot_id] = course_id
    private $levelMatrix = [];      // [level_id][day][slot_id] = course_id
    
    // The final successful placements to be saved
    private $placements = [];

    public function __construct() {
        $this->db = getDBConnection();
    }

    /**
     * Main entry point to generate the timetable.
     * Uses a greedy approach with constraint checking.
     */
    public function generate() {
        try {
            $this->db->beginTransaction();

            // 1. Clear previously generated unlocked timetable slots
            $this->db->exec("DELETE FROM timetable WHERE is_locked = false");

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
        // Check if Saturdays are allowed
        $stmt = $this->db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'allow_saturdays'");
        $stmt->execute();
        $allowSaturdays = $stmt->fetchColumn();
        if ($allowSaturdays === 'true') {
            $this->days[] = 'Saturday';
        }

        // Load Time Slots ordered by their index
        $stmt = $this->db->query("SELECT * FROM time_slots ORDER BY order_index ASC");
        $this->timeSlots = $stmt->fetchAll();

        // Load Rooms ordered by capacity (smallest suitable first to save large rooms)
        $stmt = $this->db->query("SELECT * FROM rooms ORDER BY capacity ASC");
        $this->rooms = $stmt->fetchAll();

        // Load Courses that need scheduling (ignoring already locked ones if we implement partial regeneration)
        // For now, we load all courses as we clear the unlocked timetable prior
        $stmt = $this->db->query("SELECT * FROM courses");
        $this->courses = $stmt->fetchAll();

        // Load Lecturer Unavailability
        $stmt = $this->db->query("SELECT lecturer_id, day_of_week, time_slot_id FROM lecturer_unavailability");
        $unavailability = $stmt->fetchAll();
        foreach ($unavailability as $u) {
            $this->lecturerUnavailability[$u['lecturer_id']][$u['day_of_week']][$u['time_slot_id']] = true;
        }

        // Initialize tracking matrices to empty
        $this->roomMatrix = [];
        $this->lecturerMatrix = [];
        $this->levelMatrix = [];
        $this->placements = [];
        
        // Re-populate matrices with locked timetable slots to avoid overwriting them
        $stmt = $this->db->query("SELECT t.course_id, t.room_id, t.day_of_week, t.time_slot_id, c.lecturer_id, c.level_id 
                                  FROM timetable t 
                                  JOIN courses c ON t.course_id = c.id 
                                  WHERE t.is_locked = true");
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

        // Iterate through all days
        foreach ($this->days as $day) {
            // Iterate through rooms
            foreach ($this->rooms as $room) {
                // 1. Hard Constraint: Room Capacity
                if ($room['capacity'] < $students) {
                    continue; 
                }

                // 2. Hard Constraint: Room Type
                // If practical, needs Lab. If not, needs Lecture Hall.
                $isLab = in_array($room['room_type'], ['Laboratory', 'Computer Lab']);
                if ($isPractical && !$isLab) continue;
                if (!$isPractical && $isLab) continue;

                // Iterate through time slots looking for a contiguous block
                $totalSlots = count($this->timeSlots);
                for ($startIndex = 0; $startIndex <= $totalSlots - $duration; $startIndex++) {
                    
                    if ($this->canPlaceAt($course, $room, $day, $startIndex, $duration)) {
                        // Success! Record the placement in our matrices and placements array
                        $this->commitPlacement($course, $room, $day, $startIndex, $duration);
                        return true;
                    }
                }
            }
        }

        return false; // Could not find any valid slot across all days/rooms
    }

    private function canPlaceAt($course, $room, $day, $startIndex, $duration) {
        $lecturerId = $course['lecturer_id'];
        $levelId = $course['level_id'];
        
        // Ensure consecutive lecturer hours don't exceed soft limit (3 hours max)
        $consecutiveLecturerHours = 0;
        
        // Pre-check earlier slots for existing consecutive hours
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

        // Check each required time slot in the duration block
        for ($i = 0; $i < $duration; $i++) {
            $slot = $this->timeSlots[$startIndex + $i];
            $slotId = $slot['id'];

            // Break Constraint: Cannot schedule lectures during designated break times
            if ($slot['is_break']) {
                return false;
            }

            // Room Availability
            if (isset($this->roomMatrix[$room['id']][$day][$slotId])) {
                return false;
            }

            // Level Availability (Students can't be in two places at once)
            if (isset($this->levelMatrix[$levelId][$day][$slotId])) {
                return false;
            }

            // Lecturer Availability & Constraints
            if ($lecturerId) {
                // Double booking
                if (isset($this->lecturerMatrix[$lecturerId][$day][$slotId])) {
                    return false;
                }
                // Lecturer explicit unavailability
                if (isset($this->lecturerUnavailability[$lecturerId][$day][$slotId])) {
                    return false;
                }
                
                $consecutiveLecturerHours++;
            }
        }
        
        // Soft Constraint enforcement: Avoid >3 hours consecutive for lecturer
        // Post-check slots immediately after
        if ($lecturerId) {
            for ($i = $startIndex + $duration; $i < count($this->timeSlots); $i++) {
                 $nextSlotId = $this->timeSlots[$i]['id'];
                 if (isset($this->lecturerMatrix[$lecturerId][$day][$nextSlotId])) {
                     $consecutiveLecturerHours++;
                 } else {
                     break;
                 }
            }
            if ($consecutiveLecturerHours > 3) {
                return false;
            }
        }

        return true;
    }

    private function commitPlacement($course, $room, $day, $startIndex, $duration) {
        $lecturerId = $course['lecturer_id'];
        $levelId = $course['level_id'];

        for ($i = 0; $i < $duration; $i++) {
            $slotId = $this->timeSlots[$startIndex + $i]['id'];

            // Update conflict matrices
            $this->roomMatrix[$room['id']][$day][$slotId] = $course['id'];
            $this->levelMatrix[$levelId][$day][$slotId] = $course['id'];
            if ($lecturerId) {
                $this->lecturerMatrix[$lecturerId][$day][$slotId] = $course['id'];
            }

            // Queue for database insertion
            $this->placements[] = [
                'course_id' => $course['id'],
                'room_id' => $room['id'],
                'day_of_week' => $day,
                'time_slot_id' => $slotId
            ];
        }
    }

    private function savePlacements() {
        if (empty($this->placements)) {
            return;
        }

        $stmt = $this->db->prepare("
            INSERT INTO timetable (course_id, room_id, day_of_week, time_slot_id, is_locked) 
            VALUES (:course_id, :room_id, :day_of_week, :time_slot_id, false)
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
