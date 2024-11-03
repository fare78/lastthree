<?php
session_start();
include 'db.php'; // Include your database connection file
$recaptcha_secret = '6LfD5XMqAAAAABArMd7JbVginVIiP0_ieMqTLPkT';
require 'vendor/autoload.php'; // Ensure you have the Google API Client Library
$json = file_get_contents(__DIR__ . '/config/client_secret.json'); // Update with the path to your JSON file
$data = json_decode($json, true);
$redirect_url = $data['web']['redirect_uris'];
// Verify reCAPTCHA response





$client = new Google_Client();
$client->setAuthConfig(__DIR__ . '/config/client_secret.json'); // Using __DIR__ for the current directory
$client->setRedirectUri($redirect_url); // Your redirect URI
$client->addScope("email");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the ID token sent from the login page
    $idToken = $_POST['id_token'];
    $ticket = $client->verifyIdToken($idToken);

    if ($ticket) {
        $payload = $ticket;
        $email = $payload['email'];
        $name = $payload['name'];

        // Check if the user already exists in your database
        $stmt = $conn->prepare("SELECT id, is_admin, is_info_complete FROM users WHERE email = ?");
        if (!$stmt) {
            echo json_encode(["error" => "Database error: " . $conn->error]);
            exit();
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // User exists, log them in
            $stmt->bind_result($id, $is_admin, $is_info_complete);
            $stmt->fetch();
            $_SESSION['user_id'] = $id;
            $_SESSION['is_admin'] = $is_admin;

            // Check if user has completed their information
            if ($is_info_complete) {
                // Redirect to upload.php if information is complete
                echo json_encode(["status" => "success", "redirect" => "upload.php"]);
            } else {
                // Redirect to complete_info.php if information is incomplete
                echo json_encode(["status" => "success", "redirect" => "complete_info.php"]);
            }
        } else {
            // User does not exist, create a new account
            $stmt = $conn->prepare("INSERT INTO users (email, name, password, is_info_complete) VALUES (?, ?, ?, 0)");
            if (!$stmt) {
                echo json_encode(["error" => "Database error: " . $conn->error]);
                exit();
            }
            $hashed_password = password_hash("google_login", PASSWORD_DEFAULT); // Dummy password
            $stmt->bind_param("sss", $email, $name, $hashed_password);
            $stmt->execute();

            $_SESSION['user_id'] = $conn->insert_id; // Get the newly created user ID
            $_SESSION['is_admin'] = false; // Set default value for new users
            
            // Redirect new user to complete_info.php
            echo json_encode(["status" => "success", "redirect" => "complete_info.php"]);
        }
    } else {
        // Invalid ID token
        echo json_encode(["error" => "Invalid ID token."]);
    }
} else {
    echo json_encode(["error" => "Invalid request method."]);
}
?>

