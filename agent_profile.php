<?php
// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include('db.php');

// Disable MySQLi throwing automatic Fatal Exceptions so try-catch works smoothly
mysqli_report(MYSQLI_REPORT_OFF);

$agent_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($agent_id <= 0) {
    echo "<h2 style='text-align:center; margin-top:50px; font-family:sans-serif;'>Invalid Agent ID!</h2>";
    exit();
}

// 1. Detect actual Primary/ID column name from 'agent' table automatically
$id_column = 'id'; // default fallback
$columns_res = mysqli_query($conn, "SHOW COLUMNS FROM `agent`");
if ($columns_res) {
    while ($col = mysqli_fetch_assoc($columns_res)) {
        $field = strtolower($col['Field']);
        if ($col['Key'] === 'PRI' || $field === 'agent_id' || $field === 'id' || $field === 'user_id') {
            $id_column = $col['Field'];
            break;
        }
    }
}

// 2. Fetch Agent Details using detected column name
$agent_query = mysqli_query($conn, "SELECT * FROM `agent` WHERE `$id_column` = '$agent_id'");
$agent = ($agent_query && mysqli_num_rows($agent_query) > 0) ? mysqli_fetch_assoc($agent_query) : null;

if (!$agent) {
    echo "<h2 style='text-align:center; margin-top:50px; font-family:sans-serif;'>Agent Profile Not Found!</h2>";
    exit();
}

// 3. Handle Question / Query Form Submission
$msg_success = "";
$msg_error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_query'])) {
    $sender_name = mysqli_real_escape_string($conn, trim($_POST['sender_name'] ?? ''));
    $sender_email = mysqli_real_escape_string($conn, trim($_POST['sender_email'] ?? ''));
    $query_text = mysqli_real_escape_string($conn, trim($_POST['query_text'] ?? ''));

    if (!empty($sender_name) && !empty($sender_email) && !empty($query_text)) {
        $ins = mysqli_query($conn, "INSERT INTO `query` (`agent_id`, `sender_name`, `sender_email`, `query_text`) VALUES ('$agent_id', '$sender_name', '$sender_email', '$query_text')");
        if ($ins) {
            $msg_success = "Your message/query has been sent successfully!";
        } else {
            $msg_error = "Failed to send query: " . mysqli_error($conn);
        }
    } else {
        $msg_error = "Please fill in all query fields.";
    }
}

// 4. Fetch Business Ideas
$ideas_query = mysqli_query($conn, "SELECT * FROM `business_idea` WHERE `agent_id` = '$agent_id'");
if (!$ideas_query) {
    $ideas_query = mysqli_query($conn, "SELECT * FROM `business_idea` WHERE `$id_column` = '$agent_id'");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Profile - <?php echo htmlspecialchars($agent['name'] ?? $agent['username'] ?? 'Agent'); ?></title>
    <style>
        body { font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #f8fafc; margin: 0; padding: 30px 20px; color: #0f172a; }
        .container { max-width: 850px; margin: 0 auto; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #2563eb; text-decoration: none; font-weight: 600; font-size: 14px; }
        .card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 25px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .profile-header { display: flex; align-items: center; gap: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 20px; margin-bottom: 20px; }
        .avatar { width: 75px; height: 75px; border-radius: 50%; background: #2563eb; color: white; display: flex; align-items: center; justify-content: center; font-size: 30px; font-weight: bold; }
        .details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; }
        .detail-item strong { display: block; font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 3px; }
        .detail-item span { font-size: 15px; font-weight: 500; }
        .bio-box { background: #f1f5f9; padding: 15px; border-radius: 8px; font-size: 14px; line-height: 1.6; margin-top: 15px; border-left: 4px solid #2563eb; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-size: 13px; font-weight: 600; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; font-size: 14px; }
        .btn-submit { background: #2563eb; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; }
        .alert-success { background: #dcfce7; color: #16a34a; padding: 12px; border-radius: 6px; margin-bottom: 15px; font-weight: 500; }
        .alert-error { background: #fee2e2; color: #dc2626; padding: 12px; border-radius: 6px; margin-bottom: 15px; font-weight: 500; }
    </style>
</head>
<body>

<div class="container">
    <a href="javascript:history.back()" class="back-link">← Back to Dashboard</a>

    <!-- PROFILE DETAILS -->
    <div class="card">
        <div class="profile-header">
            <div class="avatar">
                <?php 
                    $disp_name = $agent['name'] ?? $agent['username'] ?? 'A';
                    echo strtoupper(substr($disp_name, 0, 1)); 
                ?>
            </div>
            <div>
                <h2 style="margin: 0 0 5px 0;"><?php echo htmlspecialchars($disp_name); ?></h2>
                <span style="color: #16a34a; font-weight: 600; font-size: 13px; background: #dcfce7; padding: 3px 10px; border-radius: 12px;"><?php echo htmlspecialchars($agent['status'] ?? 'Pending'); ?> Agent</span>
            </div>
        </div>

        <div class="details-grid">
            <div class="detail-item"><strong>Email Address</strong><span><?php echo htmlspecialchars($agent['email'] ?? 'Not Provided'); ?></span></div>
            <div class="detail-item"><strong>Mobile Number</strong><span><?php echo htmlspecialchars($agent['mobile'] ?? 'Not Provided'); ?></span></div>
            <div class="detail-item"><strong>CNIC Number</strong><span><?php echo htmlspecialchars($agent['cnic'] ?? 'Not Provided'); ?></span></div>
            <div class="detail-item"><strong>Country</strong><span><?php echo htmlspecialchars($agent['country'] ?? 'Not Provided'); ?></span></div>
            <div class="detail-item"><strong>Qualification</strong><span><?php echo htmlspecialchars($agent['qualification'] ?? 'Not Provided'); ?></span></div>
            <div class="detail-item"><strong>Experience</strong><span><?php echo htmlspecialchars($agent['experience'] ?? 'Not Provided'); ?></span></div>
            <div class="detail-item"><strong>Business Name</strong><span><?php echo htmlspecialchars($agent['business_name'] ?? 'Not Provided'); ?></span></div>
            <div class="detail-item">
                <strong>Business Link</strong>
                <span>
                    <?php if(!empty($agent['business_link'])): ?>
                        <a href="<?php echo htmlspecialchars($agent['business_link']); ?>" target="_blank" style="color:#2563eb;"><?php echo htmlspecialchars($agent['business_link']); ?></a>
                    <?php else: ?>
                        Not Provided
                    <?php endif; ?>
                </span>
            </div>
        </div>

        <div class="bio-box">
            <strong style="color: #475569;">About / Bio:</strong>
            <p style="margin: 5px 0 0 0; color: #334155;"><?php echo nl2br(htmlspecialchars($agent['bio'] ?? 'No bio provided.')); ?></p>
        </div>
    </div>

    <!-- BUSINESS IDEAS -->
    <div class="card">
        <h3 style="margin-top:0;">Published Business Ideas 💡</h3>
        <?php if ($ideas_query && mysqli_num_rows($ideas_query) > 0): ?>
            <?php while ($idea = mysqli_fetch_assoc($ideas_query)): ?>
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px; border-radius: 8px; margin-bottom: 10px;">
                    <h4 style="margin: 0 0 5px 0; color: #1e293b;"><?php echo htmlspecialchars($idea['title']); ?></h4>
                    <p style="margin: 0; font-size: 13px; color: #64748b;"><?php echo htmlspecialchars($idea['description']); ?></p>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="color: #64748b; margin: 0; font-size: 14px;">No published ideas found for this agent.</p>
        <?php endif; ?>
    </div>

    <!-- QUERY FORM -->
    <div class="card">
        <h3 style="margin-top:0;">Send Message / Ask Question 💬</h3>
        <?php if (!empty($msg_success)): ?><div class="alert-success"><?php echo $msg_success; ?></div><?php endif; ?>
        <?php if (!empty($msg_error)): ?><div class="alert-error"><?php echo $msg_error; ?></div><?php endif; ?>

        <form action="" method="POST">
            <div class="details-grid">
                <div class="form-group">
                    <label>Your Name *</label>
                    <input type="text" name="sender_name" required placeholder="Enter your name">
                </div>
                <div class="form-group">
                    <label>Your Email *</label>
                    <input type="email" name="sender_email" required placeholder="Enter your email">
                </div>
            </div>
            <div class="form-group">
                <label>Message / Question *</label>
                <textarea name="query_text" rows="3" required placeholder="Type your message here..."></textarea>
            </div>
            <button type="submit" name="send_query" class="btn-submit">Send Message</button>
        </form>
    </div>
</div>

</body>
</html>