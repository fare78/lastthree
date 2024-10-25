<?php
session_start();
include 'db.php'; // Include your database connection file

// Redirect to login if not logged in or not admin
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit();
}

// Handle adding an order
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userId = $_POST['user_id'];
    $status = 'new';

    // Handle file upload
    if (isset($_FILES['file_path']) && $_FILES['file_path']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = 'FileUploads/'; // Specify your upload directory
        $uploadFile = $uploadDir . basename($_FILES['file_path']['name']);

        // Move the uploaded file to the specified directory
        if (move_uploaded_file($_FILES['file_path']['tmp_name'], $uploadFile)) {
            // Insert order into database
            $stmt = $conn->prepare("INSERT INTO orders (user_id, file_path, status, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("iss", $userId, $uploadFile, $status);
            $stmt->execute();

            // Fetch the newly added order ID
            $newOrderId = $stmt->insert_id;

            // Fetch user name and phone number for the newly added order
            $userQuery = $conn->prepare("SELECT name, phone FROM users WHERE id = ?");
            $userQuery->bind_param("i", $userId);
            $userQuery->execute();
            $userResult = $userQuery->get_result();
            $user = $userResult->fetch_assoc();

            // Return the new order as a row in the table
            echo "<tr>
                    <td>$newOrderId</td>
                    <td>" . htmlspecialchars($user['name']) . "</td>
                    <td>" . htmlspecialchars($user['phone']) . "</td> <!-- Added phone number -->
                    <td>" . htmlspecialchars($uploadFile) . "</td>
                    <td>" . htmlspecialchars($status) . "</td>
                    <td>" . date('Y-m-d H:i:s') . "</td>
                    <td><a href='order_details.php?order_id=$newOrderId' class='button'>Details</a></td>
                </tr>";
        } else {
            echo "Error uploading file.";
        }
    } else {
        echo "File upload error.";
    }
}
?>
