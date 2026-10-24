<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include('db.php');

$msg_success = "";
$msg_error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $name          = mysqli_real_escape_string($conn, trim($_POST['name'] ?? ''));
    $email         = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
    $password      = mysqli_real_escape_string($conn, trim($_POST['password'] ?? ''));
    $role          = mysqli_real_escape_string($conn, trim($_POST['role'] ?? 'user'));
    
    // Agent Fields (Database column names ke mutabiq)
    $mobile        = mysqli_real_escape_string($conn, trim($_POST['phone'] ?? ''));
    $qualification = mysqli_real_escape_string($conn, trim($_POST['education'] ?? ''));
    $experience    = mysqli_real_escape_string($conn, trim($_POST['experience'] ?? ''));
    $business_name = mysqli_real_escape_string($conn, trim($_POST['business_name'] ?? ''));
    $business_link = mysqli_real_escape_string($conn, trim($_POST['business_link'] ?? ''));
    
    // Document / Template File Upload
    $template_file = "";
    if ($role === 'agent' && isset($_FILES['template']) && $_FILES['template']['error'] === 0) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_name = time() . "_" . basename($_FILES["template"]["name"]);
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($_FILES["template"]["tmp_name"], $target_file)) {
            $template_file = mysqli_real_escape_string($conn, $target_file);
        }
    }

    if (!empty($name) && !empty($email) && !empty($password)) {
        
        if ($role === 'agent') {
            // Check if agent email already exists
            $check_email = mysqli_query($conn, "SELECT * FROM `agent` WHERE `email` = '$email'");
            
            if ($check_email && mysqli_num_rows($check_email) > 0) {
                $msg_error = "This email is already registered as Agent!";
            } else {
                // Insert Agent with default status 'Pending'
                $sql = "INSERT INTO `agent` (`name`, `email`, `password`, `mobile`, `qualification`, `experience`, `business_name`, `business_link`, `template`, `status`) 
                        VALUES ('$name', '$email', '$password', '$mobile', '$qualification', '$experience', '$business_name', '$business_link', '$template_file', 'Pending')";
                
                if (mysqli_query($conn, $sql)) {
                    $msg_success = "Registration successful! Your account is Pending Admin approval.";
                } else {
                    $msg_error = "Database Error: " . mysqli_error($conn);
                }
            }
        } else {
            // User Registration
            $check_email = mysqli_query($conn, "SELECT * FROM `end_user` WHERE `email` = '$email'");
            
            if ($check_email && mysqli_num_rows($check_email) > 0) {
                $msg_error = "This email is already registered as User!";
            } else {
                $sql = "INSERT INTO `end_user` (`name`, `email`, `password`) VALUES ('$name', '$email', '$password')";
                
                if (mysqli_query($conn, $sql)) {
                    $msg_success = "User Registration successful! You can now login.";
                } else {
                    $msg_error = "Database Error: " . mysqli_error($conn);
                }
            }
        }
    } else {
        $msg_error = "Please fill in all required fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 30px 0; }
        .card { background: white; padding: 30px; border-radius: 12px; width: 400px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); text-align: center; }
        .inp, .select-inp { width: 100%; padding: 10px 12px; margin: 8px 0; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; background: white; font-size: 14px; outline: none; }
        .file-label { display: block; text-align: left; font-size: 12px; font-weight: bold; color: #475569; margin-top: 8px; }
        .btn { width: 100%; background: #2563eb; color: white; padding: 11px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 15px; margin-top: 12px; }
        .btn:hover { background: #1d4ed8; }
        .alert-success { background: #dcfce7; color: #16a34a; padding: 10px; border-radius: 6px; font-size: 13px; margin-bottom: 12px; }
        .alert-error { background: #fee2e2; color: #dc2626; padding: 10px; border-radius: 6px; font-size: 13px; margin-bottom: 12px; }
        #agent_fields { display: none; text-align: left; }
        .section-title { font-size: 12px; font-weight: bold; color: #64748b; margin-top: 10px; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px; }
    </style>
</head>
<body>

<div class="card">
    <h2 style="margin-top: 0;">Create Account 🚀</h2>
    
    <?php if(!empty($msg_success)): ?><div class="alert-success"><?php echo $msg_success; ?></div><?php endif; ?>
    <?php if(!empty($msg_error)): ?><div class="alert-error"><?php echo $msg_error; ?></div><?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data">
        <select name="role" id="role_select" class="select-inp" onchange="toggleAgentFields()" required>
            <option value="user">Register as User</option>
            <option value="agent">Register as Agent</option>
        </select>

        <input type="text" name="name" class="inp" placeholder="Full Name *" required>
        <input type="email" name="email" class="inp" placeholder="Email Address *" required>
        <input type="password" name="password" class="inp" placeholder="Password *" required>
        
        <!-- Agent Extra Fields -->
        <div id="agent_fields">
            <hr style="margin: 15px 0; border: 0; border-top: 1px dashed #cbd5e1;">
            <div class="section-title">Agent Details</div>
            
            <input type="text" name="phone" class="inp" placeholder="Mobile Number">
            <input type="text" name="education" class="inp" placeholder="Qualification / Education">
            <input type="text" name="experience" class="inp" placeholder="Experience">
            <input type="text" name="business_name" class="inp" placeholder="Business Name">
            <input type="url" name="business_link" class="inp" placeholder="Business Link (e.g. https://...)">
            
            <label class="file-label">Upload Document / Template (PDF, DOC, Image):</label>
            <input type="file" name="template" class="inp" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg">
        </div>

        <button type="submit" name="register" class="btn">Sign Up</button>
    </form>

    <p style="font-size: 13px; margin-top: 18px; color: #64748b;">
        Already have an account? <a href="login.php" style="color: #2563eb; text-decoration: none; font-weight: 600;">Login Here</a>
    </p>
</div>

<script>
function toggleAgentFields() {
    var role = document.getElementById("role_select").value;
    var agentFields = document.getElementById("agent_fields");
    if (role === "agent") {
        agentFields.style.display = "block";
    } else {
        agentFields.style.display = "none";
    }
}
</script>

</body>
</html>