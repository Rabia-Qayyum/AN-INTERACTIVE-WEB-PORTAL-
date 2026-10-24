<?php
mysqli_report(MYSQLI_REPORT_OFF);
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
include('db.php');

$msg = "";
$error = "";

// Fetch Current Admin Details
$admin_id = $_SESSION['admin_id'] ?? 1;
$admin_name = "Admin User";
$admin_email = "admin@platform.com";
$admin_status = "Super Admin";
$admin_role = "System Administrator";

$ad_profile_q = mysqli_query($conn, "SELECT * FROM `admin` WHERE admin_id = $admin_id");
if ($ad_profile_q && mysqli_num_rows($ad_profile_q) > 0) {
    $ad_data = mysqli_fetch_assoc($ad_profile_q);
    $admin_name = $ad_data['name'] ?? $ad_data['username'] ?? 'Admin User';
    $admin_email = $ad_data['email'] ?? 'admin@platform.com';
    $admin_role = $ad_data['role'] ?? 'System Administrator';
}

// 1. Handle Admin Reply to Queries
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_query'])) {
    $query_id = intval($_POST['query_id']);
    $answer = mysqli_real_escape_string($conn, trim($_POST['answer'] ?? ''));

    if (!empty($answer)) {
        $update = "UPDATE `query` SET answer = '$answer', status = 'Answered' WHERE query_id = $query_id";
        if (mysqli_query($conn, $update)) {
            $msg = "Query reply updated successfully!";
        } else {
            $error = "Failed to update reply: " . mysqli_error($conn);
        }
    }
}

// 2. Handle Agent Status Approval / Rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_agent_status'])) {
    $agent_id = intval($_POST['agent_id']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    $update_ag = "UPDATE `agent` SET status = '$status' WHERE agent_id = $agent_id";
    if (!mysqli_query($conn, $update_ag)) {
        $update_ag = "UPDATE `admin` SET status = '$status' WHERE admin_id = $agent_id";
        mysqli_query($conn, $update_ag);
    }
    $msg = "Agent status updated to $status!";
}

// 3. Handle Add Event
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_event'])) {
    $title = mysqli_real_escape_string($conn, trim($_POST['event_title'] ?? ''));
    $raw_date = trim($_POST['event_date'] ?? '');
    $desc  = mysqli_real_escape_string($conn, trim($_POST['event_desc'] ?? ''));

    $timestamp = strtotime($raw_date);
    if ($timestamp !== false) {
        $formatted_date = date('Y-m-d', $timestamp);
    } else {
        $formatted_date = date('Y-m-d');
    }

    if (!empty($title)) {
        $query = "INSERT INTO `event` (event_title, event_date, event_desc) VALUES ('$title', '$formatted_date', '$desc')";
        $res = mysqli_query($conn, $query);

        if (!$res) {
            $query_alt = "INSERT INTO `event` (title, event_date, description) VALUES ('$title', '$formatted_date', '$desc')";
            $res = mysqli_query($conn, $query_alt);
        }

        if ($res) {
            $msg = "New event published successfully!";
        } else {
            $error = "Failed to add event: " . mysqli_error($conn);
        }
    }
}

// 4. Handle Add Tutorial
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_tutorial'])) {
    $tut_title = mysqli_real_escape_string($conn, trim($_POST['tut_title'] ?? ''));
    $tut_link  = mysqli_real_escape_string($conn, trim($_POST['tut_link'] ?? ''));
    $tut_desc  = mysqli_real_escape_string($conn, trim($_POST['tut_desc'] ?? ''));

    if (!empty($tut_title)) {
        $query_tut = "INSERT INTO `tutorial` (title, video_url, description) VALUES ('$tut_title', '$tut_link', '$tut_desc')";
        $res_tut = mysqli_query($conn, $query_tut);
        if (!$res_tut) {
            $query_tut_alt = "INSERT INTO `tutorials` (title, link, description) VALUES ('$tut_title', '$tut_link', '$tut_desc')";
            $res_tut = mysqli_query($conn, $query_tut_alt);
        }

        if ($res_tut) {
            $msg = "Tutorial added successfully!";
        } else {
            $error = "Failed to add tutorial: " . mysqli_error($conn);
        }
    }
}

// 5. Handle Add Template
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_template'])) {
    $tmpl_title = mysqli_real_escape_string($conn, trim($_POST['tmpl_title'] ?? ''));
    $tmpl_path  = mysqli_real_escape_string($conn, trim($_POST['tmpl_path'] ?? ''));

    if (!empty($tmpl_title)) {
        $query_tmpl = "INSERT INTO `template` (title, file_path) VALUES ('$tmpl_title', '$tmpl_path')";
        $res_tmpl = mysqli_query($conn, $query_tmpl);
        if (!$res_tmpl) {
            $query_tmpl_alt = "INSERT INTO `templates` (file_name, file_path) VALUES ('$tmpl_title', '$tmpl_path')";
            $res_tmpl = mysqli_query($conn, $query_tmpl_alt);
        }

        if ($res_tmpl) {
            $msg = "Template uploaded successfully!";
        } else {
            $error = "Failed to upload template: " . mysqli_error($conn);
        }
    }
}

// System Stats
$user_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM `end_user`"))['total'] ?? 0;
$agent_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM `agent`"))['total'] ?? 0;
$ideas_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM `business_idea`"))['total'] ?? 0;
$templates_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM `template`"))['total'] ?? 0;

// Data Query Fetches
$queries_list = mysqli_query($conn, "SELECT * FROM `query` ORDER BY query_id DESC");
$agents_list = mysqli_query($conn, "SELECT * FROM `agent` ORDER BY agent_id ASC");
if (!$agents_list) {
    $agents_list = mysqli_query($conn, "SELECT * FROM `admin` WHERE role = 'agent' OR role = 'entrepreneur'");
}

$published_events = mysqli_query($conn, "SELECT * FROM `event` ORDER BY 1 DESC");
$uploaded_tutorials = mysqli_query($conn, "SELECT * FROM `tutorial` ORDER BY 1 DESC");
if (!$uploaded_tutorials) {
    $uploaded_tutorials = mysqli_query($conn, "SELECT * FROM `tutorials` ORDER BY 1 DESC");
}

$uploaded_templates = mysqli_query($conn, "SELECT * FROM `template` ORDER BY 1 DESC");
if (!$uploaded_templates) {
    $uploaded_templates = mysqli_query($conn, "SELECT * FROM `templates` ORDER BY 1 DESC");
}

$feedbacks_list = mysqli_query($conn, "SELECT * FROM `feedback` ORDER BY 1 DESC");

// Counts for Bell Notifications Alert
$pending_agents = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM `agent` WHERE status='Pending' OR status IS NULL"))['total'] ?? 0;
$pending_queries = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM `query` WHERE status!='Answered' OR status IS NULL"))['total'] ?? 0;
$total_feedbacks = mysqli_num_rows($feedbacks_list ?? mysqli_query($conn, "SELECT * FROM `feedback`"));
$total_notifications = $pending_agents + $pending_queries + $total_feedbacks;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Administrator Portal</title>
    <style>
        :root {
            --bg-body: #f1f5f9;
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

        .navbar-left { display: flex; align-items: center; gap: 15px; }

        /* VentureGuide Logo & Name Styling */
        .logo-box {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .logo-icon-box {
            width: 38px;
            height: 38px;
            background: rgba(255, 255, 255, 0.05);
            border: 1.5px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-icon-svg {
            width: 20px;
            height: 20px;
            fill: #10b981;
        }

        .site-name {
            font-size: 20px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.5px;
        }

        .site-name .highlight {
            color: #10b981;
            font-weight: 700;
        }

        .search-input {
            background: #ffffff;
            border: none;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            outline: none;
            width: 200px;
        }

        .navbar-right { display: flex; align-items: center; gap: 16px; font-size: 13px; color: #cbd5e1; }

        /* Circular Bell Notification Icon with Red Dot */
        .notification-bell-btn {
            position: relative;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.12);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.2s;
        }

        .notification-bell-btn:hover {
            background: rgba(255, 255, 255, 0.22);
        }

        .bell-icon-svg {
            width: 18px;
            height: 18px;
            fill: #ffffff;
        }

        .bell-red-dot {
            position: absolute;
            top: 7px;
            right: 7px;
            width: 8px;
            height: 8px;
            background-color: #ef4444;
            border-radius: 50%;
            border: 2px solid var(--topbar-bg);
        }

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

        /* KPI Stat Cards Grid */
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
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .kpi-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
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

        /* Forms & Inputs */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 6px; font-weight: 600; font-size: 12px; color: var(--text-dark); }
        input[type="text"], input[type="date"], select, textarea {
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
        }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }

        /* Tables */
        table { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 15px; }
        th, td { border-bottom: 1px solid var(--border-color); padding: 12px 14px; text-align: left; font-size: 13px; }
        th { background: #f8fafc; color: var(--text-muted); font-weight: 600; text-transform: uppercase; font-size: 11px; }

        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; }
        .badge-success { background: #dcfce7; color: #15803d; }
        .badge-warning { background: #fef3c7; color: #d97706; }
        .badge-danger { background: #fee2e2; color: var(--danger); }

        .alert-success { background: #dcfce7; color: #15803d; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; font-weight: 500; }
        .alert-error { background: #fee2e2; color: #b91c1c; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; font-weight: 500; }

        .toggle-link { color: var(--accent-blue); cursor: pointer; font-size: 13px; font-weight: 600; text-decoration: none; }
    </style>
</head>
<body>

<!-- Top Navigation Bar -->
<div class="top-navbar">
    <div class="navbar-left">
        <a href="admin_dashboard.php" class="logo-box">
            <div class="logo-icon-box">
                <svg class="logo-icon-svg" viewBox="0 0 24 24">
                    <path d="M12 2a3 3 0 1 0 0 6 3 3 0 0 0 0-6zm-6 13a3 3 0 1 0 0 6 3 3 0 0 0 0-6zm12 0a3 3 0 1 0 0 6 3 3 0 0 0 0-6zm-5.17-5.59a1 1 0 0 0-1.66 0l-3.5 5.5a1 1 0 0 0 1.66 1.08L12 12.38l2.67 3.61a1 1 0 1 0 1.66-1.08l-3.5-5.5z"/>
                </svg>
            </div>
            <span class="site-name">Venture<span class="highlight">Guide</span></span>
        </a>
        <input type="text" class="search-input" placeholder="Search Admin Panel...">
    </div>

    <!-- Right Side Header Icons & User Details Dropdown -->
    <div class="navbar-right">
        <span><?php echo date("F-Y"); ?></span>
        <span> English</span>

        <!-- Circular Bell Icon with Red Dot Badge -->
        <div class="notification-bell-btn" onclick="showAlertNotifications()" title="System Notifications">
            <svg class="bell-icon-svg" viewBox="0 0 24 24">
                <path d="M12 22a2.98 2.98 0 0 0 2.818-2H9.182A2.98 2.98 0 0 0 12 22zm7-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C8.63 5.36 7 7.92 7 11v5l-2 2v1h14v-1l-2-2z"/>
            </svg>
            <?php if ($total_notifications > 0): ?>
                <span class="bell-red-dot"></span>
            <?php endif; ?>
        </div>

        <div class="user-avatar"><?php echo strtoupper(substr($admin_name, 0, 1)); ?></div>

        <div class="user-dropdown-wrapper">
            <div class="user-profile-trigger" onclick="toggleDropdown()">
                <div class="user-details">
                    <div class="user-name"><?php echo htmlspecialchars($admin_name); ?></div>
                    <div class="user-role"><?php echo htmlspecialchars($admin_role); ?></div>
                </div>
                <span class="dropdown-arrow">▼</span>
            </div>

            <div class="profile-dropdown-menu" id="profileMenu">
                <div class="dropdown-header">
                    <span class="dropdown-header-title">Account Overview</span>
                </div>
                <div class="dropdown-item">
                    <span>Status</span>
                    <span class="badge badge-success"><?php echo htmlspecialchars($admin_status); ?></span>
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
        <div class="nav-item active" data-sec="sec-dashboard" onclick="switchSection('sec-dashboard', this, 'Dashboard Overview')">
            Overview
        </div>
        <div class="nav-item" data-sec="sec-queries" onclick="switchSection('sec-queries', this, 'Manage User Queries')">
            User Queries
        </div>
        <div class="nav-item" data-sec="sec-agents" onclick="switchSection('sec-agents', this, 'Agent Approval & Management')">
            Agent Management
        </div>
        <div class="nav-item" data-sec="sec-events" onclick="switchSection('sec-events', this, 'Platform Events & Webinars')">
            Platform Events
        </div>
        <div class="nav-item" data-sec="sec-tutorials" onclick="switchSection('sec-tutorials', this, 'Learning Center & Tutorials')">
            Video Tutorials
        </div>
        <div class="nav-item" data-sec="sec-templates" onclick="switchSection('sec-templates', this, 'Resource Templates')">
            Pitch Templates
        </div>
        <div class="nav-item" data-sec="sec-feedback" onclick="switchSection('sec-feedback', this, 'Platform Feedbacks')">
            User Feedback
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="main-content">

        <!-- Page Header Breadcrumb -->
        <div class="page-header">
            <div class="page-title" id="current-title">Dashboard Overview</div>
            <div class="breadcrumb">Admin / <span id="current-breadcrumb">Overview</span></div>
        </div>

        <?php if (!empty($msg)): ?>
            <div class="alert-success"><?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- KPI Cards Grid (Clickable) -->
        <div class="kpi-grid">
            <div class="kpi-card pink" onclick="switchSection('sec-queries', document.querySelector('[data-sec=\'sec-queries\']'), 'Manage User Queries')" style="cursor: pointer;">
                <div class="kpi-body">
                    <div>
                        <div class="kpi-title">TOTAL USERS</div>
                        <div class="kpi-value"><?php echo $user_count; ?></div>
                    </div>
                </div>
                <div class="kpi-footer">
                    <span>Registered</span>
                    <span><?php echo $user_count; ?> Accounts</span>
                </div>
            </div>

            <div class="kpi-card yellow" onclick="switchSection('sec-agents', document.querySelector('[data-sec=\'sec-agents\']'), 'Agent Approval & Management')" style="cursor: pointer;">
                <div class="kpi-body">
                    <div>
                        <div class="kpi-title">TOTAL AGENTS</div>
                        <div class="kpi-value"><?php echo $agent_count; ?></div>
                    </div>
                </div>
                <div class="kpi-footer">
                    <span>Entrepreneurs</span>
                    <span><?php echo $agent_count; ?> Active</span>
                </div>
            </div>

            <div class="kpi-card green" onclick="switchSection('sec-events', document.querySelector('[data-sec=\'sec-events\']'), 'Platform Events & Webinars')" style="cursor: pointer;">
                <div class="kpi-body">
                    <div>
                        <div class="kpi-title">BUSINESS IDEAS</div>
                        <div class="kpi-value"><?php echo $ideas_count; ?></div>
                    </div>
                </div>
                <div class="kpi-footer">
                    <span>Published</span>
                    <span><?php echo $ideas_count; ?> Ideas</span>
                </div>
            </div>

            <div class="kpi-card teal" onclick="switchSection('sec-templates', document.querySelector('[data-sec=\'sec-templates\']'), 'Resource Templates')" style="cursor: pointer;">
                <div class="kpi-body">
                    <div>
                        <div class="kpi-title">TEMPLATES</div>
                        <div class="kpi-value"><?php echo $templates_count; ?></div>
                    </div>
                </div>
                <div class="kpi-footer">
                    <span>Resources</span>
                    <span><?php echo $templates_count; ?> Files</span>
                </div>
            </div>
        </div>

        <!-- Dynamic Content Sections -->

        <!-- 0. Overview / Main Panel -->
        <div id="sec-dashboard" class="section-panel active">
            
            <!-- Quick Action Shortcuts -->
            <div style="display: flex; gap: 12px; margin-bottom: 20px;">
                <button class="btn-primary" onclick="switchSection('sec-agents', document.querySelector('[data-sec=\'sec-agents\']'), 'Agent Approval & Management')">
                     Review Pending Agents
                </button>
                <button class="btn-primary" onclick="switchSection('sec-queries', document.querySelector('[data-sec=\'sec-queries\']'), 'Manage User Queries')">
                     View User Inquiries
                </button>
                <button class="btn-primary" onclick="switchSection('sec-events', document.querySelector('[data-sec=\'sec-events\']'), 'Platform Events & Webinars', 'event_title')">
                     Add New Event
                </button>
            </div>

            <!-- Overview Grid Layout -->
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                
                <!-- Left Table: Recent Pending Queries -->
                <div class="content-card">
                    <div class="card-header-bar">
                        <span> Recent Pending Inquiries</span>
                    </div>
                    <div class="card-body-content" style="padding: 0;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Question</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($queries_list && mysqli_num_rows($queries_list) > 0): ?>
                                    <?php $q_cnt = 0; mysqli_data_seek($queries_list, 0); while ($q = mysqli_fetch_assoc($queries_list)): if($q_cnt++ >= 5) break; ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($q['question'] ?? ''); ?></td>
                                            <td>
                                                <span class="badge <?php echo (($q['status']??'')=='Answered') ? 'badge-success':'badge-warning'; ?>">
                                                    <?php echo htmlspecialchars($q['status'] ?? 'Pending'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="javascript:void(0)" onclick="switchSection('sec-queries', document.querySelector('[data-sec=\'sec-queries\']'), 'Manage User Queries')" class="toggle-link">
                                                    Manage
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="3" style="text-align: center; color: var(--text-muted); padding: 15px;">No recent inquiries found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Right Side Card: Admin Summary -->
                <div class="content-card">
                    <div class="card-header-bar">
                        <span> System Status</span>
                    </div>
                    <div class="card-body-content">
                        <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 8px;">
                            Logged Administrator: <strong style="color: var(--text-dark);"><?php echo htmlspecialchars($admin_name); ?></strong>
                        </p>
                        <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 12px;">
                            Role: <span class="badge badge-success"><?php echo htmlspecialchars($admin_role); ?></span>
                        </p>
                        <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 12px 0;">
                        <p style="font-size: 12px; color: var(--text-muted); line-height: 1.5;">
                            Select any card or navigation tab to approve agents, respond to user inquiries, publish events, or view feedback.
                        </p>
                    </div>
                </div>

            </div>
        </div>

        <!-- 1. User Queries Section -->
        <div id="sec-queries" class="section-panel">
            <div class="content-card">
                <div class="card-header-bar">
                    <span>Manage User Queries & Inquiries </span>
                </div>
                <div class="card-body-content">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 5%;">ID</th>
                                <th style="width: 35%;">Question</th>
                                <th style="width: 10%;">Status</th>
                                <th style="width: 25%;">Current Answer</th>
                                <th style="width: 25%;">Action (Admin Reply)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($queries_list && mysqli_num_rows($queries_list) > 0): ?>
                                <?php mysqli_data_seek($queries_list, 0); while ($q = mysqli_fetch_assoc($queries_list)): ?>
                                    <tr>
                                        <td>#<?php echo $q['query_id'] ?? $q['id']; ?></td>
                                        <td><?php echo htmlspecialchars($q['question']); ?></td>
                                        <td>
                                            <?php if (($q['status'] ?? '') === 'Answered'): ?>
                                                <span class="badge badge-success">Answered</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars(!empty($q['answer']) ? $q['answer'] : 'Awaiting Response'); ?></td>
                                        <td>
                                            <form method="POST" action="admin_dashboard.php" style="display: flex; gap: 6px;">
                                                <input type="hidden" name="query_id" value="<?php echo $q['query_id'] ?? $q['id']; ?>">
                                                <input type="text" name="answer" placeholder="Type answer..." required>
                                                <button type="submit" name="reply_query" class="btn-primary btn-sm">
                                                    <?php echo (!empty($q['answer'])) ? 'Update' : 'Reply'; ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5">No queries found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 2. Agent Management Section -->
        <div id="sec-agents" class="section-panel">
            <div class="content-card">
                <div class="card-header-bar">
                    <span>Agent Approval & Management </span>
                </div>
                <div class="card-body-content">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name / Username</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Change Status</th>
                                <th>Profile Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($agents_list && mysqli_num_rows($agents_list) > 0): ?>
                                <?php mysqli_data_seek($agents_list, 0); while ($ag = mysqli_fetch_assoc($agents_list)): ?>
                                    <?php 
                                        $ag_id = $ag['agent_id'] ?? $ag['admin_id'] ?? $ag['id']; 
                                        $ag_status = $ag['status'] ?? 'Pending';
                                    ?>
                                    <tr>
                                        <td>#<?php echo $ag_id; ?></td>
                                        <td><b><?php echo htmlspecialchars($ag['name'] ?? $ag['username'] ?? 'Agent'); ?></b></td>
                                        <td><?php echo htmlspecialchars($ag['email'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php if ($ag_status === 'Approved'): ?>
                                                <span class="badge badge-success">Approved</span>
                                            <?php elseif ($ag_status === 'Rejected'): ?>
                                                <span class="badge badge-danger">Rejected</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form method="POST" action="admin_dashboard.php" style="display: flex; gap: 6px;">
                                                <input type="hidden" name="agent_id" value="<?php echo $ag_id; ?>">
                                                <select name="status" style="padding: 4px; font-size: 12px;">
                                                    <option value="Approved" <?php if($ag_status=='Approved') echo 'selected'; ?>>Approve</option>
                                                    <option value="Pending" <?php if($ag_status=='Pending') echo 'selected'; ?>>Pending</option>
                                                    <option value="Rejected" <?php if($ag_status=='Rejected') echo 'selected'; ?>>Reject</option>
                                                </select>
                                                <button type="submit" name="update_agent_status" class="btn-primary btn-sm">Save</button>
                                            </form>
                                        </td>
                                        <td>
                                            <a href="agent_profile.php?id=<?php echo $ag_id; ?>" class="toggle-link" target="_blank">View Profile</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6">No agents registered currently.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 3. Platform Events Section -->
        <div id="sec-events" class="section-panel">
            <div class="content-card">
                <div class="card-header-bar">
                    <span>Publish Platform Events & Webinars </span>
                </div>
                <div class="card-body-content">
                    <form action="admin_dashboard.php" method="POST" class="form-grid">
                        <div>
                            <div class="form-group">
                                <label>Event Title</label>
                                <input type="text" name="event_title" id="event_title" placeholder="e.g. Startup Pitching Summit 2026" required>
                            </div>
                            <div class="form-group">
                                <label>Event Date</label>
                                <input type="date" name="event_date" required>
                            </div>
                        </div>
                        <div>
                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="event_desc" rows="4" placeholder="Brief event details..."></textarea>
                            </div>
                            <button type="submit" name="add_event" class="btn-primary">Publish Event</button>
                        </div>
                    </form>

                    <h4 style="margin-top: 25px; margin-bottom: 10px; font-size: 14px;">Published Events List</h4>
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Date</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($published_events && mysqli_num_rows($published_events) > 0): ?>
                                <?php mysqli_data_seek($published_events, 0); while ($ev = mysqli_fetch_assoc($published_events)): ?>
                                    <tr>
                                        <td><b><?php echo htmlspecialchars($ev['event_title'] ?? $ev['title'] ?? 'Event'); ?></b></td>
                                        <td><?php echo htmlspecialchars($ev['event_date'] ?? $ev['date'] ?? 'TBA'); ?></td>
                                        <td><?php echo htmlspecialchars($ev['event_desc'] ?? $ev['description'] ?? ''); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3">No events published yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 4. Video Tutorials Section -->
        <div id="sec-tutorials" class="section-panel">
            <div class="content-card">
                <div class="card-header-bar">
                    <span>Learning Center & Video Tutorials </span>
                </div>
                <div class="card-body-content">
                    <form action="admin_dashboard.php" method="POST" class="form-grid">
                        <div>
                            <div class="form-group">
                                <label>Tutorial Title</label>
                                <input type="text" name="tut_title" placeholder="e.g. How to Pitch to Investors" required>
                            </div>
                            <div class="form-group">
                                <label>Video Link / Resource URL</label>
                                <input type="text" name="tut_link" placeholder="https://..." required>
                            </div>
                        </div>
                        <div>
                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="tut_desc" rows="4" placeholder="Resource description..."></textarea>
                            </div>
                            <button type="submit" name="add_tutorial" class="btn-primary">Add Tutorial</button>
                        </div>
                    </form>

                    <h4 style="margin-top: 25px; margin-bottom: 10px; font-size: 14px;">Uploaded Tutorials List</h4>
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Resource Link</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($uploaded_tutorials && mysqli_num_rows($uploaded_tutorials) > 0): ?>
                                <?php mysqli_data_seek($uploaded_tutorials, 0); while ($tut = mysqli_fetch_assoc($uploaded_tutorials)): ?>
                                    <tr>
                                        <td><b><?php echo htmlspecialchars($tut['title'] ?? 'Tutorial'); ?></b></td>
                                        <td><a href="<?php echo htmlspecialchars($tut['video_url'] ?? $tut['link'] ?? '#'); ?>" target="_blank" class="toggle-link">Open Link</a></td>
                                        <td><?php echo htmlspecialchars($tut['description'] ?? ''); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3">No tutorials uploaded yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 5. Resource Templates Section -->
        <div id="sec-templates" class="section-panel">
            <div class="content-card">
                <div class="card-header-bar">
                    <span>Resource Templates & Pitch Decks </span>
                </div>
                <div class="card-body-content">
                    <form action="admin_dashboard.php" method="POST" class="form-grid">
                        <div class="form-group">
                            <label>Template Name</label>
                            <input type="text" name="tmpl_title" placeholder="e.g. Financial Model Excel Sheet" required>
                        </div>
                        <div class="form-group">
                            <label>File Path / Download Link</label>
                            <input type="text" name="tmpl_path" placeholder="uploads/template.pdf" required>
                        </div>
                        <div style="grid-column: span 2;">
                            <button type="submit" name="add_template" class="btn-primary">Add Template</button>
                        </div>
                    </form>

                    <h4 style="margin-top: 25px; margin-bottom: 10px; font-size: 14px;">Uploaded Resource Files</h4>
                    <table>
                        <thead>
                            <tr>
                                <th>Template Title</th>
                                <th>File Path</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($uploaded_templates && mysqli_num_rows($uploaded_templates) > 0): ?>
                                <?php mysqli_data_seek($uploaded_templates, 0); while ($tmpl = mysqli_fetch_assoc($uploaded_templates)): ?>
                                    <tr>
                                        <td><b><?php echo htmlspecialchars($tmpl['title'] ?? $tmpl['file_name'] ?? 'Template'); ?></b></td>
                                        <td><code><?php echo htmlspecialchars($tmpl['file_path'] ?? ''); ?></code></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="2">No templates uploaded yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 6. Feedbacks Section -->
        <div id="sec-feedback" class="section-panel">
            <div class="content-card">
                <div class="card-header-bar">
                    <span>User Platform Feedbacks </span>
                </div>
                <div class="card-body-content">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 10%;">ID</th>
                                <th>Feedback Message</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($feedbacks_list && mysqli_num_rows($feedbacks_list) > 0): ?>
                                <?php mysqli_data_seek($feedbacks_list, 0); while ($fb = mysqli_fetch_assoc($feedbacks_list)): ?>
                                    <tr>
                                        <td>#<?php echo $fb['feedback_id'] ?? $fb['id']; ?></td>
                                        <td><?php echo htmlspecialchars($fb['message'] ?? $fb['feedback_text'] ?? ''); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="2">No feedback records found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    function showAlertNotifications() {
        var pAgents = <?php echo (int)$pending_agents; ?>;
        var pQueries = <?php echo (int)$pending_queries; ?>;
        var tFeedbacks = <?php echo (int)$total_feedbacks; ?>;

        var message = "🔔 SYSTEM NOTIFICATIONS OVERVIEW\n------------------------------------\n";
        message += "• Pending Agent Approvals: " + pAgents + "\n";
        message += "• Unanswered User Queries: " + pQueries + "\n";
        message += "• User Feedbacks Received: " + tFeedbacks + "\n\n";

        if (pAgents > 0) {
            if (confirm(message + "Click OK to go to Agent Approval Management.")) {
                switchSection('sec-agents', document.querySelector('[data-sec="sec-agents"]'), 'Agent Approval & Management');
            }
        } else if (pQueries > 0) {
            if (confirm(message + "Click OK to go to User Queries.")) {
                switchSection('sec-queries', document.querySelector('[data-sec="sec-queries"]'), 'Manage User Queries');
            }
        } else {
            alert(message + "All pending requests are up to date!");
        }
    }

    function switchSection(sectionId, element, title, focusInputId) {
        var panels = document.querySelectorAll('.section-panel');
        panels.forEach(function(panel) {
            panel.classList.remove('active');
        });

        var items = document.querySelectorAll('.nav-item');
        items.forEach(function(item) {
            item.classList.remove('active');
        });

        var targetPanel = document.getElementById(sectionId);
        if(targetPanel) {
            targetPanel.classList.add('active');
        }

        if(element) {
            element.classList.add('active');
        } else {
            var matchedNav = document.querySelector('[data-sec="' + sectionId + '"]');
            if(matchedNav) matchedNav.classList.add('active');
        }

        document.getElementById('current-title').innerText = title;
        document.getElementById('current-breadcrumb').innerText = title;

        if(focusInputId) {
            setTimeout(function() {
                var input = document.getElementById(focusInputId);
                if(input) input.focus();
            }, 100);
        }

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function toggleDropdown() {
        document.getElementById("profileMenu").classList.toggle("show");
    }

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