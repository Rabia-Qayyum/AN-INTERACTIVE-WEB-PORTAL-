<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include('db.php');

$msg_error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email    = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
    $password = mysqli_real_escape_string($conn, trim($_POST['password'] ?? ''));
    $role     = mysqli_real_escape_string($conn, trim($_POST['role'] ?? 'user'));

    if (!empty($email) && !empty($password)) {
        
        if ($role === 'admin') {
            // Check Admin Table
            $query = "SELECT * FROM `admin` WHERE `email` = '$email' AND `password` = '$password'";
            $result = mysqli_query($conn, $query);

            if ($result && mysqli_num_rows($result) > 0) {
                $admin = mysqli_fetch_assoc($result);

                $_SESSION['admin_id']   = $admin['admin_id'] ?? $admin['id'];
                $_SESSION['admin_name'] = $admin['name'] ?? $admin['username'] ?? 'Admin';
                $_SESSION['role']       = 'admin';

                header("Location: admin_dashboard.php");
                exit();
            } else {
                $msg_error = "Invalid Email or Password for Admin!";
            }

        } elseif ($role === 'agent') {
            // Check Agent Table
            $query = "SELECT * FROM `agent` WHERE `email` = '$email' AND `password` = '$password'";
            $result = mysqli_query($conn, $query);

            if ($result && mysqli_num_rows($result) > 0) {
                $agent = mysqli_fetch_assoc($result);

                $status = strtolower($agent['status'] ?? 'approved');

                if ($status === 'pending') {
                    $msg_error = "Your account is currently PENDING approval from the Admin.";
                } elseif ($status === 'rejected') {
                    $msg_error = "Your account registration has been REJECTED by Admin.";
                } else {
                    // Login Allowed for Agent
                    $_SESSION['agent_id']   = $agent['agent_id'] ?? $agent['id'];
                    $_SESSION['agent_name'] = $agent['name'] ?? $agent['username'] ?? 'Agent';
                    $_SESSION['role']       = 'agent';
                    
                    header("Location: agent_dashboard.php");
                    exit();
                }

            } else {
                $msg_error = "Invalid Email or Password for Agent!";
            }

        } else {
            // Check End User Table
            $query = "SELECT * FROM `end_user` WHERE `email` = '$email' AND `password` = '$password'";
            $result = mysqli_query($conn, $query);

            if ($result && mysqli_num_rows($result) > 0) {
                $user = mysqli_fetch_assoc($result);

                $_SESSION['user_id']     = $user['user_id'] ?? $user['id'];
                $_SESSION['end_user_id'] = $user['user_id'] ?? $user['id'];
                $_SESSION['user_name']   = $user['name'] ?? 'User';
                $_SESSION['role']        = 'user';

                header("Location: user_dashboard.php"); 
                exit();
            } else {
                $msg_error = "Invalid Email or Password!";
            }
        }

    } else {
        $msg_error = "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #0b0f19; color: #fff; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .card { background: #111827; padding: 35px; border-radius: 12px; width: 380px; box-shadow: 0 4px 20px rgba(0,0,0,0.5); text-align: center; border: 1px solid #374151; }
        .inp, .select-inp { width: 100%; padding: 11px 14px; margin: 8px 0; border: 1px solid #374151; border-radius: 6px; box-sizing: border-box; background: #1f2937; color: #fff; font-size: 14px; outline: none; }
        .inp:focus, .select-inp:focus { border-color: #3b82f6; }
        .btn { width: 100%; background: #3b82f6; color: white; padding: 11px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 15px; margin-top: 14px; transition: background 0.2s; }
        .btn:hover { background: #2563eb; }
        .alert-error { background: rgba(239, 68, 68, 0.15); color: #f87171; padding: 10px; border-radius: 6px; font-size: 13px; margin-bottom: 14px; text-align: left; border: 1px solid rgba(239, 68, 68, 0.3); }
    </style>
</head>
<body>

<div class="card">
    <h2 style="margin-top: 0; font-size: 22px;">Welcome Back</h2>
    
    <?php if(!empty($msg_error)): ?>
        <div class="alert-error"><?php echo htmlspecialchars($msg_error); ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <select name="role" class="select-inp" required>
            <option value="user">Login as User</option>
            <option value="agent">Login as Agent</option>
            <option value="admin">Login as Admin</option>
        </select>

        <input type="email" name="email" class="inp" placeholder="Email Address" required>
        <input type="password" name="password" class="inp" placeholder="Password" required>

        <button type="submit" name="login" class="btn">Login</button>
    </form>

    <p style="font-size: 13px; margin-top: 20px; color: #9ca3af;">
        Don't have an account? <a href="signup.php" style="color: #60a5fa; text-decoration: none; font-weight: 600;">Sign Up Here</a>
    </p>
</div>

</body>
</html>