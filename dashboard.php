<?php
// Secure check to ensure this file is only loaded through index.php
if (!isset($core) || !$core->isLoggedIn()) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sazug Timetable System</title>
    <!-- Bootstrap 5 CSS for UI components -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-color: #f8f9fa;
            overflow-x: hidden;
        }
        
        /* Sidebar Styling */
        #sidebar {
            min-width: 250px;
            max-width: 250px;
            min-height: 100vh;
            background-color: #212529;
            color: #fff;
            transition: all 0.3s;
        }
        #sidebar .nav-link {
            color: rgba(255,255,255,.75);
            border-radius: 8px;
            margin: 0.2rem 1rem;
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }
        #sidebar .nav-link:hover, #sidebar .nav-link.active {
            color: #fff;
            background-color: rgba(255,255,255,.1);
        }
        #sidebar .nav-link.active {
            background-color: #0d6efd;
        }
        
        /* Content Area */
        #content {
            width: 100%;
            padding: 2rem;
            height: 100vh;
            overflow-y: auto;
        }
        
        /* SPA View Management */
        .spa-view {
            display: none;
            animation: fadeIn 0.3s ease-in-out;
        }
        .spa-view.active-view {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Timetable Grid Specifics */
        .timetable-container {
            overflow-x: auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .timetable-table {
            min-width: 1000px;
            margin-bottom: 0;
        }
        .timetable-table th, .timetable-table td {
            text-align: center;
            vertical-align: middle;
            border: 1px solid #e9ecef;
        }
        .timetable-table th {
            background-color: #f1f3f5;
            font-weight: 600;
            color: #495057;
            padding: 1rem;
        }
        .time-col {
            width: 120px;
            background-color: #f8f9fa;
            font-weight: 600;
            font-size: 0.85rem;
        }
        .slot-cell {
            height: 80px;
            padding: 0.5rem !important;
        }
        .course-card {
            background-color: #e7f1ff;
            border-left: 4px solid #0d6efd;
            border-radius: 4px;
            padding: 0.5rem;
            font-size: 0.8rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            transition: transform 0.2s;
        }
        .course-card:hover {
            transform: scale(1.02);
            z-index: 10;
            position: relative;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .course-card.practical {
            background-color: #fdf3e5;
            border-left-color: #fd7e14;
        }
        .break-cell {
            background-color: #e9ecef;
            color: #adb5bd;
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;
            font-size: 0.85rem;
        }

        /* Toast positioning */
        .toast-container {
            z-index: 1055;
        }
    </style>
</head>
<body>

<div class="d-flex">
    <!-- Sidebar -->
    <nav id="sidebar" class="d-flex flex-column py-3 shadow-lg">
        <div class="text-center mb-4 px-3">
            <h5 class="m-0 fw-bold d-flex align-items-center justify-content-center gap-2">
                <i class="bi bi-calendar-grid-3x3 text-primary"></i>
                Sazug Admin
            </h5>
            <small class="text-white-50">Timetable System</small>
        </div>
        
        <ul class="nav flex-column mb-auto">
            <li class="nav-item">
                <a href="#" class="nav-link active" data-target="dashboard"><i class="bi bi-speedometer2"></i> Dashboard</a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link" data-target="rooms"><i class="bi bi-door-open"></i> Rooms</a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link" data-target="lecturers"><i class="bi bi-person-badge"></i> Lecturers</a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link" data-target="courses"><i class="bi bi-journal-text"></i> Courses</a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link" data-target="generator"><i class="bi bi-cpu"></i> Generator</a>
            </li>
            <li class="nav-item mt-3">
                <a href="#" class="nav-link" data-target="settings"><i class="bi bi-gear"></i> Settings</a>
            </li>
        </ul>
        <hr class="mx-3 border-secondary">
        <div class="px-3">
            <!-- Ensure logout goes to a route handling logout, for simplicity we use logout parameter -->
            <a href="login.php?logout=1" class="btn btn-outline-danger w-100 btn-sm text-start ps-3" id="logout-btn">
                <i class="bi bi-box-arrow-right"></i> Log out
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <main id="content">
        
        <!-- View: Dashboard -->
        <div id="view-dashboard" class="spa-view active-view">
            <h2 class="fw-bold mb-4">System Overview</h2>
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body">
                            <h6 class="text-muted fw-bold">Total Courses</h6>
                            <h2 id="stat-courses" class="display-5 fw-bold text-primary">0</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body">
                            <h6 class="text-muted fw-bold">Total Lecturers</h6>
                            <h2 id="stat-lecturers" class="display-5 fw-bold text-success">0</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body">
                            <h6 class="text-muted fw-bold">Total Rooms</h6>
                            <h2 id="stat-rooms" class="display-5 fw-bold text-warning">0</h2>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-5 text-center">
                    <i class="bi bi-magic display-1 text-primary mb-3"></i>
                    <h4>Ready to Schedule</h4>
                    <p class="text-muted max-w-50 mx-auto">Welcome to the Sazug Timetable generation engine. Use the sidebar to manage your institutional constraints, add courses, and generate a conflict-free timetable.</p>
                    <button class="btn btn-primary rounded-pill px-4 nav-link-btn" data-target="generator">Go to Generator</button>
                </div>
            </div>
        </div>

        <!-- View: Rooms -->
        <div id="view-rooms" class="spa-view">
            <h2 class="fw-bold mb-4">Manage Rooms</h2>
            
            <div class="card shadow-sm border-0 mb-4 rounded-4">
                <div class="card-body">
                    <form id="form-room" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Room Name</label>
                            <input type="text" name="name" class="form-control" required placeholder="e.g. Lecture Hall A">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Capacity</label>
                            <input type="number" name="capacity" class="form-control" required min="10" placeholder="e.g. 150">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Type</label>
                            <select name="room_type" class="form-select">
                                <option value="Lecture Hall">Lecture Hall</option>
                                <option value="Laboratory">Laboratory</option>
                                <option value="Computer Lab">Computer Lab</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-plus-lg"></i> Add Room</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-0">
                    <table class="table table-hover mb-0" id="table-rooms">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Capacity</th>
                                <th>Type</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody><!-- Rendered via JS --></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- View: Lecturers -->
        <div id="view-lecturers" class="spa-view">
            <h2 class="fw-bold mb-4">Manage Lecturers</h2>
            
            <div class="card shadow-sm border-0 mb-4 rounded-4">
                <div class="card-body">
                    <form id="form-lecturer" class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" required placeholder="e.g. Dr. John Doe">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Email (Optional)</label>
                            <input type="email" name="email" class="form-control" placeholder="john.doe@university.edu">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-plus-lg"></i> Add</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-0">
                    <table class="table table-hover mb-0" id="table-lecturers">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody><!-- Rendered via JS --></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- View: Courses -->
        <div id="view-courses" class="spa-view">
            <h2 class="fw-bold mb-4">Manage Courses</h2>
            
            <div class="card shadow-sm border-0 mb-4 rounded-4">
                <div class="card-body">
                    <form id="form-course" class="row g-3 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label small">Course Code</label>
                            <input type="text" name="code" class="form-control form-control-sm" required placeholder="MTH101">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Title</label>
                            <input type="text" name="title" class="form-control form-control-sm" required placeholder="Algebra I">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label small">Hrs</label>
                            <input type="number" name="duration_hours" class="form-control form-control-sm" required min="1" max="3" value="2">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label small">Students</label>
                            <input type="number" name="students_count" class="form-control form-control-sm" required min="1" value="100">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Program</label>
                            <select name="program_id" class="form-select form-select-sm" id="select-course-program" required></select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Level</label>
                            <select name="level_id" class="form-select form-select-sm" id="select-course-level" required></select>
                        </div>
                        <div class="col-md-2 mt-2">
                            <label class="form-label small">Lecturer</label>
                            <select name="lecturer_id" class="form-select form-select-sm" id="select-course-lecturer"></select>
                        </div>
                        <div class="col-md-2 mt-2 pt-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_practical" value="1" id="isPrac">
                                <label class="form-check-label small" for="isPrac">Practical/Lab</label>
                            </div>
                        </div>
                        <div class="col-md-12 mt-3">
                            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Add Course</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-0 table-responsive">
                    <table class="table table-hover mb-0 text-sm" id="table-courses" style="font-size: 0.9rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Code</th>
                                <th>Title</th>
                                <th>Hrs</th>
                                <th>Students</th>
                                <th>Program / Level</th>
                                <th>Lecturer</th>
                                <th>Type</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody><!-- Rendered via JS --></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- View: Settings -->
        <div id="view-settings" class="spa-view">
            <h2 class="fw-bold mb-4">System Settings</h2>
            <div class="card shadow-sm border-0 rounded-4 max-w-50">
                <div class="card-body">
                    <form id="form-settings">
                        <div class="mb-4">
                            <label class="form-label fw-bold">Academic Semester</label>
                            <input type="text" class="form-control" name="academic_semester" id="set-semester">
                        </div>
                        <div class="form-check form-switch mb-4">
                            <input class="form-check-input" type="checkbox" role="switch" id="set-saturdays" name="allow_saturdays" value="true">
                            <label class="form-check-label fw-bold" for="set-saturdays">Allow Saturday Scheduling</label>
                            <div class="form-text">If enabled, the engine will place courses on Saturdays if weekdays are full.</div>
                        </div>
                        <button type="button" class="btn btn-primary" onclick="saveSettings()">Save Settings</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- View: Generator -->
        <div id="view-generator" class="spa-view">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold m-0">Timetable Grid</h2>
                <div>
                    <button class="btn btn-outline-danger me-2" onclick="clearTimetable()">
                        <i class="bi bi-trash3"></i> Clear Unlocked
                    </button>
                    <button class="btn btn-success shadow" onclick="generateTimetable()">
                        <i class="bi bi-cpu"></i> Run Auto-Generator
                    </button>
                </div>
            </div>

            <!-- Filter Controls -->
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body d-flex gap-3 align-items-center bg-light rounded">
                    <i class="bi bi-funnel text-muted"></i>
                    <span class="fw-bold small text-muted">View Mode:</span>
                    <select id="grid-filter-level" class="form-select form-select-sm w-auto border-secondary" onchange="renderTimetableGrid()">
                        <option value="ALL">All Levels Overview</option>
                        <!-- Populated dynamically -->
                    </select>
                    <small class="text-muted ms-auto" id="grid-status-text">Showing all scheduled lectures</small>
                </div>
            </div>

            <!-- The Grid -->
            <div class="timetable-container p-2">
                <table class="table table-bordered timetable-table" id="main-timetable">
                    <thead>
                        <tr id="grid-days-header">
                            <!-- Populated dynamically -->
                        </tr>
                    </thead>
                    <tbody id="grid-body">
                        <!-- Populated dynamically -->
                    </tbody>
                </table>
            </div>
            
            <!-- Result Alert Placeholder -->
            <div id="generation-result" class="mt-3"></div>
        </div>

    </main>
</div>

<!-- Toast Container for Notifications -->
<div class="toast-container position-fixed top-0 end-0 p-3">
  <div id="app-toast" class="toast align-items-center text-bg-primary border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body" id="toast-message">Action successful.</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // --- Global State ---
    const state = {
        setup: {
            programs: [], levels: [], time_slots: [], settings: {}
        },
        rooms: [],
        lecturers: [],
        courses: [],
        timetable: [],
        days: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'] // Base days
    };

    // --- Utility: Toast Notification ---
    function showToast(message, type = 'primary') {
        const toastEl = document.getElementById('app-toast');
        toastEl.className = `toast align-items-center text-bg-${type} border-0`;
        document.getElementById('toast-message').innerText = message;
        const toast = new bootstrap.Toast(toastEl);
        toast.show();
    }

    // --- Utility: API Wrapper ---
    async function apiCall(action, formData = null) {
        try {
            let options = { method: formData ? 'POST' : 'GET' };
            if (formData) {
                // If it's a JSON object, stringify it
                if (!(formData instanceof FormData)) {
                    options.headers = { 'Content-Type': 'application/json' };
                    options.body = JSON.stringify({ action: action, ...formData });
                } else {
                    formData.append('action', action);
                    options.body = formData;
                }
            } else {
                action = `?action=${action}`;
            }

            const url = `api.php${!formData ? action : ''}`;
            const response = await fetch(url, options);
            
            // Handle HTTP errors
            if (!response.ok) {
                if (response.status === 401) window.location.href = 'login.php';
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            if (!result.success) throw new Error(result.error || 'Unknown API error');
            
            return result.data || true;
        } catch (error) {
            console.error('API Error:', error);
            showToast(error.message, 'danger');
            return null;
        }
    }

    // --- STREAMING_CHUNK: Navigation & UI Logic ---
    // Handle Navigation Clicks
    document.querySelectorAll('.nav-link, .nav-link-btn').forEach(link => {
        link.addEventListener('click', (e) => {
            if (e.target.closest('#logout-btn')) return; // Ignore logout button
            e.preventDefault();
            const targetId = e.currentTarget.getAttribute('data-target');
            if (targetId) switchView(targetId);
        });
    });

    function switchView(viewId) {
        // Update Sidebar UI
        document.querySelectorAll('#sidebar .nav-link').forEach(l => l.classList.remove('active'));
        const navLink = document.querySelector(`#sidebar .nav-link[data-target="${viewId}"]`);
        if (navLink) navLink.classList.add('active');

        // Hide all views, show target view
        document.querySelectorAll('.spa-view').forEach(v => v.classList.remove('active-view'));
        document.getElementById(`view-${viewId}`).classList.add('active-view');
        
        // Refresh specific data when viewing it
        if (viewId === 'dashboard') updateDashboardStats();
        if (viewId === 'rooms') loadRooms();
        if (viewId === 'lecturers') loadLecturers();
        if (viewId === 'courses') loadCourses();
        if (viewId === 'generator') loadTimetable();
    }

    function updateDashboardStats() {
        document.getElementById('stat-courses').innerText = state.courses.length;
        document.getElementById('stat-lecturers').innerText = state.lecturers.length;
        document.getElementById('stat-rooms').innerText = state.rooms.length;
    }

    // --- STREAMING_CHUNK: Data Initialization (App Load) ---
    async function initializeApp() {
        // Fetch core relational data required for forms
        const setup = await apiCall('get_setup_data');
        if (setup) {
            state.setup = setup;
            populateSelectOptions();
            applySettingsToUI();
        }
        // Load initial entities for stats
        await Promise.all([loadRooms(), loadLecturers(), loadCourses()]);
        updateDashboardStats();
    }

    function populateSelectOptions() {
        // Populate Course Form Options
        const progSelect = document.getElementById('select-course-program');
        progSelect.innerHTML = state.setup.programs.map(p => `<option value="${p.id}">${p.code}</option>`).join('');
        
        const levSelect = document.getElementById('select-course-level');
        levSelect.innerHTML = state.setup.levels.map(l => `<option value="${l.id}">${l.name}</option>`).join('');
        
        // Populate Generator Filter
        const filterSelect = document.getElementById('grid-filter-level');
        filterSelect.innerHTML = '<option value="ALL">All Levels Overview</option>' + 
            state.setup.levels.map(l => `<option value="${l.id}">${l.name}</option>`).join('');
    }

    function applySettingsToUI() {
        const s = state.setup.settings;
        if (s.academic_semester) document.getElementById('set-semester').value = s.academic_semester;
        
        const allowSat = s.allow_saturdays === 'true';
        document.getElementById('set-saturdays').checked = allowSat;
        
        // Update global days array based on settings
        state.days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        if (allowSat) state.days.push('Saturday');
    }

    // --- STREAMING_CHUNK: CRUD Operations (Rooms, Lecturers, Courses) ---

    // Generic Delete Function
    async function deleteEntity(type, id) {
        if (confirm(`Are you sure you want to delete this ${type}?`)) {
            const success = await apiCall(`delete_${type}`, { id: id });
            if (success) {
                showToast(`${type} deleted successfully`, 'success');
                if (type === 'room') loadRooms();
                if (type === 'lecturer') loadLecturers();
                if (type === 'course') loadCourses();
            }
        }
    }

    // ROOMS
    async function loadRooms() {
        const rooms = await apiCall('get_rooms');
        if (rooms) {
            state.rooms = rooms;
            const tbody = document.querySelector('#table-rooms tbody');
            tbody.innerHTML = rooms.map(r => `
                <tr>
                    <td class="fw-bold">${r.name}</td>
                    <td>${r.capacity}</td>
                    <td><span class="badge ${r.room_type.includes('Lab') ? 'text-bg-warning' : 'text-bg-info'}">${r.room_type}</span></td>
                    <td class="text-end"><button class="btn btn-sm btn-outline-danger" onclick="deleteEntity('room', ${r.id})"><i class="bi bi-trash"></i></button></td>
                </tr>
            `).join('');
        }
    }

    document.getElementById('form-room').addEventListener('submit', async (e) => {
        e.preventDefault();
        const success = await apiCall('save_room', new FormData(e.target));
        if (success) {
            showToast('Room saved', 'success');
            e.target.reset();
            loadRooms();
        }
    });

    // LECTURERS
    async function loadLecturers() {
        const lecturers = await apiCall('get_lecturers');
        if (lecturers) {
            state.lecturers = lecturers;
            
            // Update table
            const tbody = document.querySelector('#table-lecturers tbody');
            tbody.innerHTML = lecturers.map(l => `
                <tr>
                    <td class="fw-bold">${l.name}</td>
                    <td>${l.email || '-'}</td>
                    <td class="text-end"><button class="btn btn-sm btn-outline-danger" onclick="deleteEntity('lecturer', ${l.id})"><i class="bi bi-trash"></i></button></td>
                </tr>
            `).join('');

            // Update course form select
            const lecSelect = document.getElementById('select-course-lecturer');
            lecSelect.innerHTML = '<option value="">-- None --</option>' + 
                lecturers.map(l => `<option value="${l.id}">${l.name}</option>`).join('');
        }
    }

    document.getElementById('form-lecturer').addEventListener('submit', async (e) => {
        e.preventDefault();
        const success = await apiCall('save_lecturer', new FormData(e.target));
        if (success) {
            showToast('Lecturer saved', 'success');
            e.target.reset();
            loadLecturers();
        }
    });

    // COURSES
    async function loadCourses() {
        const courses = await apiCall('get_courses');
        if (courses) {
            state.courses = courses;
            const tbody = document.querySelector('#table-courses tbody');
            tbody.innerHTML = courses.map(c => `
                <tr>
                    <td class="fw-bold text-primary">${c.code}</td>
                    <td>${c.title}</td>
                    <td>${c.duration_hours}</td>
                    <td>${c.students_count}</td>
                    <td>${c.program_name} / ${c.level_name}</td>
                    <td>${c.lecturer_name || '-'}</td>
                    <td>${c.is_practical === true || c.is_practical === 'true' ? '<span class="badge text-bg-warning">Practical</span>' : 'Lecture'}</td>
                    <td class="text-end"><button class="btn btn-sm btn-outline-danger" onclick="deleteEntity('course', ${c.id})"><i class="bi bi-trash"></i></button></td>
                </tr>
            `).join('');
        }
    }

    document.getElementById('form-course').addEventListener('submit', async (e) => {
        e.preventDefault();
        const success = await apiCall('save_course', new FormData(e.target));
        if (success) {
            showToast('Course saved', 'success');
            e.target.reset();
            loadCourses();
        }
    });

    // SETTINGS
    async function saveSettings() {
        const isSat = document.getElementById('set-saturdays').checked ? 'true' : 'false';
        const sem = document.getElementById('set-semester').value;
        
        await apiCall('update_setting', { setting_key: 'allow_saturdays', setting_value: isSat });
        await apiCall('update_setting', { setting_key: 'academic_semester', setting_value: sem });
        
        showToast('Settings saved. System config updated.', 'success');
        
        // Re-initialize to apply structural changes (like Saturday grid changes)
        initializeApp();
    }

    // --- STREAMING_CHUNK: Timetable Generation and Rendering Logic ---

    async function loadTimetable() {
        const data = await apiCall('fetch_timetable');
        if (data) {
            state.timetable = data;
            renderTimetableGrid();
        }
    }

    async function generateTimetable() {
        document.getElementById('generation-result').innerHTML = '<div class="alert alert-info border-0 shadow-sm"><i class="bi bi-hourglass-split"></i> Engine is calculating optimal constraints...</div>';
        
        const result = await apiCall('generate_timetable', { trigger: true });
        if (result) {
            showToast('Timetable algorithm completed.', 'success');
            
            // Show result summary
            let resHtml = `<div class="alert alert-success border-0 shadow-sm"><i class="bi bi-check-circle"></i> ${result.message}</div>`;
            
            if (result.unplaced && result.unplaced.length > 0) {
                resHtml += `<div class="alert alert-warning border-0 shadow-sm mt-2">
                    <strong><i class="bi bi-exclamation-triangle"></i> Notice:</strong> Could not fit ${result.unplaced.length} course(s) due to hard constraints (likely room capacity or lack of slots).
                    <ul class="mb-0 mt-1">` + 
                    result.unplaced.map(c => `<li>${c.code} (${c.students_count} students)</li>`).join('') + 
                    `</ul></div>`;
            }
            document.getElementById('generation-result').innerHTML = resHtml;
            
            // Reload grid
            loadTimetable();
        }
    }

    async function clearTimetable() {
        if(confirm("Are you sure you want to clear all unlocked scheduled slots?")) {
            const result = await apiCall('clear_timetable', { trigger: true });
            if (result) {
                showToast('Timetable cleared.', 'info');
                document.getElementById('generation-result').innerHTML = '';
                loadTimetable();
            }
        }
    }

    // The core rendering engine for the dynamic grid
    function renderTimetableGrid() {
        const filterLevelId = document.getElementById('grid-filter-level').value;
        
        // Filter timetable placements
        let placements = state.timetable;
        if (filterLevelId !== 'ALL') {
            // Level filtering requires matching level names or IDs. Our view returns level_name.
            // Let's map ID to Name from setup
            const lvl = state.setup.levels.find(l => l.id == filterLevelId);
            if (lvl) {
                placements = placements.filter(p => p.level_name === lvl.name);
            }
        }

        // Build Header
        const headerRow = document.getElementById('grid-days-header');
        headerRow.innerHTML = `<th class="time-col bg-secondary text-white border-secondary">Time / Day</th>` + 
            state.days.map(d => `<th>${d}</th>`).join('');

        // Build Body Rows (One for each time slot)
        const tbody = document.getElementById('grid-body');
        let html = '';

        state.setup.time_slots.forEach(slot => {
            // Format time e.g. 08:00
            const tStart = slot.start_time.substring(0,5);
            const tEnd = slot.end_time.substring(0,5);

            if (slot.is_break || slot.is_break === 'true') {
                html += `<tr><td class="time-col text-center">${tStart} - ${tEnd}</td>
                         <td colspan="${state.days.length}" class="break-cell text-center align-middle">
                            <i class="bi bi-cup-hot me-2"></i> University Break
                         </td></tr>`;
                return; // Continue to next slot
            }

            html += `<tr><td class="time-col text-center">${tStart} - ${tEnd}</td>`;
            
            // Loop through each active day
            state.days.forEach(day => {
                // Find courses scheduled here
                const cellPlacements = placements.filter(p => p.time_slot_id == slot.id && p.day_of_week === day);
                
                html += `<td class="slot-cell">`;
                if (cellPlacements.length > 0) {
                    html += `<div class="d-flex flex-column gap-1 h-100">`;
                    cellPlacements.forEach(p => {
                        const isPrac = (p.room_name && (p.room_name.toLowerCase().includes('lab') || p.room_capacity < 60)); // Basic inferring for UI style
                        html += `
                        <div class="course-card ${isPrac ? 'practical' : ''}">
                            <div class="fw-bold text-dark d-flex justify-content-between">
                                <span>${p.course_code}</span>
                                <span class="badge text-bg-secondary" style="font-size:0.6rem">${p.level_name}</span>
                            </div>
                            <div class="text-muted" style="font-size: 0.75rem;"><i class="bi bi-geo-alt-fill"></i> ${p.room_name}</div>
                            ${p.lecturer_name ? `<div class="text-truncate mt-1" style="font-size: 0.7rem;"><i class="bi bi-person"></i> ${p.lecturer_name}</div>` : ''}
                        </div>`;
                    });
                    html += `</div>`;
                }
                html += `</td>`;
            });
            html += `</tr>`;
        });

        tbody.innerHTML = html;
    }

    // --- Boot up the app on load ---
    document.addEventListener("DOMContentLoaded", () => {
        initializeApp();
    });
</script>

</body>
</html>
