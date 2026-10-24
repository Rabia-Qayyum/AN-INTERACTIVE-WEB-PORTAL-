<?php
mysqli_report(MYSQLI_REPORT_OFF);
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
include('db.php');

// Check agent session
$agent_id = $_SESSION['agent_id'] ?? 1;

// Fetch Current Agent Profile Status & Details
$agent_name = "Agent User";
$agent_email = "agent@platform.com";
$agent_status = "Approved";

$ag_profile_q = mysqli_query($conn, "SELECT * FROM `agent` WHERE agent_id = '$agent_id'");
if ($ag_profile_q && mysqli_num_rows($ag_profile_q) > 0) {
    $ag_data = mysqli_fetch_assoc($ag_profile_q);
    $agent_name = $ag_data['name'] ?? $ag_data['username'] ?? 'Agent User';
    $agent_email = $ag_data['email'] ?? 'agent@platform.com';
    $agent_status = $ag_data['status'] ?? 'Approved';
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Verify & Create User Account
    if (isset($_POST['verify_create_user'])) {
        $u_name = mysqli_real_escape_string($conn, trim($_POST['u_name'] ?? ''));
        $u_email = mysqli_real_escape_string($conn, trim($_POST['u_email'] ?? ''));
        $u_pass = mysqli_real_escape_string($conn, trim($_POST['u_password'] ?? ''));

        if (!empty($u_name) && !empty($u_email) && !empty($u_pass)) {
            $ins_user = "INSERT INTO `end_user` (name, email, password, agent_id) VALUES ('$u_name', '$u_email', '$u_pass', '$agent_id')";
            if (!mysqli_query($conn, $ins_user)) {
                $ins_user = "INSERT INTO `end_user` (name, email, password) VALUES ('$u_name', '$u_email', '$u_pass')";
                $res = mysqli_query($conn, $ins_user);
            } else {
                $res = true;
            }

            if ($res) {
                $_SESSION['msg'] = "User Identity Verified & Account Created Successfully!";
            } else {
                $_SESSION['error'] = "Failed to create user account: " . mysqli_error($conn);
            }
        } else {
            $_SESSION['error'] = "Please fill in all user account fields.";
        }
        header("Location: agent_dashboard.php?tab=users");
        exit();
    }

    // 2. Post Business Idea
    if (isset($_POST['publish_idea'])) {
        $title = mysqli_real_escape_string($conn, trim($_POST['idea_title'] ?? ''));
        $desc = mysqli_real_escape_string($conn, trim($_POST['idea_desc'] ?? ''));
        $budget = mysqli_real_escape_string($conn, trim($_POST['idea_budget'] ?? ''));

        if (!empty($title)) {
            $ins_idea = "INSERT INTO `business_idea` (title, description, investment_required, agent_id) VALUES ('$title', '$desc', '$budget', '$agent_id')";
            $res = mysqli_query($conn, $ins_idea);

            if (!$res) {
                $ins_idea_alt = "INSERT INTO `business_idea` (title, description, investment_required) VALUES ('$title', '$desc', '$budget')";
                $res = mysqli_query($conn, $ins_idea_alt);
            }

            if ($res) {
                $_SESSION['msg'] = "Business Idea posted successfully!";
            } else {
                $_SESSION['error'] = "Failed to post idea: " . mysqli_error($conn);
            }
        } else {
            $_SESSION['error'] = "Please provide an idea title.";
        }
        header("Location: agent_dashboard.php?tab=ideas");
        exit();
    }

    // 3. Reply to Query
    if (isset($_POST['reply_query'])) {
        $q_id = intval($_POST['query_id']);
        $ans = mysqli_real_escape_string($conn, trim($_POST['answer'] ?? ''));

        if (!empty($ans)) {
            $upd_q = "UPDATE `query` SET answer = '$ans', status = 'Answered', agent_id = '$agent_id' WHERE query_id = $q_id";
            if (!mysqli_query($conn, $upd_q)) {
                $upd_q = "UPDATE `query` SET answer = '$ans', status = 'Answered' WHERE query_id = $q_id";
                mysqli_query($conn, $upd_q);
            }
            $_SESSION['msg'] = "Query answered successfully!";
        } else {
            $_SESSION['error'] = "Please write an answer before submitting.";
        }
        header("Location: agent_dashboard.php?tab=queries");
        exit();
    }

    // 4. Upload Template
    if (isset($_POST['upload_template'])) {
        $t_title = mysqli_real_escape_string($conn, trim($_POST['tmpl_title'] ?? ''));
        $t_path = mysqli_real_escape_string($conn, trim($_POST['tmpl_path'] ?? ''));

        if (!empty($t_title)) {
            $ins_tmpl = "INSERT INTO `template` (title, file_path, agent_id) VALUES ('$t_title', '$t_path', '$agent_id')";
            if (!mysqli_query($conn, $ins_tmpl)) {
                $ins_tmpl = "INSERT INTO `template` (title, file_path) VALUES ('$t_title', '$t_path')";
                mysqli_query($conn, $ins_tmpl);
            }
            $_SESSION['msg'] = "Resource Template uploaded successfully!";
        } else {
            $_SESSION['error'] = "Please enter a resource title.";
        }
        header("Location: agent_dashboard.php?tab=templates");
        exit();
    }
}

// Flash Notifications
$msg = $_SESSION['msg'] ?? "";
$error = $_SESSION['error'] ?? "";
unset($_SESSION['msg'], $_SESSION['error']);

// Fetch Data
$my_ideas = mysqli_query($conn, "SELECT * FROM `business_idea` WHERE agent_id = '$agent_id' ORDER BY 1 DESC");
if (!$my_ideas) { $my_ideas = mysqli_query($conn, "SELECT * FROM `business_idea` ORDER BY 1 DESC"); }

$user_queries = mysqli_query($conn, "SELECT * FROM `query` WHERE agent_id = '$agent_id' OR agent_id IS NULL ORDER BY query_id DESC");
if (!$user_queries) { $user_queries = mysqli_query($conn, "SELECT * FROM `query` ORDER BY query_id DESC"); }

$registered_users = mysqli_query($conn, "SELECT * FROM `end_user` ORDER BY 1 DESC");
$feedback_list = mysqli_query($conn, "SELECT * FROM `feedback` ORDER BY 1 DESC");

$my_templates = mysqli_query($conn, "SELECT * FROM `template` WHERE agent_id = '$agent_id' ORDER BY 1 DESC");
if (!$my_templates) { $my_templates = mysqli_query($conn, "SELECT * FROM `template` ORDER BY 1 DESC"); }

// Counts
$ideas_count = $my_ideas ? mysqli_num_rows($my_ideas) : 0;
$queries_count = $user_queries ? mysqli_num_rows($user_queries) : 0;
$users_count = $registered_users ? mysqli_num_rows($registered_users) : 0;
$templates_count = $my_templates ? mysqli_num_rows($my_templates) : 0;

$active_tab = $_GET['tab'] ?? 'ideas';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VentureGuide - Agent Dashboard Portal</title>
    <style>
        :root {
            --bg-main: #f1f5f9;
            --header-bg: #1e293b;
            --sidebar-bg: #ffffff;
            --accent-blue: #2563eb;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --card-pink: #f43f5e;
            --card-yellow: #eab308;
            --card-green: #10b981;
            --card-cyan: #06b6d4;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background: var(--bg-main); color: var(--text-dark); display: flex; flex-direction: column; min-height: 100vh; }

        /* Top Header Navbar */
        .top-navbar {
            background: var(--header-bg);
            height: 64px;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: #fff;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        /* Group for Logo + Search Box to align together */
        .header-left-group {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        /* Brand Section & Scaled Logo Container */
        .brand-section { 
            display: flex; 
            align-items: center; 
            gap: 10px; 
            font-weight: 800; 
            font-size: 20px; 
            color: #ffffff; 
            text-decoration: none; 
        }

        .brand-logo-box {
            width: 32px;
            height: 32px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .brand-logo-box svg {
            width: 18px;
            height: 18px;
        }

        .brand-section span.logo-highlight { color: #10b981; }

        .search-bar-container {
            display: flex;
            align-items: center;
        }

        .search-input {
            background: #ffffff;
            border: none;
            padding: 7px 14px;
            border-radius: 6px;
            font-size: 13px;
            outline: none;
            width: 220px;
        }

        .header-right { display: flex; align-items: center; gap: 20px; font-size: 13px; color: #cbd5e1; }

        /* Notification Bell Button */
        .notification-btn {
            position: relative;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            outline: none;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.2s;
        }

        .notification-btn:hover { background: rgba(255, 255, 255, 0.2); }
        .notification-btn svg { width: 18px; height: 18px; fill: #ffffff; }

        .notification-red-dot {
            position: absolute;
            top: 6px;
            right: 6px;
            width: 8px;
            height: 8px;
            background: #ef4444;
            border-radius: 50%;
            border: 2px solid var(--header-bg);
            box-shadow: 0 0 6px rgba(239, 68, 68, 0.8);
        }

        /* USER PROFILE / AVATAR LINK */
        .user-profile-btn { 
            display: flex; 
            align-items: center; 
            gap: 10px; 
            background: transparent;
            border: none;
            cursor: pointer;
            color: inherit;
            padding: 6px 10px;
            border-radius: 8px;
            transition: background 0.2s ease;
        }
        .user-profile-btn:hover { background: rgba(255, 255, 255, 0.1); }
        
        .avatar-circle {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #2563eb;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .user-details { text-align: right; }
        .user-details .user-name { font-weight: 700; color: #ffffff; display: block; font-size: 13px; }
        .user-details .user-role { font-size: 11px; color: #94a3b8; display: block; }

        /* PROFILE MODAL POPUP */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.active { display: flex; }

        .profile-card-modal {
            background: #ffffff;
            width: 380px;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            animation: popIn 0.2s ease-out;
        }

        @keyframes popIn {
            from { transform: scale(0.95); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .profile-modal-header {
            background: var(--header-bg);
            padding: 24px;
            text-align: center;
            color: #ffffff;
            position: relative;
        }

        .profile-modal-avatar {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: #2563eb;
            color: #ffffff;
            font-size: 24px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            border: 3px solid #ffffff;
        }

        .profile-modal-body { padding: 20px 24px; }
        .profile-info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--border-color); font-size: 13px; }
        .profile-info-row:last-child { border-bottom: none; }
        .profile-info-label { color: var(--text-muted); font-weight: 600; }
        .profile-info-val { color: var(--text-dark); font-weight: 700; }

        .modal-close-btn {
            width: 100%;
            padding: 10px;
            background: var(--bg-main);
            border: none;
            border-top: 1px solid var(--border-color);
            font-size: 13px;
            font-weight: 700;
            color: var(--text-dark);
            cursor: pointer;
        }
        .modal-close-btn:hover { background: #e2e8f0; }

        /* Dashboard Layout Structure */
        .dashboard-wrapper { display: flex; flex: 1; }

        .sidebar {
            width: 240px;
            background: var(--sidebar-bg);
            border-right: 1px solid var(--border-color);
            padding: 20px 0;
            flex-shrink: 0;
        }

        .nav-item {
            padding: 12px 24px;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-muted);
            cursor: pointer;
            display: block;
            transition: all 0.2s ease;
            text-decoration: none;
            border-left: 4px solid transparent;
        }

        .nav-item:hover { color: var(--accent-blue); background: #f8fafc; }
        .nav-item.active {
            color: var(--accent-blue);
            background: #eff6ff;
            border-left-color: var(--accent-blue);
            font-weight: 700;
        }

        .main-content { flex-grow: 1; padding: 28px 36px; overflow-y: auto; }

        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .page-title { font-size: 20px; font-weight: 700; color: #0f172a; }
        .breadcrumb { font-size: 12px; color: var(--text-muted); }

        .alert-success { background: #dcfce7; color: #15803d; padding: 12px 18px; border-radius: 6px; margin-bottom: 20px; font-weight: 600; font-size: 13px; }
        .alert-error { background: #fee2e2; color: #b91c1c; padding: 12px 18px; border-radius: 6px; margin-bottom: 20px; font-weight: 600; font-size: 13px; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 28px;
        }

        .stat-card {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
            border: 1px solid var(--border-color);
            background: #fff;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            cursor: pointer;
            user-select: none;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.12);
        }

        .stat-card-top {
            padding: 16px 20px;
            color: #ffffff;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-card-top .title { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.95; }
        .stat-card-top .number { font-size: 32px; font-weight: 800; margin-top: 4px; }

        .stat-card.pink .stat-card-top { background: var(--card-pink); }
        .stat-card.yellow .stat-card-top { background: var(--card-yellow); }
        .stat-card.green .stat-card-top { background: var(--card-green); }
        .stat-card.cyan .stat-card-top { background: var(--card-cyan); }

        .stat-card-bottom {
            background: #ffffff;
            padding: 10px 20px;
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            border-top: 1px solid rgba(0,0,0,0.05);
        }
        .stat-card-bottom span.val { color: var(--text-dark); font-weight: 700; }

        .panel { display: none; }
        .panel.active { display: block; animation: fadeIn 0.25s ease-in-out; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(4px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card-box {
            background: #ffffff;
            border-radius: 10px;
            border: 1px solid var(--border-color);
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            overflow: hidden;
            margin-bottom: 24px;
        }

        .card-box-header {
            padding: 16px 24px;
            border-bottom: 1px solid var(--border-color);
            font-size: 15px;
            font-weight: 700;
            color: #1e293b;
        }

        .card-box-body { padding: 24px; }

        .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 18px; }
        label { display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px; }

        input[type="text"], input[type="email"], input[type="password"], textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            background: #f8fafc;
            font-size: 13px;
            outline: none;
            transition: all 0.2s;
        }

        input:focus, textarea:focus { border-color: var(--accent-blue); background: #ffffff; }

        .btn-blue {
            background: var(--accent-blue);
            color: white;
            border: none;
            padding: 9px 20px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-blue:hover { background: #1d4ed8; transform: translateY(-1px); }
        .btn-sm { padding: 6px 12px; font-size: 12px; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #f8fafc; color: var(--text-muted); font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 12px 16px; text-align: left; border-bottom: 1px solid var(--border-color); }
        td { padding: 14px 16px; font-size: 13px; border-bottom: 1px solid var(--border-color); color: #334155; }
        tr:last-child td { border-bottom: none; }

        .badge { padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: 700; display: inline-block; }
        .badge-success { background: #dcfce7; color: #15803d; }
        .badge-warning { background: #fef3c7; color: #b45309; }

        a.table-link { color: #2563eb; text-decoration: none; font-weight: 600; }
        a.table-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

<!-- Top Header Navbar -->
<div class="top-navbar">
    <!-- Left Group: Logo + Left-Aligned Search Box -->
    <div class="header-left-group">
        <a href="agent_dashboard.php" class="brand-section">
            <div class="brand-logo-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="6" r="2.5" fill="#10b981"/>
                    <circle cx="6" cy="18" r="2.5" fill="#10b981"/>
                    <circle cx="18" cy="18" r="2.5" fill="#10b981"/>
                    <path d="M12 8.5v3.5M10.5 14L7.5 16.5M13.5 14l3 2.5"/>
                </svg>
            </div>
            <span>Venture<span class="logo-highlight">Guide</span></span>
        </a>

        <div class="search-bar-container">
            <input type="text" class="search-input" placeholder="Search Agent Panel...">
        </div>
    </div>

    <!-- Right Group -->
    <div class="header-right">
        <span>August-2026</span>
        <span>English</span>

        <!-- Notification Bell Icon Section -->
        <button class="notification-btn" onclick="triggerNotificationAlert(<?php echo $queries_count; ?>)" title="Check Notifications">
            <svg viewBox="0 0 24 24">
                <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/>
            </svg>
            <?php if ($queries_count > 0): ?>
                <span class="notification-red-dot"></span>
            <?php endif; ?>
        </button>
        
        <!-- Profile Section -->
        <button class="user-profile-btn" onclick="openProfileModal()" title="View Profile Details">
            <div class="avatar-circle">
                <?php echo strtoupper(substr($agent_name, 0, 1)); ?>
            </div>
            <div class="user-details">
                <span class="user-name"><?php echo htmlspecialchars($agent_name); ?></span>
                <span class="user-role">Agent / <?php echo htmlspecialchars($agent_status); ?> </span>
            </div>
        </button>
    </div>
</div>

<!-- PROFILE DETAILS MODAL -->
<div class="modal-overlay" id="profileModal" onclick="closeProfileModal(event)">
    <div class="profile-card-modal">
        <div class="profile-modal-header">
            <div class="profile-modal-avatar">
                <?php echo strtoupper(substr($agent_name, 0, 1)); ?>
            </div>
            <h3 style="font-size: 16px; font-weight: 700;"><?php echo htmlspecialchars($agent_name); ?></h3>
            <span style="font-size: 12px; opacity: 0.8;"><?php echo htmlspecialchars($agent_email); ?></span>
        </div>
        <div class="profile-modal-body">
            <div class="profile-info-row">
                <span class="profile-info-label">Agent ID:</span>
                <span class="profile-info-val">#<?php echo htmlspecialchars($agent_id); ?></span>
            </div>
            <div class="profile-info-row">
                <span class="profile-info-label">Account Status:</span>
                <span class="profile-info-val" style="color: #10b981;"><?php echo htmlspecialchars($agent_status); ?></span>
            </div>
            <div class="profile-info-row">
                <span class="profile-info-label">Active Ideas:</span>
                <span class="profile-info-val"><?php echo $ideas_count; ?></span>
            </div>
            <div class="profile-info-row">
                <span class="profile-info-label">Total Handled Queries:</span>
                <span class="profile-info-val"><?php echo $queries_count; ?></span>
            </div>
        </div>
        <button class="modal-close-btn" onclick="closeProfileModalDirect()">Close Profile Window</button>
    </div>
</div>

<!-- Main Layout Structure -->
<div class="dashboard-wrapper">

    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="nav-item <?php echo $active_tab==='ideas'?'active':''; ?>" onclick="switchNav('ideas', this)">Overview & Ideas</div>
        <div class="nav-item <?php echo $active_tab==='queries'?'active':''; ?>" onclick="switchNav('queries', this)">User Queries</div>
        <div class="nav-item <?php echo $active_tab==='users'?'active':''; ?>" onclick="switchNav('users', this)">User Accounts</div>
        <div class="nav-item <?php echo $active_tab==='templates'?'active':''; ?>" onclick="switchNav('templates', this)">Pitch Templates</div>
        <div class="nav-item <?php echo $active_tab==='feedback'?'active':''; ?>" onclick="switchNav('feedback', this)">User Feedback</div>
        <a href="logout.php" class="nav-item" style="color: #ef4444; margin-top: 20px;">Logout</a>
    </div>

    <!-- Main Content Area -->
    <div class="main-content">

        <div class="page-header">
            <div class="page-title" id="page-heading-title">Overview & Business Ideas</div>
            <div class="breadcrumb">Agent / <span id="breadcrumb-active">Overview & Business Ideas</span></div>
        </div>

        <?php if (!empty($msg)): ?>
            <div class="alert-success"><?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- CLICKABLE STAT CARDS -->
        <div class="stats-grid">
            <div class="stat-card pink" onclick="switchNav('users', document.querySelectorAll('.nav-item')[2])">
                <div class="stat-card-top">
                    <div>
                        <div class="title">Total Users</div>
                        <div class="number"><?php echo $users_count; ?></div>
                    </div>
                </div>
                <div class="stat-card-bottom">
                    <span>Registered</span>
                    <span class="val"><?php echo $users_count; ?> Accounts</span>
                </div>
            </div>

            <div class="stat-card yellow" onclick="switchNav('queries', document.querySelectorAll('.nav-item')[1])">
                <div class="stat-card-top">
                    <div>
                        <div class="title">Total Queries</div>
                        <div class="number"><?php echo $queries_count; ?></div>
                    </div>
                </div>
                <div class="stat-card-bottom">
                    <span>Pending/Answered</span>
                    <span class="val"><?php echo $queries_count; ?> Inquiries</span>
                </div>
            </div>

            <div class="stat-card green" onclick="switchNav('ideas', document.querySelectorAll('.nav-item')[0])">
                <div class="stat-card-top">
                    <div>
                        <div class="title">Business Ideas</div>
                        <div class="number"><?php echo $ideas_count; ?></div>
                    </div>
                </div>
                <div class="stat-card-bottom">
                    <span>Published</span>
                    <span class="val"><?php echo $ideas_count; ?> Ideas</span>
                </div>
            </div>

            <div class="stat-card cyan" onclick="switchNav('templates', document.querySelectorAll('.nav-item')[3])">
                <div class="stat-card-top">
                    <div>
                        <div class="title">Templates</div>
                        <div class="number"><?php echo $templates_count; ?></div>
                    </div>
                </div>
                <div class="stat-card-bottom">
                    <span>Resources</span>
                    <span class="val"><?php echo $templates_count; ?> Files</span>
                </div>
            </div>
        </div>

        <!-- PANEL 1: Business Ideas -->
        <div id="panel-ideas" class="panel <?php echo $active_tab==='ideas'?'active':''; ?>">
            <div class="card-box">
                <div class="card-box-header">Post & Manage Business Ideas</div>
                <div class="card-box-body">
                    <form action="agent_dashboard.php" method="POST">
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label>Idea Title</label>
                                <input type="text" name="idea_title" placeholder="e.g. How to Pitch to Investors" required>
                            </div>
                            <div class="form-group">
                                <label>Required Investment / Budget</label>
                                <input type="text" name="idea_budget" placeholder="e.g. $5,000 or PKR 500,000">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="idea_desc" rows="3" placeholder="Resource description..."></textarea>
                        </div>
                        <button type="submit" name="publish_idea" class="btn-blue">Publish Idea</button>
                    </form>

                    <h4 style="margin-top: 30px; margin-bottom: 12px; font-size: 14px;">Published Ideas List</h4>
                    <table>
                        <thead>
                            <tr>
                                <th>TITLE</th>
                                <th>INVESTMENT REQUIRED</th>
                                <th>DESCRIPTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($my_ideas && mysqli_num_rows($my_ideas) > 0): ?>
                                <?php while ($idea = mysqli_fetch_assoc($my_ideas)): ?>
                                    <tr>
                                        <td><b><?php echo htmlspecialchars($idea['title'] ?? ''); ?></b></td>
                                        <td><?php echo htmlspecialchars($idea['investment_required'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($idea['description'] ?? ''); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3">No business ideas published yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- PANEL 2: User Queries -->
        <div id="panel-queries" class="panel <?php echo $active_tab==='queries'?'active':''; ?>">
            <div class="card-box">
                <div class="card-box-header">User Inquiries & Questions</div>
                <div class="card-box-body">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 35%;">QUESTION</th>
                                <th style="width: 15%;">STATUS</th>
                                <th style="width: 50%;">RESPONSE / ANSWER</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($user_queries && mysqli_num_rows($user_queries) > 0): ?>
                                <?php while ($q = mysqli_fetch_assoc($user_queries)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($q['question'] ?? ''); ?></td>
                                        <td>
                                            <?php if (($q['status'] ?? '') === 'Answered'): ?>
                                                <span class="badge badge-success">Answered</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($q['answer'])): ?>
                                                <?php echo htmlspecialchars($q['answer']); ?>
                                            <?php else: ?>
                                                <form method="POST" action="agent_dashboard.php" style="display: flex; gap: 8px;">
                                                    <input type="hidden" name="query_id" value="<?php echo $q['query_id'] ?? $q['id']; ?>">
                                                    <input type="text" name="answer" placeholder="Type answer..." required>
                                                    <button type="submit" name="reply_query" class="btn-blue btn-sm">Reply</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3">No questions received yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- PANEL 3: User Accounts -->
        <div id="panel-users" class="panel <?php echo $active_tab==='users'?'active':''; ?>">
            <div class="card-box">
                <div class="card-box-header">Register & Verify User Account</div>
                <div class="card-box-body">
                    <form action="agent_dashboard.php" method="POST" class="form-grid-2">
                        <div class="form-group">
                            <label>User Full Name</label>
                            <input type="text" name="u_name" placeholder="Full name" required>
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="u_email" placeholder="user@example.com" required>
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="u_password" placeholder="Password" required>
                        </div>
                        <div style="grid-column: span 2;">
                            <button type="submit" name="verify_create_user" class="btn-blue">Create Account</button>
                        </div>
                    </form>

                    <h4 style="margin-top: 30px; margin-bottom: 12px; font-size: 14px;">Registered Users</h4>
                    <table>
                        <thead>
                            <tr>
                                <th>USER ID</th>
                                <th>NAME</th>
                                <th>EMAIL</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($registered_users && mysqli_num_rows($registered_users) > 0): ?>
                                <?php while ($usr = mysqli_fetch_assoc($registered_users)): ?>
                                    <tr>
                                        <td>#<?php echo $usr['user_id'] ?? $usr['id'] ?? 'N/A'; ?></td>
                                        <td><?php echo htmlspecialchars($usr['name'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($usr['email'] ?? ''); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3">No users found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- PANEL 4: Pitch Templates -->
        <div id="panel-templates" class="panel <?php echo $active_tab==='templates'?'active':''; ?>">
            <div class="card-box">
                <div class="card-box-header">Pitch Templates & Resources</div>
                <div class="card-box-body">
                    <form action="agent_dashboard.php" method="POST" class="form-grid-2">
                        <div class="form-group">
                            <label>Resource Title</label>
                            <input type="text" name="tmpl_title" placeholder="e.g. Startup Pitch Deck" required>
                        </div>
                        <div class="form-group">
                            <label>Resource URL / Link</label>
                            <input type="text" name="tmpl_path" placeholder="https://..." required>
                        </div>
                        <div style="grid-column: span 2;">
                            <button type="submit" name="upload_template" class="btn-blue">Add Resource</button>
                        </div>
                    </form>

                    <h4 style="margin-top: 30px; margin-bottom: 12px; font-size: 14px;">Uploaded Resources</h4>
                    <table>
                        <thead>
                            <tr>
                                <th>TITLE</th>
                                <th>RESOURCE LINK</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($my_templates && mysqli_num_rows($my_templates) > 0): ?>
                                <?php while ($tmpl = mysqli_fetch_assoc($my_templates)): ?>
                                    <tr>
                                        <td><b><?php echo htmlspecialchars($tmpl['title'] ?? ''); ?></b></td>
                                        <td><a href="<?php echo htmlspecialchars($tmpl['file_path'] ?? '#'); ?>" class="table-link" target="_blank">Open Link</a></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="2">No templates uploaded.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- PANEL 5: Feedback -->
        <div id="panel-feedback" class="panel <?php echo $active_tab==='feedback'?'active':''; ?>">
            <div class="card-box">
                <div class="card-box-header">User Feedback Received</div>
                <div class="card-box-body">
                    <table>
                        <thead>
                            <tr>
                                <th>FEEDBACK MESSAGE</th>
                                <th>DATE</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($feedback_list && mysqli_num_rows($feedback_list) > 0): ?>
                                <?php while ($fb = mysqli_fetch_assoc($feedback_list)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($fb['message'] ?? $fb['feedback_text'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($fb['submitted_at'] ?? $fb['created_at'] ?? 'N/A'); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="2">No feedback available.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    function switchNav(panelKey, element) {
        var panels = document.querySelectorAll('.panel');
        panels.forEach(function(p) { p.classList.remove('active'); });

        var navItems = document.querySelectorAll('.nav-item');
        navItems.forEach(function(item) { item.classList.remove('active'); });

        var targetPanel = document.getElementById('panel-' + panelKey);
        if (targetPanel) {
            targetPanel.classList.add('active');
        }
        
        if (element) {
            element.classList.add('active');
        }

        var titles = {
            'ideas': 'Overview & Business Ideas',
            'queries': 'User Questions & Queries',
            'users': 'User Account Management',
            'templates': 'Pitch Templates & Resources',
            'feedback': 'User Feedback'
        };

        document.getElementById('page-heading-title').innerText = titles[panelKey] || 'Dashboard';
        document.getElementById('breadcrumb-active').innerText = titles[panelKey] || 'Dashboard';
    }

    function triggerNotificationAlert(count) {
        if (count > 0) {
            alert("Notification Alert: You have " + count + " user queries pending for review!");
            switchNav('queries', document.querySelectorAll('.nav-item')[1]);
        } else {
            alert("No new notifications or queries at the moment.");
        }
    }

    function openProfileModal() {
        document.getElementById('profileModal').classList.add('active');
    }

    function closeProfileModalDirect() {
        document.getElementById('profileModal').classList.remove('active');
    }

    function closeProfileModal(e) {
        if (e.target.id === 'profileModal') {
            document.getElementById('profileModal').classList.remove('active');
        }
    }
</script>

</body>
</html>