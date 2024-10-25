<?php
session_start();
include 'db.php'; // Include your database connection file

// Redirect to login if not logged in or not admin
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit();
}

// Fetch orders

$orders = $conn->query("SELECT o.*, u.name AS customer_name, u.phone AS customer_phone FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.id DESC");


// Fetch users for the dropdown
$users = $conn->query("SELECT id, name FROM users");

// Fetch distinct customer names for the filter
$customerQuery = "SELECT DISTINCT u.name FROM users u JOIN orders o ON u.id = o.user_id";
$customerResult = $conn->query($customerQuery);
$customers = $customerResult->fetch_all(MYSQLI_ASSOC);

// Fetch distinct order statuses for the filter
$statusQuery = "SELECT DISTINCT status FROM orders";
$statusResult = $conn->query($statusQuery);
$statuses = $statusResult->fetch_all(MYSQLI_ASSOC);

// Fetch distinct delivery centers
$deliveryCenterQuery = "SELECT DISTINCT d.name FROM delivery_centers d JOIN orders o ON d.id = o.delivery_center_id";
$deliveryCenterResult = $conn->query($deliveryCenterQuery);
$deliveryCenters = $deliveryCenterResult->fetch_all(MYSQLI_ASSOC);

// Fetch distinct printing centers
$printingCenterQuery = "SELECT DISTINCT p.name FROM printing_centers p JOIN orders o ON p.id = o.printing_center_id";
$printingCenterResult = $conn->query($printingCenterQuery);
$printingCenters = $printingCenterResult->fetch_all(MYSQLI_ASSOC);
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<div class="admin-buttons">
    <button onclick="window.location.href='manage_centers.php'"class="button">Manage Centers</button>
    <button onclick="window.location.href='manage_users.php'"  class="button">Users</button>
    <a class="button" href="?action=logout">Logout</a>
</div>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 20px;
        }

        h1 {
            color: #333;
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        th, td {
            padding: 10px;
            border: 1px solid #ddd;
        }

        th {
            background-color: #007BFF;
            color: white;
        }

        .button {
            background-color: #007BFF;
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            text-decoration: none;
        }

        .button:hover {
            background-color: #0056b3;
        }

        .form-container {
            margin: 20px 0;
            text-align: center;
        }

        select, input {
            margin: 5px;
            padding: 10px;
            width: 200px;
        }
    </style>
</head>

<body>
    <h1>Admin Panel - Order Management</h1>
    <h2>Filter Orders</h2>
    <form id="filterForm" class="form-container">
        <select name="customer_name" id="customer_name">
            <option value="">All Customers</option>
            <?php foreach ($customers as $customer): ?>
                <option value="<?= htmlspecialchars($customer['name']) ?>"><?= htmlspecialchars($customer['name']) ?></option>
            <?php endforeach; ?>
        </select>
    
        <select name="status" id="status">
            <option value="">All Statuses</option>
            <?php foreach ($statuses as $status): ?>
                <option value="<?= htmlspecialchars($status['status']) ?>"><?= htmlspecialchars($status['status']) ?></option>
            <?php endforeach; ?>
        </select>
    
        <select name="delivery_center" id="delivery_center">
            <option value="">All Delivery Centers</option>
            <?php foreach ($deliveryCenters as $center): ?>
                <option value="<?= htmlspecialchars($center['name']) ?>"><?= htmlspecialchars($center['name']) ?></option>
            <?php endforeach; ?>
        </select>
    
        <select name="printing_center" id="printing_center">
            <option value="">All Printing Centers</option>
            <?php foreach ($printingCenters as $center): ?>
                <option value="<?= htmlspecialchars($center['name']) ?>"><?= htmlspecialchars($center['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="text" id="phone_number" placeholder="Search by Phone Number">
        <button type="button" id="filterButton" class="button">Search</button>
        <button type="button" id="clearFilters" class="button">Clear Filters</button>
    </form>

    <script>
        document.getElementById('filterButton').addEventListener('click', function() {
            const customerName = document.getElementById('customer_name').value;
            const status = document.getElementById('status').value;
            const deliveryCenter = document.getElementById('delivery_center').value;
            const printingCenter = document.getElementById('printing_center').value;
            const phoneNumber = document.getElementById('phone_number').value; // Get phone number


            const xhr = new XMLHttpRequest();
            xhr.open('GET', `filter.php?customer_name=${customerName}&status=${status}&delivery_center=${deliveryCenter}&printing_center=${printingCenter}&phone_number=${phoneNumber}`, true);
            xhr.onload = function() {
                if (this.status === 200) {
                    document.querySelector('tbody').innerHTML = this.responseText;
                }
            };
            xhr.send();
        });

        document.getElementById('clearFilters').addEventListener('click', function() {
            document.getElementById('customer_name').value = '';
            document.getElementById('status').value = '';
            document.getElementById('delivery_center').value = '';
            document.getElementById('printing_center').value = '';
            document.getElementById('phone_number').value = '';
            document.getElementById('filterButton').click();
        });
    </script>

    <h3>Add New Order</h3>
    <form id="addOrderForm" class="form-container" enctype="multipart/form-data">
        <select name="user_id" required>
            <option value="">Select Customer</option>
            <?php while ($user = $users->fetch_assoc()): ?>
                <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?></option>
            <?php endwhile; ?>
        </select>

        <input type="file" name="file_path" required>

        <button type="submit" class="button">Add Order</button>
    </form>

    <h2>Order List</h2>
    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer Name</th>
                <th>Phone</th> <!-- New column for phone number -->
                <th>Order Details</th>
                <th>Status</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($order = $orders->fetch_assoc()): ?>
                <tr>
                    <td><?= $order['id'] ?></td>
                    <td><?= htmlspecialchars($order['customer_name']) ?></td>
                    <td><?= htmlspecialchars($order['customer_phone']) ?></td> <!-- Display phone number -->
                    <td><?= htmlspecialchars($order['file_path']) ?></td>
                    <td><?= htmlspecialchars($order['status']) ?></td>
                    <td><?= htmlspecialchars($order['created_at']) ?></td>
                    <td>
                        <a href="order_details.php?order_id=<?= $order['id'] ?>" class="button">Details</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <script>
        document.getElementById('addOrderForm').addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent form submission
            const formData = new FormData(this);

            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'add_order.php', true);
            xhr.onload = function() {
            if (this.status === 200) {
                // Prepend the new order row to the top of the table
                document.querySelector('tbody').insertAdjacentHTML('afterbegin', this.responseText);
                document.getElementById('addOrderForm').reset(); // Reset form fields
            }
            };
            xhr.send(formData);
        });
    </script>

</body>
</html>
