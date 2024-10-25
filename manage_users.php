<?php
session_start();
include 'db.php'; // Include your database connection

// Check if the admin is logged in
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] == 0) {
    header("Location: login.php");
    exit();
}

// Fetch all users from the database
$sql = "SELECT id, name, email, phone, is_admin FROM users";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .button {
            background-color: #007BFF;
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            margin-top: 20px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
        }
        .back-button {
            margin-bottom: 20px;
            /* Space between button and form */
            display: block;
            /* Center the button */
            text-align: center;
        }
    </style>
</head>
<body>
    <h2>Manage Users</h2>
    <div class="back-button">
        <a href="admin.php" class="button">Back to Admin Panel</a>
    </div>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                
                <th>Admin</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= $row['name'] ?></td>
                        <td><?= $row['email'] ?></td>
                        <td><?= $row['phone'] ?></td>
                        
                        <td><?= $row['is_admin'] ? 'Yes' : 'No' ?></td>
                        <td>
                            <button onclick="window.location.href='edit_user.php?id=<?= $row['id'] ?>'">Edit</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">No users found</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
