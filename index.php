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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sazug Timetable System | Dept of Mathematical Sciences</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        :root {
            --primary: #1e3a8a; /* Deep Professional Blue */
            --primary-hover: #172554; /* Darker Blue */
            --primary-light: #eff6ff; /* Very Light Blue */
            --accent: #d946ef; /* Vibrant Magenta */
            --accent-hover: #c026d3; /* Darker Magenta */
            --bg-body: #f8fafc;
            --text-main: #0f172a;
            --text-muted: #64748b;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            overflow-x: hidden;
        }

        /* Top Header */
        .academic-header {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        
        .brand-text {
            font-weight: 700;
            color: var(--primary);
            letter-spacing: -0.5px;
        }

        .nav-link-custom {
            color: var(--text-muted);
            font-weight: 500;
            cursor: pointer;
            transition: color 0.2s ease;
        }
        .nav-link-custom:hover {
            color: var(--primary);
        }

        /* Buttons */
        .btn-primary-custom {
            background-color: var(--primary);
            border-color: var(--primary);
            color: white;
            font-weight: 500;
            border-radius: 8px;
            padding: 0.6rem 1.5rem;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary-custom:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
            color: white;
            transform: translateY(-1px);
        }
        
        .btn-outline-custom {
            background-color: transparent;
            border: 1px solid #cbd5e1;
            color: var(--text-main);
            font-weight: 500;
            border-radius: 8px;
            padding: 0.6rem 1.5rem;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-outline-custom:hover {
            border-color: var(--primary);
            color: var(--primary);
            background-color: var(--primary-light);
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, #ffffff 0%, var(--primary-light) 100%);
            padding: 5rem 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .hero-badge {
            background: rgba(217, 70, 239, 0.1);
            color: var(--accent-hover);
            font-size: 0.85rem;
            font-weight: 600;
            padding: 0.4rem 1rem;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid rgba(217, 70, 239, 0.2);
        }
        .hero-title {
            font-size: clamp(2.5rem, 5vw, 3.5rem);
            font-weight: 800;
            line-height: 1.2;
            color: #0f172a;
            letter-spacing: -1px;
        }

        /* Cards */
        .feature-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s;
        }
        .feature-card:hover {
            transform: translateY(-4px);
        }
        .stat-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
        }

        /* Timetable Grid Specifics */
        .timetable-wrapper {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .table-custom {
            margin-bottom: 0;
            border-color: #e2e8f0;
        }
        .table-custom th {
            background-color: #f8fafc;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.9rem;
            padding: 1rem;
            border-bottom: 2px solid #cbd5e1;
        }
        .table-custom td {
            vertical-align: top;
            padding: 0.75rem;
        }
        .time-col {
            background-color: #f8fafc;
            font-weight: 600;
            color: var(--text-main);
            width: 120px;
            text-align: center;
            vertical-align: middle !important;
        }
        .course-pill {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-left: 4px solid var(--primary);
            border-radius: 6px;
            padding: 0.5rem;
            font-size: 0.8rem;
            margin-bottom: 0.5rem;
        }
        .course-pill:last-child {
            margin-bottom: 0;
        }

        /* Print & PDF Specific Styles */
        @media print {
            body * { visibility: hidden; }
            #printable-area, #printable-area * { visibility: visible; }
            #printable-area { position: absolute; left: 0; top: 0; width: 100%; }
            .d-print-none { display: none !important; }
            .print-header { display: block !important; text-align: center; margin-bottom: 20px; }
            .timetable-wrapper { border: none; box-shadow: none; }
        }
        /* PDF Mode Override */
        .pdf-mode { padding: 20px; background: white; }
        .pdf-mode .print-header { display: block !important; text-align: center; margin-bottom: 20px; }
        .pdf-mode .table-custom th, .pdf-mode .table-custom td { border: 1px solid #cbd5e1 !important; }
        .pdf-mode .course-pill { border: 1px solid #cbd5e1 !important; border-left: 4px solid #000 !important; }
        
        .lucide { width: 1.25rem; height: 1.25rem; }
    </style>
</head>
<body>

<header class="academic-header sticky-top py-3">
    <div class="container d-flex align-items-center justify-content-between">
        <a href="#" onclick="toggleView('home')" class="d-flex align-items-center gap-2 text-decoration-none">
            <div class="bg-primary text-white p-2 rounded d-flex align-items-center justify-content-center" style="background-color: var(--primary) !important;">
                <i data-lucide="calendar-days"></i>
            </div>
            <div>
                <span class="brand-text fs-5">SAZUG</span>
                <span class="badge bg-secondary ms-1" style="font-size: 0.65rem;">MATH SCI</span>
            </div>
        </a>

        <nav class="d-none d-md-flex align-items-center gap-4">
            <span onclick="toggleView('home')" class="nav-link-custom">Home</span>
            <span onclick="toggleView('timetable')" class="nav-link-custom">Live Timetable</span>
        </nav>

        <div class="d-flex align-items-center gap-2">
            <?php if ($isLoggedIn): ?>
                <a href="index.php?view=dashboard" class="btn btn-primary-custom btn-sm">
                    <i data-lucide="layout-dashboard"></i> Dashboard
                </a>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline-custom btn-sm">
                    <i data-lucide="shield"></i> Admin Login
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>

<div id="view-home">
    <section class="hero-section">
        <div class="container text-center" style="max-width: 900px;">
            <div class="hero-badge mb-3 mx-auto">
                <i data-lucide="cpu" style="width: 1rem; height: 1rem;"></i> Algorithm Version 2.4 • Active
            </div>
            
            <h1 class="hero-title mb-4">
                Automated Academic Scheduling for the <span style="color: var(--accent);">Department of Mathematical Sciences</span>
            </h1>
            
            <p class="lead text-muted mb-5 mx-auto" style="max-width: 720px; font-size: 1.15rem;">
                Eliminate double-bookings, balance faculty workloads, and optimize lecture hall capacities with our automated constraint-satisfaction engine.
            </p>

            <div class="d-flex flex-wrap align-items-center justify-content-center gap-3">
                <button onclick="toggleView('timetable')" class="btn btn-primary-custom btn-lg">
                    <i data-lucide="eye"></i> View Live Timetable
                </button>
                <?php if ($isLoggedIn): ?>
                    <a href="index.php?view=dashboard" class="btn btn-outline-custom btn-lg">Manage Data</a>
                <?php endif; ?>
            </div>

            <!-- Quick Stats -->
            <div class="row g-3 justify-content-center mt-5">
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <h3 class="fw-bold mb-1" style="color: var(--primary);">100%</h3>
                        <small class="text-muted fw-medium">Conflict Free</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <h3 class="fw-bold mb-1 text-dark">Automated</h3>
                        <small class="text-muted fw-medium">Lab Allocations</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <h3 class="fw-bold mb-1 text-dark">Smart</h3>
                        <small class="text-muted fw-medium">Break Logic</small>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 bg-white">
        <div class="container py-4">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Engine Capabilities</h2>
                <p class="text-muted">Designed strictly for university departmental constraints.</p>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card p-4 h-100 text-center">
                        <div class="d-inline-flex p-3 rounded-circle mb-3" style="background: var(--primary-light); color: var(--primary);">
                            <i data-lucide="clock" style="width: 2rem; height: 2rem;"></i>
                        </div>
                        <h5 class="fw-bold">Multi-Hour Logic</h5>
                        <p class="text-muted small">Reserves contiguous time blocks for 1, 2, or 3-hour courses without fragmentation across standard breaks.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card p-4 h-100 text-center">
                        <div class="d-inline-flex p-3 rounded-circle mb-3" style="background: rgba(217, 70, 239, 0.1); color: var(--accent);">
                            <i data-lucide="laptop" style="width: 2rem; height: 2rem;"></i>
                        </div>
                        <h5 class="fw-bold">Hardware Matching</h5>
                        <p class="text-muted small">Automatically maps practical courses to Computer Labs with strict capacity checking against student limits.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card p-4 h-100 text-center">
                        <div class="d-inline-flex p-3 rounded-circle mb-3" style="background: var(--primary-light); color: var(--primary);">
                            <i data-lucide="user-check" style="width: 2rem; height: 2rem;"></i>
                        </div>
                        <h5 class="fw-bold">Faculty Protection</h5>
                        <p class="text-muted small">Prevents lecturer fatigue by restricting consecutive teaching hours and strictly adhering to unavailability periods.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 text-center" style="background-color: var(--primary); color: white;">
        <div class="container py-4">
            <h2 class="fw-bold mb-3">Department Administration</h2>
            <p class="mx-auto mb-4" style="max-width: 600px; color: var(--primary-light);">
                Authorized personnel can log in to manage courses, update room capacities, and execute the scheduling algorithm.
            </p>
            <a href="login.php" class="btn btn-light btn-lg fw-bold px-4" style="border-radius: 8px; color: var(--primary);">
                Access Portal <i data-lucide="arrow-right" class="ms-2" style="width: 1.2rem; height: 1.2rem; display:inline-block; vertical-align: middle;"></i>
            </a>
        </div>
    </section>
</div>

<div id="view-timetable" class="d-none bg-body" style="min-height: calc(100vh - 70px);">
    <div class="container py-5">
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 d-print-none">
            <div>
                <button onclick="toggleView('home')" class="btn btn-sm btn-outline-secondary mb-2 border-0 px-0 d-flex align-items-center gap-1">
                    <i data-lucide="arrow-left" style="width:16px;"></i> Back to Home
                </button>
                <h2 class="fw-bold mb-1">Official Timetable</h2>
                <p class="text-muted m-0">Filter, view, download, and print the generated schedule.</p>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <button class="btn btn-outline-custom" onclick="window.print()">
                    <i data-lucide="printer"></i> Print
                </button>
                <button class="btn btn-primary-custom" onclick="downloadPDF()">
                    <i data-lucide="file-text"></i> Export PDF
                </button>
            </div>
        </div>

        <!-- Filter Controls -->
        <div class="timetable-wrapper p-3 mb-4 d-print-none">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label text-muted small fw-bold mb-1">Academic Level</label>
                    <select id="filter-level" class="form-select bg-light" onchange="renderPublicTimetable()">
                        <option value="ALL">All Levels</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted small fw-bold mb-1">Lecturer / Instructor</label>
                    <select id="filter-lecturer" class="form-select bg-light" onchange="renderPublicTimetable()">
                        <option value="ALL">All Lecturers</option>
                    </select>
                </div>
                 <div class="col-md-4">
                    <label class="form-label text-muted small fw-bold mb-1">Room / Laboratory</label>
                    <select id="filter-room" class="form-select bg-light" onchange="renderPublicTimetable()">
                        <option value="ALL">All Rooms</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Printable Data Area -->
        <div id="printable-area" class="timetable-wrapper table-responsive">
            <div class="d-none print-header pt-4">
                <h3 class="fw-bold mb-1" style="color: var(--primary);">Department of Mathematical Sciences</h3>
                <h5 class="mb-3 text-dark">Official Academic Timetable</h5>
                <p id="print-meta" class="small text-muted"></p>
            </div>
            
            <table class="table table-bordered table-custom text-center align-middle m-0" id="public-timetable" style="min-width: 1000px;">
                <thead>
                    <tr id="public-grid-header">
                        <th class="time-col">Time / Day</th>
                    </tr>
                </thead>
                <tbody id="public-grid-body">
                    <tr><td colspan="7" class="py-5 text-muted">Connecting to scheduling engine...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<footer class="py-4 bg-white border-top">
    <div class="container text-center text-md-start">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2 mb-1">
                    <i data-lucide="calendar-days" style="color: var(--primary);"></i>
                    <span class="fw-bold text-dark">Sazug Timetable</span>
                </div>
                <p class="text-muted small mb-0">Department of Mathematical Sciences</p>
            </div>
            <div class="col-md-6 text-center text-md-end mt-3 mt-md-0">
                <small class="text-muted">&copy; <?= date('Y') ?> Sazug University. All rights reserved.</small>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap & External Libraries -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script>
    // Initialize Lucide icons
    lucide.createIcons();

    // View Toggler
    function toggleView(view) {
        const home = document.getElementById('view-home');
        const timetable = document.getElementById('view-timetable');
        
        if (view === 'timetable') {
            home.classList.add('d-none');
            timetable.classList.remove('d-none');
            // Ensure icons are rendered if content was hidden
            lucide.createIcons();
            window.scrollTo(0,0);
        } else {
            timetable.classList.add('d-none');
            home.classList.remove('d-none');
            window.scrollTo(0,0);
        }
    }

    // --- Public Timetable Viewer Logic ---
    let pubData = {
        timetable: [],
        setup: { time_slots: [], levels: [], lecturers: [], rooms: [], settings: {} },
        days: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']
    };

    async function loadPublicData() {
        try {
            const setupRes = await fetch('api.php?action=get_setup_data');
            const setupJson = await setupRes.json();
            if(setupJson.success) pubData.setup = setupJson.data;

            if (pubData.setup.settings.allow_saturdays === 'true') {
                pubData.days.push('Saturday');
            }

            const timeRes = await fetch('api.php?action=fetch_timetable');
            const timeJson = await timeRes.json();
            if(timeJson.success) pubData.timetable = timeJson.data;

            populateFilters();
            renderPublicTimetable();
        } catch (error) {
            console.error("Error:", error);
            document.getElementById('public-grid-body').innerHTML = '<tr><td colspan="7" class="text-danger py-4">Unable to load timetable data.</td></tr>';
        }
    }

    function populateFilters() {
        const lvlSelect = document.getElementById('filter-level');
        (pubData.setup.levels || []).forEach(l => lvlSelect.add(new Option(l.name, l.name)));

        const lecSelect = document.getElementById('filter-lecturer');
        (pubData.setup.lecturers || []).forEach(l => lecSelect.add(new Option(l.name, l.name)));

        const roomSelect = document.getElementById('filter-room');
        (pubData.setup.rooms || []).forEach(r => roomSelect.add(new Option(r.name, r.name)));
    }

    function renderPublicTimetable() {
        const fLvl = document.getElementById('filter-level').value;
        const fLec = document.getElementById('filter-lecturer').value;
        const fRoom = document.getElementById('filter-room').value;

        let filtered = pubData.timetable;
        if (fLvl !== 'ALL') filtered = filtered.filter(p => p.level_name === fLvl);
        if (fLec !== 'ALL') filtered = filtered.filter(p => p.lecturer_name === fLec);
        if (fRoom !== 'ALL') filtered = filtered.filter(p => p.room_name === fRoom);

        let metaArr = [];
        if (fLvl !== 'ALL') metaArr.push(`Level: ${fLvl}`);
        if (fLec !== 'ALL') metaArr.push(`Lecturer: ${fLec}`);
        if (fRoom !== 'ALL') metaArr.push(`Room: ${fRoom}`);
        document.getElementById('print-meta').innerText = metaArr.length ? 'Filtered by - ' + metaArr.join(' | ') : 'All Levels, Lecturers, and Rooms';

        const thead = document.getElementById('public-grid-header');
        thead.innerHTML = '<th class="time-col">Time</th>' + 
            pubData.days.map(d => `<th>${d}</th>`).join('');

        const tbody = document.getElementById('public-grid-body');
        let html = '';

        pubData.setup.time_slots.forEach(slot => {
            const tStart = slot.start_time.substring(0,5);
            const tEnd = slot.end_time.substring(0,5);

            if (slot.is_break === true || slot.is_break === 'true') {
                html += `<tr><td class="time-col text-muted">${tStart} - ${tEnd}</td>
                         <td colspan="${pubData.days.length}" class="text-uppercase fw-bold text-muted bg-light" style="letter-spacing: 2px; font-size: 0.85rem;">
                            <i data-lucide="coffee" class="me-2" style="width:16px;"></i> University Break
                         </td></tr>`;
                return;
            }

            html += `<tr><td class="time-col text-primary" style="color: var(--primary) !important;">${tStart} - ${tEnd}</td>`;
            
            pubData.days.forEach(day => {
                const cellPlacements = filtered.filter(p => p.time_slot_id == slot.id && p.day_of_week === day);
                
                html += `<td>`;
                if (cellPlacements.length > 0) {
                    html += `<div class="d-flex flex-column gap-2 text-start">`;
                    cellPlacements.forEach(p => {
                        html += `
                        <div class="course-pill shadow-sm">
                            <div class="fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                <span>${p.course_code}</span>
                                <span class="badge bg-secondary opacity-75" style="font-size: 0.65rem;">${p.level_name}</span>
                            </div>
                            <div class="text-muted" style="font-size: 0.75rem;"><i data-lucide="map-pin" style="width:12px; height:12px;"></i> ${p.room_name}</div>
                            ${p.lecturer_name ? `<div class="text-muted mt-1" style="font-size: 0.75rem;"><i data-lucide="user" style="width:12px; height:12px;"></i> ${p.lecturer_name}</div>` : ''}
                        </div>`;
                    });
                    html += `</div>`;
                } else {
                    html += `<span class="text-muted opacity-25">-</span>`;
                }
                html += `</td>`;
            });
            html += `</tr>`;
        });

        tbody.innerHTML = html || '<tr><td colspan="7">No timetable slots configured.</td></tr>';
        
        // Re-render lucide icons inside the newly generated HTML
        lucide.createIcons();
    }

    function downloadPDF() {
        const element = document.getElementById('printable-area');
        element.classList.add('pdf-mode');
        
        const opt = {
            margin:       0.3,
            filename:     'Sazug_Timetable.pdf',
            image:        { type: 'jpeg', quality: 1 },
            html2canvas:  { scale: 2, useCORS: true },
            jsPDF:        { unit: 'in', format: 'a4', orientation: 'landscape' }
        };

        html2pdf().set(opt).from(element).save().then(() => {
            element.classList.remove('pdf-mode');
        });
    }

    document.addEventListener("DOMContentLoaded", loadPublicData);
</script>

</body>
</html>
