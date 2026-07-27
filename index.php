<?php
require_once __DIR__ . '/SystemCore.php';

$core = new SystemCore();
$isLoggedIn = $core->isLoggedIn();

// Direct route to SPA dashboard if requested by an authenticated user
if (isset($_GET['view']) && $_GET['view'] === 'dashboard') {
    if (!$isLoggedIn) {
        header("Location: login.php");
        exit;
    }
    require_once __DIR__ . '/dashboard.php';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sazug Timetable System | Dept of Mathematical Sciences</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-dark: #0a0e17;
            --bg-card: rgba(19, 26, 42, 0.7);
            --primary-glow: #3b82f6;
            --accent-purple: #8b5cf6;
            --accent-cyan: #06b6d4;
            --border-glow: rgba(59, 130, 246, 0.2);
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: var(--bg-dark);
            color: #f1f5f9;
            overflow-x: hidden;
        }

        /* Glow effects & Background Mesh */
        .bg-mesh {
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100vw;
            height: 100vh;
            background: 
                radial-gradient(circle at 20% 20%, rgba(59, 130, 246, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 80% 30%, rgba(139, 92, 246, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 50% 70%, rgba(6, 182, 212, 0.1) 0%, transparent 50%);
            z-index: -1;
            pointer-events: none;
        }

        /* Glassmorphic Navbar */
        .glass-header {
            background: rgba(10, 14, 23, 0.8);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.3s ease;
        }

        .brand-text {
            font-weight: 800;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, #fff 30%, #a5b4fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Hero Section */
        .hero-badge {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            color: #60a5fa;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 0.4rem 1rem;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .hero-title {
            font-size: clamp(2.5rem, 5vw, 4.2rem);
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -1.5px;
        }

        .gradient-text {
            background: linear-gradient(135deg, #60a5fa 0%, #a78bfa 50%, #2dd4bf 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Glass Cards */
        .glass-card {
            background: var(--bg-card);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .glass-card:hover {
            border-color: rgba(96, 165, 250, 0.4);
            transform: translateY(-4px);
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.5);
        }

        /* Buttons */
        .btn-glow {
            background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
            color: white;
            font-weight: 600;
            border: none;
            padding: 0.8rem 1.8rem;
            border-radius: 12px;
            box-shadow: 0 0 20px rgba(37, 99, 235, 0.4);
            transition: all 0.3s ease;
        }

        .btn-glow:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 30px rgba(37, 99, 235, 0.7);
            color: white;
        }

        .btn-outline-glass {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #f1f5f9;
            font-weight: 600;
            padding: 0.8rem 1.8rem;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .btn-outline-glass:hover {
            background: rgba(255, 255, 255, 0.12);
            color: white;
            border-color: rgba(255, 255, 255, 0.3);
        }

        /* Feature Carousel Styling */
        .carousel-item {
            padding: 2rem 1rem;
        }

        .carousel-indicators [data-bs-target] {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: #60a5fa;
        }

        /* Schedule Matrix Sample */
        .mini-grid-table th, .mini-grid-table td {
            font-size: 0.8rem;
            border-color: rgba(255,255,255,0.08);
        }

        .tag-pill {
            display: inline-block;
            padding: 0.25rem 0.6rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .tag-blue { background: rgba(59, 130, 246, 0.2); color: #93c5fd; }
        .tag-purple { background: rgba(139, 92, 246, 0.2); color: #c4b5fd; }
        .tag-amber { background: rgba(245, 158, 11, 0.2); color: #fde68a; }

        /* Footer */
        footer {
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(5, 8, 15, 0.95);
        }

        /* Print & PDF Specific Styles */
        @media print {
            body * { visibility: hidden; }
            #printable-area, #printable-area * { visibility: visible; }
            #printable-area { position: absolute; left: 0; top: 0; width: 100%; }
            .d-print-none { display: none !important; }
            .print-header { display: block !important; }
            .glass-card-print { background: white !important; color: black !important; border: none; backdrop-filter: none; }
            .table { border-color: #dee2e6 !important; }
            .table td, .table th { border-color: #dee2e6 !important; color: #000 !important; background-color: #fff !important; }
            .time-col { background-color: #f8f9fa !important; }
        }
        /* PDF Mode Override */
        .pdf-mode { background: white !important; color: black !important; padding: 20px; border-radius: 0; }
        .pdf-mode * { color: black !important; }
        .pdf-mode .table { border-color: #dee2e6 !important; }
        .pdf-mode .table th, .pdf-mode .table td { border-color: #dee2e6 !important; background: transparent !important; }
        .pdf-mode .print-header { display: block !important; }
        .pdf-mode .badge { border: 1px solid #000; color: #000 !important; background: transparent !important; }
    </style>
</head>
<body>

<div class="bg-mesh"></div>

<header class="glass-header sticky-top py-3">
    <div class="container d-flex align-items-center justify-content-between">
        <a href="index.php" class="d-flex align-items-center gap-2 text-decoration-none">
            <div class="bg-primary bg-gradient text-white p-2 rounded-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                <i class="bi bi-calendar-grid-3x3 fs-5"></i>
            </div>
            <div>
                <span class="brand-text fs-5">SAZUG</span>
                <span class="badge text-bg-primary ms-1" style="font-size: 0.65rem;">MATH SCI</span>
            </div>
        </a>

        <nav class="d-none d-md-flex align-items-center gap-4">
            <a href="#features" class="text-white-50 text-decoration-none hover-white fw-medium small">Features</a>
            <a href="#carousel-section" class="text-white-50 text-decoration-none hover-white fw-medium small">Capabilities</a>
            <a href="#schedule-preview" class="text-white-50 text-decoration-none hover-white fw-medium small">Matrix View</a>
            <a href="#about" class="text-white-50 text-decoration-none hover-white fw-medium small">About</a>
        </nav>

        <div class="d-flex align-items-center gap-2">
            <?php if ($isLoggedIn): ?>
                <a href="index.php?view=dashboard" class="btn btn-glow btn-sm px-3">
                    <i class="bi bi-speedometer2 me-1"></i> Admin Dashboard
                </a>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline-glass btn-sm px-3">
                    <i class="bi bi-shield-lock me-1"></i> Admin Portal
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>

<section class="py-5 my-4">
    <div class="container text-center max-w-4xl">
        <div class="hero-badge mb-3 mx-auto">
            <i class="bi bi-cpu-fill text-primary"></i> Algorithm Version 2.4 • Active Constraint Solver
        </div>
        
        <h1 class="hero-title mb-4">
            Automated Academic Scheduling for <span class="gradient-text">Department of Mathematical Sciences</span>
        </h1>
        
        <p class="lead text-white-50 mb-5 mx-auto" style="max-width: 720px; font-size: 1.15rem;">
            Eliminate double-bookings, balance faculty workloads, and optimize lecture hall capacities in seconds using our institutional constraint-satisfaction engine.
        </p>

        <div class="d-flex flex-wrap align-items-center justify-content-center gap-3 mb-5">
            <?php if ($isLoggedIn): ?>
                <a href="index.php?view=dashboard" class="btn btn-glow">
                    Launch Scheduler Core <i class="bi bi-arrow-right ms-2"></i>
                </a>
            <?php else: ?>
                <a href="login.php" class="btn btn-glow">
                    Access Portal <i class="bi bi-arrow-right ms-2"></i>
                </a>
            <?php endif; ?>
            <a href="#schedule-preview" class="btn btn-outline-glass">
                <i class="bi bi-eye me-2"></i> View Schedule Matrix
            </a>
        </div>

        <!-- Quick Stats Banner -->
        <div class="row g-3 justify-content-center mt-4">
            <div class="col-6 col-md-3">
                <div class="glass-card p-3">
                    <h3 class="fw-bold text-primary mb-1">100%</h3>
                    <small class="text-white-50">Conflict Free Guarantee</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="glass-card p-3">
                    <h3 class="fw-bold text-info mb-1">&lt; 1s</h3>
                    <small class="text-white-50">Generation Speed</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="glass-card p-3">
                    <h3 class="fw-bold text-purple mb-1" style="color:#c4b5fd;">1-3 Hrs</h3>
                    <small class="text-white-50">Consecutive Slots</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="glass-card p-3">
                    <h3 class="fw-bold text-warning mb-1">Saturday</h3>
                    <small class="text-white-50">Flexible Expansion</small>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="carousel-section" class="py-5 bg-dark-subtle bg-opacity-10">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold display-6">Engine Capabilities</h2>
            <p class="text-white-50">Designed specifically for the strict constraints of university departmental scheduling.</p>
        </div>

        <div id="featureCarousel" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#featureCarousel" data-bs-slide-to="0" class="active"></button>
                <button type="button" data-bs-target="#featureCarousel" data-bs-slide-to="1"></button>
                <button type="button" data-bs-target="#featureCarousel" data-bs-slide-to="2"></button>
            </div>

            <div class="carousel-inner">
                <!-- Slide 1 -->
                <div class="carousel-item active">
                    <div class="row align-items-center g-4 glass-card p-4 mx-2">
                        <div class="col-md-6">
                            <span class="badge text-bg-primary mb-2">Multi-Hour Logic</span>
                            <h3 class="fw-bold mb-3">Seamless Consecutive Slot Allocations</h3>
                            <p class="text-white-50">The engine automatically reserves contiguous time blocks for 1, 2, or 3-hour courses without splitting classes across lunch breaks or standard university rest hours.</p>
                            <ul class="list-unstyled text-white-70 small">
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-primary me-2"></i> Respects mandatory 1:00 PM Break</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-primary me-2"></i> Prevents multi-hour fragmentation</li>
                            </ul>
                        </div>
                        <div class="col-md-6 text-center">
                            <i class="bi bi-clock-history text-primary display-1 opacity-75"></i>
                        </div>
                    </div>
                </div>

                <!-- Slide 2 -->
                <div class="carousel-item">
                    <div class="row align-items-center g-4 glass-card p-4 mx-2">
                        <div class="col-md-6">
                            <span class="badge text-bg-info mb-2">Smart Hardware Matching</span>
                            <h3 class="fw-bold mb-3">Laboratory & Classroom Enforcement</h3>
                            <p class="text-white-50">Courses designated as practical or computer-based are automatically mapped to Computer Labs or Science Laboratories with appropriate capacity limits.</p>
                            <ul class="list-unstyled text-white-70 small">
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-info me-2"></i> Room Capacity Checking against Student Count</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-info me-2"></i> Automatic Lab Equipment Priority</li>
                            </ul>
                        </div>
                        <div class="col-md-6 text-center">
                            <i class="bi bi-laptop text-info display-1 opacity-75"></i>
                        </div>
                    </div>
                </div>

                <!-- Slide 3 -->
                <div class="carousel-item">
                    <div class="row align-items-center g-4 glass-card p-4 mx-2">
                        <div class="col-md-6">
                            <span class="badge text-bg-warning mb-2">Faculty Protection</span>
                            <h3 class="fw-bold mb-3">Lecturer Workload & Unavailability Safeguards</h3>
                            <p class="text-white-50">Protects faculty from fatigue by restricting consecutive teaching hours to a max of 3 consecutive hours while adhering strictly to individual unavailable slots.</p>
                            <ul class="list-unstyled text-white-70 small">
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-warning me-2"></i> Hard constraint on lecturer double-booking</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-warning me-2"></i> Individual availability matrix support</li>
                            </ul>
                        </div>
                        <div class="col-md-6 text-center">
                            <i class="bi bi-person-badge text-warning display-1 opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>

            <button class="carousel-control-prev d-none d-md-block" type="button" data-bs-target="#featureCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon"></span>
            </button>
            <button class="carousel-control-next d-none d-md-block" type="button" data-bs-target="#featureCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon"></span>
            </button>
        </div>
    </div>
</section>

<section id="schedule-preview" class="py-5">
    <div class="container">
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 d-print-none">
            <div>
                <h2 class="fw-bold mb-1">Live Timetable Explorer</h2>
                <p class="text-white-50 m-0">Filter, view, download, and print the official generated schedule.</p>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <button class="btn btn-outline-glass" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print</button>
                <button class="btn btn-glow" onclick="downloadPDF()"><i class="bi bi-file-earmark-pdf me-1"></i> Download PDF</button>
            </div>
        </div>

        <!-- Filter Controls -->
        <div class="glass-card p-3 mb-4 d-print-none">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label text-white-50 small mb-1">Academic Level</label>
                    <select id="filter-level" class="form-select bg-dark text-white border-secondary" onchange="renderPublicTimetable()">
                        <option value="ALL">All Levels</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-white-50 small mb-1">Lecturer / Instructor</label>
                    <select id="filter-lecturer" class="form-select bg-dark text-white border-secondary" onchange="renderPublicTimetable()">
                        <option value="ALL">All Lecturers</option>
                    </select>
                </div>
                 <div class="col-md-4">
                    <label class="form-label text-white-50 small mb-1">Room / Laboratory</label>
                    <select id="filter-room" class="form-select bg-dark text-white border-secondary" onchange="renderPublicTimetable()">
                        <option value="ALL">All Rooms</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Printable Data Area -->
        <div id="printable-area" class="glass-card glass-card-print p-3 table-responsive">
            <!-- Hidden by default, shows up in Print/PDF -->
            <div class="text-center mb-4 d-none print-header">
                <h3 class="fw-bold mb-1">Department of Mathematical Sciences</h3>
                <h5 class="mb-3">Official Academic Timetable</h5>
                <p id="print-meta" class="small"></p>
            </div>
            
            <table class="table table-dark table-bordered border-secondary table-hover mini-grid-table m-0 text-center align-middle" id="public-timetable" style="min-width: 1000px;">
                <thead>
                    <tr id="public-grid-header">
                        <th class="bg-secondary text-white">Time / Day</th>
                        <!-- Generated by JS -->
                    </tr>
                </thead>
                <tbody id="public-grid-body">
                    <tr><td colspan="7" class="py-5 text-white-50">Connecting to scheduling engine...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="glass-card p-5 text-center position-relative overflow-hidden" style="background: linear-gradient(135deg, rgba(37, 99, 235, 0.2) 0%, rgba(139, 92, 246, 0.2) 100%);">
            <h2 class="fw-bold display-6 mb-3">Ready to Schedule the Semester?</h2>
            <p class="text-white-50 mx-auto mb-4" style="max-width: 600px;">
                Log into the administrative portal to manage rooms, courses, and lecturers, or run the auto-generator algorithm in one click.
            </p>
            <?php if ($isLoggedIn): ?>
                <a href="index.php?view=dashboard" class="btn btn-glow px-4 py-2">
                    Open Admin Dashboard <i class="bi bi-arrow-right ms-2"></i>
                </a>
            <?php else: ?>
                <a href="login.php" class="btn btn-glow px-4 py-2">
                    Login to Portal <i class="bi bi-shield-lock ms-2"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<footer class="py-4 mt-5">
    <div class="container text-center text-md-start">
        <div class="row g-4 align-items-center">
            <div class="col-md-6">
                <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2 mb-2">
                    <i class="bi bi-calendar-grid-3x3 text-primary fs-5"></i>
                    <span class="fw-bold">Sazug Timetable System</span>
                </div>
                <p class="text-white-50 small mb-0">Department of Mathematical Sciences • Optimized for Render & Neon PostgreSQL.</p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <small class="text-white-50">&copy; <?= date('Y') ?> Sazug University. All rights reserved.</small>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- HTML2PDF Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script>
    // --- Public Timetable Viewer Logic ---
    let pubData = {
        timetable: [],
        setup: { time_slots: [], levels: [], lecturers: [], rooms: [], settings: {} },
        days: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']
    };

    async function loadPublicData() {
        try {
            // Fetch configuration and entities
            const setupRes = await fetch('api.php?action=get_setup_data');
            const setupJson = await setupRes.json();
            if(setupJson.success) pubData.setup = setupJson.data;

            // Update days array if Saturdays are allowed
            if (pubData.setup.settings.allow_saturdays === 'true') {
                pubData.days.push('Saturday');
            }

            // Fetch the actual placed courses
            const timeRes = await fetch('api.php?action=fetch_timetable');
            const timeJson = await timeRes.json();
            if(timeJson.success) pubData.timetable = timeJson.data;

            populateFilters();
            renderPublicTimetable();
        } catch (error) {
            console.error("Error loading public data:", error);
            document.getElementById('public-grid-body').innerHTML = '<tr><td colspan="7" class="text-danger py-4">Unable to load timetable data.</td></tr>';
        }
    }

    function populateFilters() {
        const lvlSelect = document.getElementById('filter-level');
        pubData.setup.levels.forEach(l => lvlSelect.add(new Option(l.name, l.name)));

        const lecSelect = document.getElementById('filter-lecturer');
        pubData.setup.lecturers.forEach(l => lecSelect.add(new Option(l.name, l.name)));

        const roomSelect = document.getElementById('filter-room');
        pubData.setup.rooms.forEach(r => roomSelect.add(new Option(r.name, r.name)));
    }

    function renderPublicTimetable() {
        const fLvl = document.getElementById('filter-level').value;
        const fLec = document.getElementById('filter-lecturer').value;
        const fRoom = document.getElementById('filter-room').value;

        // Apply filters to placements
        let filtered = pubData.timetable;
        if (fLvl !== 'ALL') filtered = filtered.filter(p => p.level_name === fLvl);
        if (fLec !== 'ALL') filtered = filtered.filter(p => p.lecturer_name === fLec);
        if (fRoom !== 'ALL') filtered = filtered.filter(p => p.room_name === fRoom);

        // Update Meta string for printing
        let metaArr = [];
        if (fLvl !== 'ALL') metaArr.push(`Level: ${fLvl}`);
        if (fLec !== 'ALL') metaArr.push(`Lecturer: ${fLec}`);
        if (fRoom !== 'ALL') metaArr.push(`Room: ${fRoom}`);
        document.getElementById('print-meta').innerText = metaArr.length ? 'Filtered by - ' + metaArr.join(' | ') : 'All Levels, Lecturers, and Rooms';

        // Render Header
        const thead = document.getElementById('public-grid-header');
        thead.innerHTML = '<th class="bg-dark text-white time-col" style="width:120px;">Time</th>' + 
            pubData.days.map(d => `<th class="bg-dark text-white">${d}</th>`).join('');

        // Render Body
        const tbody = document.getElementById('public-grid-body');
        let html = '';

        pubData.setup.time_slots.forEach(slot => {
            const tStart = slot.start_time.substring(0,5);
            const tEnd = slot.end_time.substring(0,5);

            if (slot.is_break === true || slot.is_break === 'true') {
                html += `<tr><td class="fw-bold time-col text-white-50">${tStart} - ${tEnd}</td>
                         <td colspan="${pubData.days.length}" class="text-uppercase fw-bold text-white-50" style="letter-spacing: 2px;">
                            <i class="bi bi-cup-hot me-1"></i> Break
                         </td></tr>`;
                return;
            }

            html += `<tr><td class="fw-bold time-col text-primary">${tStart} - ${tEnd}</td>`;
            
            pubData.days.forEach(day => {
                const cellPlacements = filtered.filter(p => p.time_slot_id == slot.id && p.day_of_week === day);
                
                html += `<td>`;
                if (cellPlacements.length > 0) {
                    html += `<div class="d-flex flex-column gap-2">`;
                    cellPlacements.forEach(p => {
                        html += `
                        <div class="p-2 rounded border border-secondary" style="background: rgba(255,255,255,0.02);">
                            <div class="fw-bold text-info">${p.course_code}</div>
                            <div style="font-size: 0.75rem;"><span class="badge bg-secondary opacity-75">${p.level_name}</span></div>
                            <div class="text-white-50 mt-1" style="font-size: 0.75rem;"><i class="bi bi-geo-alt"></i> ${p.room_name}</div>
                            ${p.lecturer_name ? `<div class="text-white-50" style="font-size: 0.75rem;"><i class="bi bi-person"></i> ${p.lecturer_name}</div>` : ''}
                        </div>`;
                    });
                    html += `</div>`;
                } else {
                    html += `<span class="text-white-50 opacity-25">-</span>`;
                }
                html += `</td>`;
            });
            html += `</tr>`;
        });

        tbody.innerHTML = html || '<tr><td colspan="7">No timetable slots configured.</td></tr>';
    }

    // PDF Generation Wrapper
    function downloadPDF() {
        const element = document.getElementById('printable-area');
        // Add class to format it nicely for a white PDF background
        element.classList.add('pdf-mode');
        
        // Remove Bootstrap dark table classes temporarily
        const table = document.getElementById('public-timetable');
        table.classList.remove('table-dark');
        
        const opt = {
            margin:       0.3,
            filename:     'Sazug_Timetable.pdf',
            image:        { type: 'jpeg', quality: 1 },
            html2canvas:  { scale: 2, useCORS: true },
            jsPDF:        { unit: 'in', format: 'a4', orientation: 'landscape' }
        };

        // Generate and then clean up classes
        html2pdf().set(opt).from(element).save().then(() => {
            element.classList.remove('pdf-mode');
            table.classList.add('table-dark');
        });
    }

    // Init on load
    document.addEventListener("DOMContentLoaded", loadPublicData);
</script>

</body>
</html>
