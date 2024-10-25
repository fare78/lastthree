<?php
include 'db.php'; // Include your database connection file

// Check if the phone number is provided in the GET request
$phone = isset($_GET['phone']) ? trim($_GET['phone']) : '';

$results = [];

if ($phone !== '') {
    // Prepare a statement to search for users by phone number
    $stmt = $conn->prepare("SELECT id, name, email, phone, address, is_admin FROM users WHERE phone LIKE ?");
    $searchTerm = '%' . $phone . '%';
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    $results = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search User</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background-color: #007BFF;
            color: white;
        }
    </style>
</head>
<body>

<h1>Search Results for Phone: <?= htmlspecialchars($phone) ?></h1>

<?php if (!empty($results)): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Is Admin</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['id']) ?></td>
                            <td><?= htmlspecialchars($user['name']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['phone']) ?></td>
                            <td><?= htmlspecialchars($user['address']) ?></td>
                            <td><?= $user['is_admin'] ? 'Yes' : 'No' ?></td>
                            <td>
                                <a href="edit_user.php?id=<?= $user['id'] ?>">Edit</a>
                                <a href="delete_user.php?id=<?= $user['id'] ?>" onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                            </td>
                        </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
<?php else: ?>
        <p>No users found with this phone number.</p>
<?php endif; ?>

<a href="admin.php" class="button">Back to Admin Panel</a>
</body>
</html>
