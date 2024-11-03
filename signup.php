<?php
session_start();
include 'db.php'; // Include your database connection file
$error = '';
$json = file_get_contents(__DIR__ . '/config/client_secret.json'); // Update with the path to your JSON file
$data = json_decode($json, true);
$redirect_url = $data['web']['redirect_uris'];
// Extract the client_id
$client_id = $data['web']['client_id'];
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['signup'])) {
        // Sign-up logic
        $name = $_POST['username'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $phone = $_POST['phone'];
        $address = $_POST['address'];

        // Check if email, phone, or name already exists
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR phone = ? OR name = ?");
        $checkStmt->bind_param("sss", $email, $phone, $name);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            // Email, phone, or name already exists
            $error = "This email, phone number, or username is already registered. Please login.";
        } else {
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, address) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $email, $password, $phone, $address);

            if ($stmt->execute()) {
                $_SESSION['user_id'] = $conn->insert_id;
                header("Location: upload.php");
                exit();
            } else {
                $error = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
        $checkStmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f7f7f7;
            margin: 0;
        }

        .auth-container {
            width: 300px;
            padding: 30px;
            background-color: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
        }

        .auth-container h2 {
            text-align: center;
            margin-bottom: 20px;
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

        .toggle {
            text-align: center;
            margin-top: 10px;
            color: #4285f4;
            cursor: pointer;
        }

        .error {
            color: red;
            text-align: center;
        }
    </style>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    
</head>

<body>

    <div class="auth-container">
        <h2>Sign Up</h2>
        <form method="POST" action="">
            <input type="text" name="username" placeholder="Username" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="text" name="phone" placeholder="Phone Number" required>
            <input type="text" name="address" placeholder="Address" required>
            <button type="submit" name="signup">Sign Up</button>
            <div class="toggle">Already have an account? <a href="login.php" style="color: #4285f4;">Login</a></div>
            <?php if ($error): ?>
                <p class='error'><?= $error ?></p>
            <?php endif; ?>
        </form>
        <div id="g_id_onload" data-client_id="<?php echo $client_id; ?>"
            data-login_uri="<?php echo $redirect_url; ?>" data-callback="handleCredentialResponse"
            data-auto_prompt="false">
        </div>
        <p>Sign Up with Google</p>
        <div class="g_id_signin" data-type="standard" data-size="large" data-theme="outline" data-text="sign_in_with"
            data-shape="rectangular" data-logo_alignment="left">
        </div>
        
    </div>
    
   

<script>
    // Handle Google Sign-In response
    function handleCredentialResponse(response) {
        
        
        // Send ID token to your server
        fetch("google_login.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `id_token=${response.credential}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                // Redirect based on the server response
                window.location.href = data.redirect;
            } else {
                console.error("Error:", data.error);
                alert("Google Sign-In failed. Please try again.");
            }
        });
    }

    // Initialize the button
    window.onload = function () {
        google.accounts.id.initialize({
            client_id: "<?php echo $client_id; ?>",
            callback: handleCredentialResponse
        });
        google.accounts.id.renderButton(
            document.querySelector(".g_id_signin"),
            { theme: "outline", size: "large" } // Customize button style here
        );
    };
</script>


</body>

</html>