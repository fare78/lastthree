<?php
session_start();
include 'db.php'; // Include your database connection file
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$error = '';

$json = file_get_contents(__DIR__ . '/config/client_secret.json'); // Update with the path to your JSON file
$data = json_decode($json, true);
$redirect_url = $data['web']['redirect_uris'];
// Extract the client_id
$client_id = $data['web']['client_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['login'])) {
        // Login logic
        $email = $_POST['email'];
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT id, password, is_admin FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $hashed_password, $is_admin);
            $stmt->fetch();

            if (password_verify($password, $hashed_password)) {
                $_SESSION['user_id'] = $id;
                $_SESSION['is_admin'] = $is_admin;

                if ($is_admin) {
                    header("Location: admin.php");
                } else {
                    header("Location: upload.php");
                }
                exit();
            } else {
                $error = "Invalid password!";
            }
        } else {
            $error = "No user found with this email.";
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
    <title>Login</title>
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
        <h2>Login</h2>
        <form method="POST" action="">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="login">Login</button>
            <div class="toggle">Don't have an account? <a href="signup.php" style="color: #4285f4;">Sign up</a></div>
            <?php if ($error): ?>
                <p class='error'><?= $error ?></p>
            <?php endif; ?>
        </form>

        <!-- Google Sign-In Button -->
        <p>Sign in with Google</p>
        <div id="g_id_onload" data-client_id="<?php echo $client_id; ?>"
            data-login_uri="<?php echo $redirect_url; ?>" data-callback="handleCredentialResponse"
            data-auto_prompt="false">
        </div>
        <div class="g_id_signin" data-type="standard" data-size="large" data-theme="outline" data-text="sign_in_with"
            data-shape="rectangular" data-logo_alignment="left">
        </div>
    </div>

    <script>
        function handleCredentialResponse(response) {
            var id_token = response.credential;
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'google_login.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function () {
                try {
                    const res = JSON.parse(xhr.responseText);
                    if (res.status === "success") {
                        window.location.href = res.redirect;
                    } else {
                        console.error("Error: ", res.error);
                        alert(res.error);
                    }
                } catch (e) {
                    console.error("Failed to parse response", e);
                }
            };
            xhr.send('id_token=' + id_token);
        }
    </script>
</body>

</html>