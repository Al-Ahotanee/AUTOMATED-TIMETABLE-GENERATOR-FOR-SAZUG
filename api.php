<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

require_once __DIR__ . '/SystemCore.php';
$core = new SystemCore();

// Support both standard POST data and JSON payloads from Fetch API
$input = json_decode(file_get_contents('php://input'), true);
if (is_array($input)) {
    $_POST = array_merge($_POST, $input);
}

// Determine the action requested
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Define actions that do NOT require admin authentication (Public Access)
$public_actions = ['fetch_timetable', 'get_setup_data'];

// Ensure the user is authenticated to access protected API actions
if (!in_array($action, $public_actions) && !$core->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access.']);
    exit;
}

// Helper function to send JSON responses
function sendResponse($success, $data = null, $error = null) {
    $response = ['success' => $success];
    if ($data !== null) $response['data'] = $data;
    if ($error !== null) $response['error'] = $error;
    echo json_encode($response);
    exit;
}

try {
    switch ($action) {
        
        // --- TIMETABLE GENERATION & FETCHING ---
        case 'fetch_timetable':
            sendResponse(true, $core->getTimetable());
            break;

        case 'generate_timetable':
            require_once __DIR__ . '/TimetableEngine.php';
            $engine = new TimetableEngine();
            $result = $engine->generate();
            if ($result['success']) {
                sendResponse(true, $result);
            } else {
                sendResponse(false, null, $result['error']);
            }
            break;
            
        case 'clear_timetable':
            $core->clearUnlockedTimetable();
            sendResponse(true, ['message' => 'Unlocked timetable slots cleared.']);
            break;

        // --- SETUP DATA (For populating forms) ---
        case 'get_setup_data':
            $data = [
                'programs' => $core->getPrograms(),
                'levels' => $core->getLevels(),
                'time_slots' => $core->getTimeSlots(),
                'lecturers' => $core->getLecturers(),
                'settings' => $core->getSettings()
            ];
            sendResponse(true, $data);
            break;
            
        // --- SETTINGS ---
        case 'update_setting':
            $key = trim($_POST['setting_key'] ?? '');
            $val = trim($_POST['setting_value'] ?? '');
            if ($key) {
                $core->updateSetting($key, $val);
                sendResponse(true);
            }
            sendResponse(false, null, 'Invalid parameters');
            break;

        // --- ROOMS ---
        case 'get_rooms':
            sendResponse(true, $core->getRooms());
            break;
            
        case 'save_room':
            $id = $_POST['id'] ?? null;
            $name = trim($_POST['name'] ?? '');
            $capacity = (int)($_POST['capacity'] ?? 0);
            $type = $_POST['room_type'] ?? 'Lecture Hall';
            
            if (!$name || $capacity <= 0) {
                sendResponse(false, null, 'Invalid room data');
            }
            
            if ($id) {
                $core->updateRoom($id, $name, $capacity, $type);
            } else {
                $core->addRoom($name, $capacity, $type);
            }
            sendResponse(true);
            break;
            
        case 'delete_room':
            $id = $_POST['id'] ?? null;
            if ($id) $core->deleteRoom($id);
            sendResponse(true);
            break;

        // --- LECTURERS ---
        case 'get_lecturers':
            sendResponse(true, $core->getLecturers());
            break;
            
        case 'save_lecturer':
            $id = $_POST['id'] ?? null;
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            
            if (!$name) sendResponse(false, null, 'Name is required');
            
            if ($id) {
                $core->updateLecturer($id, $name, $email);
            } else {
                $core->addLecturer($name, $email);
            }
            sendResponse(true);
            break;
            
        case 'delete_lecturer':
            $id = $_POST['id'] ?? null;
            if ($id) $core->deleteLecturer($id);
            sendResponse(true);
            break;

        // --- COURSES ---
        case 'get_courses':
            sendResponse(true, $core->getCourses());
            break;
            
        case 'save_course':
            $code = trim($_POST['code'] ?? '');
            $title = trim($_POST['title'] ?? '');
            $duration = (int)($_POST['duration_hours'] ?? 0);
            $students = (int)($_POST['students_count'] ?? 0);
            $is_practical = isset($_POST['is_practical']) && $_POST['is_practical'] == '1';
            $prog_id = $_POST['program_id'] ?? null;
            $level_id = $_POST['level_id'] ?? null;
            $lecturer_id = $_POST['lecturer_id'] ?? null;
            
            if (!$code || !$title || $duration < 1 || $duration > 3 || $students <= 0 || !$prog_id || !$level_id) {
                sendResponse(false, null, 'Missing or invalid course parameters.');
            }
            
            // Note: Update is omitted for brevity as per minimalist architecture, 
            // the admin can delete and recreate if a mistake is made.
            $core->addCourse($code, $title, $duration, $is_practical, $students, $prog_id, $level_id, $lecturer_id);
            sendResponse(true);
            break;
            
        case 'delete_course':
            $id = $_POST['id'] ?? null;
            if ($id) $core->deleteCourse($id);
            sendResponse(true);
            break;

        // --- DEFAULT ---
        default:
            sendResponse(false, null, 'Unknown action specified: ' . htmlspecialchars($action));
            break;
    }
} catch (\Exception $e) {
    // Catch database or syntax errors and return them cleanly to the frontend
    sendResponse(false, null, 'Server Error: ' . $e->getMessage());
}
?>
