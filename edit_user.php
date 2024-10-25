<?php
session_start();
include 'db.php'; // Include your database connection

// Check if the admin is logged in
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] == 0) {
    header("Location: login.php");
    exit();
}

$user_id = $_GET['id'];
$error = '';
$success = '';

// Fetch user details
$stmt = $conn->prepare("SELECT name, email, phone, is_admin FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($name, $email, $phone, $is_admin);
$stmt->fetch();
$stmt->close();

// Update user details
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_name = $_POST['name'];
    $new_email = $_POST['email'];
    $new_phone = $_POST['phone'];
    
    $new_is_admin = isset($_POST['is_admin']) ? 1 : 0;
    $new_password = $_POST['password'];

    // Update query without password first
    $query = "UPDATE users SET name = ?, email = ?, phone = ?, is_admin = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssii", $new_name, $new_email, $new_phone, $new_is_admin, $user_id);
    
    if ($stmt->execute()) {
        $success = "User details updated successfully.";

    } else {
        $error = "Failed to update user details.";
    }
    $stmt->close();

    // If a new password is provided, update the password
    if (!empty($new_password)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $password_query = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $conn->prepare($password_query);
        $stmt->bind_param("si", $hashed_password, $user_id);
        
        if ($stmt->execute()) {
            $success .= " Password updated successfully.";
        } else {
            $error = "Failed to update password.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <style>
        .form-container {
            width: 300px;
            margin: auto;
            padding: 30px;
            background-color: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
        }
        input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #4285f4;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>
<body>

    <div class="form-container">
        <h2>Edit User</h2>
        <?php if ($error): ?>
            <p style="color: red;"><?= $error ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p style="color: green;"><?= $success ?></p>
        <?php endif; ?>
        <form method="POST">
            <input type="text" name="name" value="<?= $name ?>" placeholder="Name" required>
            <input type="email" name="email" value="<?= $email ?>" placeholder="Email" required>
            <input type="text" name="phone" value="<?= $phone ?>" placeholder="Phone" required>
            
            <input type="password" name="password" placeholder="New Password (leave blank if unchanged)">
            <label>
                
                <input type="checkbox" name="is_admin" <?= $is_admin ? 'checked' : '' ?>> Is Admin
                <br><br>
            </label>
            <button type="submit">Update User</button>
        
        </form>
    <div class="back-button">
        <a href="manage_users.php" class="back-button">Back to users</a>
    </div>
    </div>

</body>
</html>
