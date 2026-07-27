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
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
            <div>
                <h2 class="fw-bold mb-1">Live Timetable Structure</h2>
                <p class="text-white-50 m-0">Sample representation of generated schedule slots across levels.</p>
            </div>
            <a href="login.php" class="btn btn-outline-glass btn-sm">
                <i class="bi bi-gear me-1"></i> Manage Full Matrix
            </a>
        </div>

        <div class="glass-card p-3 table-responsive">
            <table class="table table-dark table-hover mini-grid-table m-0">
                <thead>
                    <tr>
                        <th>Time Slot</th>
                        <th>Monday</th>
                        <th>Tuesday</th>
                        <th>Wednesday</th>
                        <th>Thursday</th>
                        <th>Friday</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="fw-semibold text-primary">08:00 - 10:00</td>
                        <td><span class="tag-pill tag-blue">MTH101 (100L)</span><br><small class="text-white-50">Hall A • Dr. Smith</small></td>
                        <td><span class="tag-pill tag-purple">CSC201 (200L)</span><br><small class="text-white-50">Lab 1 • Prof. Alan</small></td>
                        <td>---</td>
                        <td><span class="tag-pill tag-blue">MTH301 (300L)</span><br><small class="text-white-50">Hall B • Dr. Jane</small></td>
                        <td>---</td>
                    </tr>
                    <tr>
                        <td class="fw-semibold text-primary">10:00 - 12:00</td>
                        <td>---</td>
                        <td><span class="tag-pill tag-amber">STA102 (100L)</span><br><small class="text-white-50">Hall A • Dr. Smith</small></td>
                        <td><span class="tag-pill tag-blue">MTH401 (400L)</span><br><small class="text-white-50">Hall B • Prof. Alan</small></td>
                        <td>---</td>
                        <td><span class="tag-pill tag-purple">CSC102 (100L)</span><br><small class="text-white-50">Lab 1 • Tech Team</small></td>
                    </tr>
                    <tr class="table-active">
                        <td class="fw-semibold text-warning">13:00 - 14:00</td>
                        <td colspan="5" class="text-center text-uppercase fw-bold text-white-50 tracking-wider">
                            <i class="bi bi-cup-hot me-2"></i> University Lunch Break
                        </td>
                    </tr>
                    <tr>
                        <td class="fw-semibold text-primary">14:00 - 16:00</td>
                        <td><span class="tag-pill tag-purple">CSC305 (300L)</span><br><small class="text-white-50">Lab 1 • Tech Team</small></td>
                        <td>---</td>
                        <td><span class="tag-pill tag-blue">MTH202 (200L)</span><br><small class="text-white-50">Hall A • Dr. Jane</small></td>
                        <td><span class="tag-pill tag-amber">STA401 (400L)</span><br><small class="text-white-50">Hall B • Dr. Smith</small></td>
                        <td>---</td>
                    </tr>
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

</body>
</html>
