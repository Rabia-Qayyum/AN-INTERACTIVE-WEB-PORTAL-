<?php
mysqli_report(MYSQLI_REPORT_OFF);
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
include('db.php');

// User Session Handling
$user_id = $_SESSION['user_id'] ?? $_SESSION['end_user_id'] ?? 1;
$user_name = $_SESSION['user_name'] ?? 'User';
$user_role = "User Member";
$user_status = "Active User";

$msg = "";
$error = "";

// 1. Handle Submit Question / Inquiry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_query'])) {
    $question = mysqli_real_escape_string($conn, trim($_POST['question'] ?? ''));
    $agent_id = intval($_POST['agent_id'] ?? 0);

    if (!empty($question)) {
        $ins = "INSERT INTO `query` (question, user_id, agent_id, status) VALUES ('$question', '$user_id', '$agent_id', 'Pending')";
        $res = mysqli_query($conn, $ins);

        if (!$res) {
            $ins_alt = "INSERT INTO `query` (question, status) VALUES ('$question', 'Pending')";
            $res = mysqli_query($conn, $ins_alt);
        }

        if ($res) {
            $msg = "Your question has been submitted successfully!";
        } else {
            $error = "Failed to submit question: " . mysqli_error($conn);
        }
    } else {
        $error = "Please enter your question before submitting.";
    }
}

// 2. Handle Submit Feedback
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $fb_text = mysqli_real_escape_string($conn, trim($_POST['feedback_text'] ?? ''));

    if (!empty($fb_text)) {
        $ins_fb = "INSERT INTO `feedback` (message, user_id) VALUES ('$fb_text', '$user_id')";
        $res_fb = mysqli_query($conn, $ins_fb);

        if (!$res_fb) {
            $ins_fb_alt = "INSERT INTO `feedback` (message) VALUES ('$fb_text')";
            $res_fb = mysqli_query($conn, $ins_fb_alt);
        }

        if ($res_fb) {
            $msg = "Thank you for your feedback!";
        } else {
            $error = "Failed to submit feedback: " . mysqli_error($conn);
        }
    }
}

// Data Fetches
$events_list = mysqli_query($conn, "SELECT * FROM `event` ORDER BY 1 DESC LIMIT 5");
$ideas_list = mysqli_query($conn, "SELECT * FROM `business_idea` ORDER BY 1 DESC");
$tutorials_list = mysqli_query($conn, "SELECT * FROM `tutorial` ORDER BY 1 DESC");
if (!$tutorials_list) {
    $tutorials_list = mysqli_query($conn, "SELECT * FROM `tutorials` ORDER BY 1 DESC");
}

$templates_list = mysqli_query($conn, "SELECT * FROM `template` ORDER BY 1 DESC");
if (!$templates_list) {
    $templates_list = mysqli_query($conn, "SELECT * FROM `templates` ORDER BY 1 DESC");
}

// Agents Dropdown & Cards Query
$agents_dropdown = mysqli_query($conn, "SELECT * FROM `agent` WHERE status = 'Approved' OR status IS NULL");
if (!$agents_dropdown || mysqli_num_rows($agents_dropdown) == 0) {
    $agents_dropdown = mysqli_query($conn, "SELECT * FROM `admin` WHERE role = 'agent' OR role = 'entrepreneur'");
}

$all_agents = mysqli_query($conn, "SELECT * FROM `agent` WHERE status = 'Approved' OR status IS NULL");
if (!$all_agents || mysqli_num_rows($all_agents) == 0) {
    $all_agents = mysqli_query($conn, "SELECT * FROM `admin` WHERE role = 'agent' OR role = 'entrepreneur'");
}

// My Asked Questions
$my_queries = mysqli_query($conn, "SELECT * FROM `query` ORDER BY query_id DESC");

// System Summary Counters
$events_count = ($events_list) ? mysqli_num_rows($events_list) : 0;
$ideas_count = ($ideas_list) ? mysqli_num_rows($ideas_list) : 0;
$agents_count = ($all_agents) ? mysqli_num_rows($all_agents) : 0;
$my_q_count = ($my_queries) ? mysqli_num_rows($my_queries) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Portal Dashboard</title>
    <style>
        :root {
            --bg-body: #ffffff;
            --topbar-bg: #1b2a47;
            --sidebar-bg: #ffffff;
            --card-bg: #ffffff;
            --accent-blue: #2563eb;
            --border-color: #e2e8f0;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --card-pink: #f43f5e;
            --card-yellow: #eab308;
            --card-green: #10b981;
            --card-teal: #06b6d4;
            --danger: #dc2626;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: var(--bg-body); color: var(--text-dark); }

        /* Top Navbar */
        .top-navbar {
            height: 65px;
            background: var(--topbar-bg);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 25px;
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1000;
        }

        .navbar-left { display: flex; align-items: center; gap: 20px; }
        
        /* Logo Styles */
        .logo-box {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 800;
            font-size: 20px;
            color: #ffffff;
            letter-spacing: -0.5px;
        }
        .logo-icon {
            width: 38px;
            height: 38px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .logo-green { color: #10b981; }

        .search-input {
            background: #ffffff;
            border: none;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            outline: none;
            width: 200px;
        }

        .navbar-right { display: flex; align-items: center; gap: 20px; font-size: 13px; color: #cbd5e1; }

        /* User Profile Dropdown Container */
        .user-dropdown-wrapper {
            position: relative;
            display: inline-block;
        }

        .user-profile-trigger {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 6px;
            transition: background 0.2s;
        }
        .user-profile-trigger:hover { background: rgba(255, 255, 255, 0.08); }

        .user-details { text-align: right; }
        .user-name { font-size: 13px; font-weight: 700; color: #ffffff; line-height: 1.2; }
        .user-role { font-size: 11px; color: #94a3b8; margin-top: 2px; }

        .user-avatar {
            width: 38px; height: 38px;
            border-radius: 50%;
            background: var(--accent-blue);
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 14px; color: #fff;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .dropdown-arrow { font-size: 10px; color: #94a3b8; }

        /* Dropdown Menu Box */
        .profile-dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 48px;
            background: #ffffff;
            min-width: 200px;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            border: 1px solid var(--border-color);
            z-index: 1100;
            overflow: hidden;
        }

        .profile-dropdown-menu.show { display: block; }

        .dropdown-header {
            padding: 12px 16px;
            background: #f8fafc;
            border-bottom: 1px solid var(--border-color);
        }
        .dropdown-header-title { font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; }

        .dropdown-item {
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 13px;
            color: var(--text-dark);
            text-decoration: none;
            border-bottom: 1px solid var(--border-color);
        }

        .dropdown-item:last-child { border-bottom: none; }
        .dropdown-item:hover { background: #f1f5f9; }
        .dropdown-item.logout { color: #dc2626; font-weight: 600; }

        /* Layout Container */
        .app-layout { display: flex; margin-top: 65px; min-height: calc(100vh - 65px); }

        /* Left Sidebar Menu */
        .sidebar {
            width: 240px;
            background: var(--sidebar-bg);
            border-right: 1px solid var(--border-color);
            padding-top: 15px;
            flex-shrink: 0;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: var(--text-muted);
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
            border-left: 4px solid transparent;
        }

        .nav-item:hover { background: #f8fafc; color: var(--accent-blue); }
        .nav-item.active {
            background: #eff6ff;
            color: var(--accent-blue);
            border-left-color: var(--accent-blue);
        }

        /* Main Content Area */
        .main-content { flex: 1; padding: 25px; overflow-x: hidden; }

        /* Page Header / Breadcrumb */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .page-title { font-size: 18px; font-weight: 700; color: var(--text-dark); }
        .breadcrumb { font-size: 12px; color: var(--text-muted); }

        /* KPI Stat Cards Grid (Clickable) */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
            margin-bottom: 25px;
        }

        .kpi-card {
            border-radius: 10px;
            color: #fff;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .kpi-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .kpi-card.pink { background: var(--card-pink); }
        .kpi-card.yellow { background: var(--card-yellow); }
        .kpi-card.green { background: var(--card-green); }
        .kpi-card.teal { background: var(--card-teal); }

        .kpi-body {
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .kpi-title { font-size: 11px; font-weight: 700; text-transform: uppercase; opacity: 0.9; letter-spacing: 0.5px; }
        .kpi-value { font-size: 32px; font-weight: 700; margin-top: 5px; }

        .kpi-footer {
            background: rgba(255, 255, 255, 0.95);
            color: var(--text-dark);
            padding: 10px 20px;
            font-size: 12px;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
        }

        /* Card Sections Layout */
        .content-card {
            background: var(--card-bg);
            border-radius: 10px;
            border: 1px solid var(--border-color);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            overflow: hidden;
        }

        .card-header-bar {
            padding: 16px 20px;
            background: #ffffff;
            border-bottom: 1px solid var(--border-color);
            font-weight: 700;
            font-size: 15px;
            color: var(--text-dark);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-body-content { padding: 20px; }

        /* Dynamic Section Panels */
        .section-panel { display: none; }
        .section-panel.active { display: block; }

        .item-card {
            background: #f8fafc;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 12px;
        }
        .item-card h4 { margin: 0 0 6px 0; color: var(--accent-blue); font-size: 15px; }
        .item-card p { margin: 0 0 8px 0; font-size: 13px; color: #475569; }
        .item-meta { font-size: 12px; font-weight: 600; color: var(--text-muted); }

        /* Forms & Inputs */
        .form-grid { display: grid; grid-template-columns: 1fr; gap: 15px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 6px; font-weight: 600; font-size: 12px; color: var(--text-dark); }
        input[type="text"], select, textarea {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 13px;
            outline: none;
            background: #fafafa;
        }
        input:focus, select:focus, textarea:focus { border-color: var(--accent-blue); background: #fff; }

        .btn-primary {
            background: var(--accent-blue);
            color: white;
            border: none;
            padding: 9px 18px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary:hover { background: #1d4ed8; }

        /* Tables */
        table { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 15px; }
        th, td { border-bottom: 1px solid var(--border-color); padding: 12px 14px; text-align: left; font-size: 13px; }
        th { background: #f8fafc; color: var(--text-muted); font-weight: 600; text-transform: uppercase; font-size: 11px; }

        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; }
        .badge-success { background: #dcfce7; color: #15803d; }
        .badge-warning { background: #fef3c7; color: #b45309; }

        .alert-success { background: #dcfce7; color: #15803d; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; font-weight: 500; }
        .alert-error { background: #fee2e2; color: #b91c1c; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; font-weight: 500; }

        .toggle-link { color: var(--accent-blue); cursor: pointer; font-size: 13px; font-weight: 600; text-decoration: none; }
    </style>
</head>
<body>

<!-- Top Navigation Bar -->
<div class="top-navbar">
    <div class="navbar-left">
        <!-- VentureGuide Logo -->
        <div class="logo-box">
            <div class="logo-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="6" r="3" fill="#10b981"/>
                    <circle cx="6" cy="17" r="3" fill="#10b981"/>
                    <circle cx="18" cy="17" r="3" fill="#10b981"/>
                    <path d="M12 9V13M12 13L8.5 15M12 13L15.5 15" stroke="#10b981" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </div>
            <span>Venture<span class="logo-green">Guide</span></span>
        </div>
        <input type="text" class="search-input" placeholder="Search Portal...">
    </div>

    <!-- Right Side Header Icons & User Details Dropdown -->
    <div class="navbar-right">
        <span><?php echo date("F-Y"); ?></span>
        <span>English</span>

        <!-- User Profile Dropdown Component -->
        <div class="user-dropdown-wrapper">
            <div class="user-profile-trigger" onclick="toggleDropdown()">
                <div class="user-avatar"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
                <div class="user-details">
                    <div class="user-name"><?php echo htmlspecialchars($user_name); ?></div>
                    <div class="user-role"><?php echo htmlspecialchars($user_role); ?></div>
                </div>
                <span class="dropdown-arrow">▼</span>
            </div>

            <!-- Dropdown Menu -->
            <div class="profile-dropdown-menu" id="profileMenu">
                <div class="dropdown-header">
                    <span class="dropdown-header-title">Account Overview</span>
                </div>
                <div class="dropdown-item">
                    <span>Status</span>
                    <span class="badge badge-success"><?php echo htmlspecialchars($user_status); ?></span>
                </div>
                <a href="logout.php" class="dropdown-item logout">
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="app-layout">

    <!-- Left Sidebar Navigation Menu -->
    <div class="sidebar">
        <div class="nav-item active" id="nav-sec-overview" onclick="switchSection('sec-overview', this, 'Dashboard Overview')">
            Overview
        </div>
        <div class="nav-item" id="nav-sec-events" onclick="switchSection('sec-events', this, 'Platform Events & Webinars')">
            Platform Events
        </div>
        <div class="nav-item" id="nav-sec-ideas" onclick="switchSection('sec-ideas', this, 'Featured Business Ideas')">
            Business Ideas
        </div>
        <div class="nav-item" id="nav-sec-agents" onclick="switchSection('sec-agents', this, 'Experts & Mentors Directory')">
            Expert Directory
        </div>
        <div class="nav-item" id="nav-sec-templates" onclick="switchSection('sec-templates', this, 'Resource Templates & Decks')">
            Pitch Templates
        </div>
        <div class="nav-item" id="nav-sec-ask" onclick="switchSection('sec-ask', this, 'Ask an Expert')">
            Ask an Expert
        </div>
        <div class="nav-item" id="nav-sec-my-queries" onclick="switchSection('sec-my-queries', this, 'My Asked Questions')">
            My Questions
        </div>
        <div class="nav-item" id="nav-sec-feedback" onclick="switchSection('sec-feedback', this, 'Submit Platform Feedback')">
            Feedback
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="main-content">

        <!-- Page Header Breadcrumb -->
        <div class="page-header">
            <div class="page-title" id="current-title">Dashboard Overview</div>
            <div class="breadcrumb">User / <span id="current-breadcrumb">Overview</span></div>
        </div>

        <?php if (!empty($msg)): ?>
            <div class="alert-success"><?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- KPI Cards Grid (Clickable) -->
        <div class="kpi-grid">
            <div class="kpi-card pink" onclick="triggerNav('sec-events', 'Platform Events & Webinars')">
                <div class="kpi-body">
                    <div>
                        <div class="kpi-title">UPCOMING EVENTS</div>
                        <div class="kpi-value"><?php echo $events_count; ?></div>
                    </div>
                </div>
                <div class="kpi-footer">
                    <span>Webinars & Summits</span>
                    <span><?php echo $events_count; ?> Listed</span>
                </div>
            </div>

            <div class="kpi-card yellow" onclick="triggerNav('sec-ideas', 'Featured Business Ideas')">
                <div class="kpi-body">
                    <div>
                        <div class="kpi-title">FEATURED IDEAS</div>
                        <div class="kpi-value"><?php echo $ideas_count; ?></div>
                    </div>
                </div>
                <div class="kpi-footer">
                    <span>Startup Concepts</span>
                    <span><?php echo $ideas_count; ?> Ideas</span>
                </div>
            </div>

            <div class="kpi-card green" onclick="triggerNav('sec-agents', 'Experts & Mentors Directory')">
                <div class="kpi-body">
                    <div>
                        <div class="kpi-title">EXPERTS & MENTORS</div>
                        <div class="kpi-value"><?php echo $agents_count; ?></div>
                    </div>
                </div>
                <div class="kpi-footer">
                    <span>Available Agents</span>
                    <span><?php echo $agents_count; ?> Active</span>
                </div>
            </div>

            <div class="kpi-card teal" onclick="triggerNav('sec-my-queries', 'My Asked Questions')">
                <div class="kpi-body">
                    <div>
                        <div class="kpi-title">MY SUBMITTED QUERIES</div>
                        <div class="kpi-value"><?php echo $my_q_count; ?></div>
                    </div>
                </div>
                <div class="kpi-footer">
                    <span>My Inquiries</span>
                    <span><?php echo $my_q_count; ?> Asked</span>
                </div>
            </div>
        </div>

        <!-- Dynamic Content Sections -->

        <!-- 0. Overview Panel -->
        <div id="sec-overview" class="section-panel active">
            <div class="content-card">
                <div class="card-header-bar">
                    <span>Welcome Back, <?php echo htmlspecialchars($user_name); ?></span>
                </div>
                <div class="card-body-content">
                    <p style="color: var(--text-muted); font-size: 14px;">Welcome to your User Portal! Use the sidebar menu to browse upcoming events, explore startup business ideas, download resource templates, connect with mentors, or ask direct queries to expert agents.</p>
                </div>
            </div>
        </div>

        <!-- 1. Platform Events & Webinars -->
        <div id="sec-events" class="section-panel">
            <div class="content-card">
                <div class="card-header-bar">
                    <span>Platform Events & Webinars</span>
                </div>
                <div class="card-body-content">
                    <?php if ($events_list && mysqli_num_rows($events_list) > 0): ?>
                        <?php while ($ev = mysqli_fetch_assoc($events_list)): ?>
                            <div class="item-card">
                                <h4><?php echo htmlspecialchars($ev['event_title'] ?? $ev['title'] ?? 'Event'); ?></h4>
                                <p><?php echo htmlspecialchars($ev['event_desc'] ?? $ev['description'] ?? ''); ?></p>
                                <div class="item-meta">Date: <?php echo htmlspecialchars($ev['event_date'] ?? $ev['date'] ?? 'TBA'); ?></div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="font-size: 13px; color: var(--text-muted);">No upcoming events scheduled.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 2. Explore Featured Business Ideas -->
        <div id="sec-ideas" class="section-panel">
            <div class="content-card">
                <div class="card-header-bar">
                    <span>Explore Featured Business Ideas</span>
                </div>
                <div class="card-body-content">
                    <?php if ($ideas_list && mysqli_num_rows($ideas_list) > 0): ?>
                        <?php while ($idea = mysqli_fetch_assoc($ideas_list)): ?>
                            <div class="item-card">
                                <h4><?php echo htmlspecialchars($idea['title'] ?? 'Business Idea'); ?></h4>
                                <p><?php echo htmlspecialchars($idea['description'] ?? ''); ?></p>
                                <div class="item-meta">Investment Required: <?php echo htmlspecialchars($idea['investment_required'] ?? 'Contact Agent'); ?></div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="font-size: 13px; color: var(--text-muted);">No business ideas published yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 3. Available Experts & Mentors Directory -->
        <div id="sec-agents" class="section-panel">
            <div class="content-card">
                <div class="card-header-bar">
                    <span>Available Experts & Mentors Directory</span>
                </div>
                <div class="card-body-content">
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 15px;">
                        <?php if ($all_agents && mysqli_num_rows($all_agents) > 0): ?>
                            <?php while ($ag_card = mysqli_fetch_assoc($all_agents)): 
                                $ag_id = $ag_card['agent_id'] ?? $ag_card['admin_id'] ?? $ag_card['id'];
                                $ag_name = $ag_card['name'] ?? $ag_card['username'] ?? 'Expert Agent';
                                $ag_role = $ag_card['category'] ?? $ag_card['role'] ?? 'Startup Mentor';
                            ?>
                                <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 8px; padding: 15px; text-align: center;">
                                    <h4 style="margin: 0 0 5px 0; color: var(--accent-blue); font-size: 15px;">
                                        <?php echo htmlspecialchars($ag_name); ?>
                                    </h4>
                                    <p style="margin: 0 0 12px 0; font-size: 12px; color: var(--text-muted);">
                                        <?php echo htmlspecialchars($ag_role); ?>
                                    </p>
                                    <a href="agent_profile.php?id=<?php echo $ag_id; ?>" target="_blank" class="btn-primary" style="padding: 6px 12px; font-size: 12px;">
                                        View Profile
                                    </a>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p style="font-size: 13px; color: var(--text-muted);">No registered experts found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Resource Templates & Pitch Decks -->
        <div id="sec-templates" class="section-panel">
            <div class="content-card">
                <div class="card-header-bar">
                    <span>Resource Templates & Pitch Decks</span>
                </div>
                <div class="card-body-content">
                    <table>
                        <thead>
                            <tr>
                                <th>Template Title</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($templates_list && mysqli_num_rows($templates_list) > 0): ?>
                                <?php while ($tmpl = mysqli_fetch_assoc($templates_list)): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($tmpl['title'] ?? 'Template'); ?></strong></td>
                                        <td><a href="<?php echo htmlspecialchars($tmpl['file_path'] ?? '#'); ?>" target="_blank" class="toggle-link">Download Resource</a></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="2">No resource templates available.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 5. Ask an Expert / Agent -->
        <div id="sec-ask" class="section-panel">
            <div class="content-card">
                <div class="card-header-bar">
                    <span>Ask an Expert / Agent</span>
                </div>
                <div class="card-body-content">
                    <form action="user_dashboard.php" method="POST" class="form-grid">
                        <div class="form-group">
                            <label>Select Agent / Entrepreneur (Optional)</label>
                            <select name="agent_id">
                                <option value="0">-- General Query / Any Agent --</option>
                                <?php if ($agents_dropdown && mysqli_num_rows($agents_dropdown) > 0): ?>
                                    <?php while ($ag = mysqli_fetch_assoc($agents_dropdown)): ?>
                                        <option value="<?php echo $ag['agent_id'] ?? $ag['admin_id'] ?? $ag['id']; ?>">
                                            <?php echo htmlspecialchars($ag['name'] ?? $ag['username'] ?? 'Agent'); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Your Question</label>
                            <textarea name="question" rows="4" placeholder="e.g. How can I apply for seed funding for my startup idea?" required></textarea>
                        </div>
                        <div>
                            <button type="submit" name="submit_query" class="btn-primary">Submit Question</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- 6. My Asked Questions & Responses -->
        <div id="sec-my-queries" class="section-panel">
            <div class="content-card">
                <div class="card-header-bar">
                    <span>My Asked Questions & Responses</span>
                </div>
                <div class="card-body-content">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 40%;">Question</th>
                                <th style="width: 15%;">Status</th>
                                <th style="width: 45%;">Response</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($my_queries && mysqli_num_rows($my_queries) > 0): ?>
                                <?php while ($q = mysqli_fetch_assoc($my_queries)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($q['question'] ?? ''); ?></td>
                                        <td>
                                            <?php if (($q['status'] ?? '') == 'Answered'): ?>
                                                <span class="badge badge-success">Answered</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($q['answer'] ?? $q['response'] ?? 'Waiting for expert reply...'); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3">You haven't submitted any questions yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 7. Platform Feedback -->
        <div id="sec-feedback" class="section-panel">
            <div class="content-card">
                <div class="card-header-bar">
                    <span>Submit Platform Feedback</span>
                </div>
                <div class="card-body-content">
                    <form action="user_dashboard.php" method="POST" class="form-grid">
                        <div class="form-group">
                            <label>Your Experience & Suggestions</label>
                            <textarea name="feedback_text" rows="4" placeholder="Tell us how we can improve the platform..." required></textarea>
                        </div>
                        <div>
                            <button type="submit" name="submit_feedback" class="btn-primary">Submit Feedback</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    function switchSection(sectionId, element, title) {
        var panels = document.querySelectorAll('.section-panel');
        panels.forEach(function(panel) {
            panel.classList.remove('active');
        });

        var items = document.querySelectorAll('.nav-item');
        items.forEach(function(item) {
            item.classList.remove('active');
        });

        document.getElementById(sectionId).classList.add('active');
        if(element) element.classList.add('active');

        document.getElementById('current-title').innerText = title;
        document.getElementById('current-breadcrumb').innerText = title;
    }

    // Helper to click section via KPI Box
    function triggerNav(sectionId, title) {
        var targetNav = document.getElementById('nav-' + sectionId);
        switchSection(sectionId, targetNav, title);
    }

    // Toggle Profile Dropdown Menu
    function toggleDropdown() {
        document.getElementById("profileMenu").classList.toggle("show");
    }

    // Close Dropdown when clicking outside
    window.onclick = function(event) {
        if (!event.target.closest('.user-dropdown-wrapper')) {
            var dropdowns = document.getElementsByClassName("profile-dropdown-menu");
            for (var i = 0; i < dropdowns.length; i++) {
                var openDropdown = dropdowns[i];
                if (openDropdown.classList.contains('show')) {
                    openDropdown.classList.remove('show');
                }
            }
        }
    }
</script>

</body>
</html>




