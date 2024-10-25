
<?php
session_start();
include 'db.php'; // Include your database connection file

// Set the timezone to Egypt
date_default_timezone_set('Africa/Cairo');

// Redirect to login if not logged in or not admin
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit();
}

// Fetch order details based on order_id
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

$stmt = $conn->prepare("SELECT o.*, u.name as customer_name, d.name as delivery_center, p.name as printing_center 
                        FROM orders o 
                        JOIN users u ON o.user_id = u.id 
                        LEFT JOIN delivery_centers d ON o.delivery_center_id = d.id
                        LEFT JOIN printing_centers p ON o.printing_center_id = p.id 
                        WHERE o.id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch issues related to the order
$issues = $conn->query("SELECT * FROM order_issues WHERE order_id = $order_id");

// Fetch delivery centers
$deliveryCenters = $conn->query("SELECT * FROM delivery_centers");

// Fetch printing centers
$printingCenters = $conn->query("SELECT * FROM printing_centers");

// Function to convert execution time to hours, minutes, and seconds
function formatExecutionTime($seconds)
{
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $seconds = $seconds % 60;
    return sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
}

// Handle updates to the order if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle status update
    if (isset($_POST['update_status'])) {
        $newStatus = $_POST['status'];

        // Check if the status is changing to Pending, Printed, or Delivered and update the respective fields
        if ($newStatus == 'Pending') {
            $pendingAt = date('Y-m-d H:i:s');

            $stmt = $conn->prepare("UPDATE orders SET status = ?, pending_at = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("ssi", $newStatus, $pendingAt, $order_id);
        } elseif ($newStatus == 'Printed') {
            $printedAt = date('Y-m-d H:i:s');
            $executionTimePrinted = strtotime($printedAt) - strtotime($order['pending_at']);

            $stmt = $conn->prepare("UPDATE orders SET status = ?, printed_at = ?, execution_time_printed = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("ssii", $newStatus, $printedAt, $executionTimePrinted, $order_id);
        } elseif ($newStatus == 'Delivered') {
            $deliveredAt = date('Y-m-d H:i:s');
            $executionTimeDelivered = strtotime($deliveredAt) - strtotime($order['printed_at']);

            $stmt = $conn->prepare("UPDATE orders SET status = ?, delivered_at = ?, execution_time_delivered = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("ssii", $newStatus, $deliveredAt, $executionTimeDelivered, $order_id);
        } else {
            $stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("si", $newStatus, $order_id);
        }

        $stmt->execute();
        $stmt->close();
    }

    // Update delivery center, printing center, and calculate prices
    if (isset($_POST['update_order'])) {
        $deliveryCenterId = $_POST['delivery_center'];
        $printingCenterId = $_POST['printing_center'];
        $quantity = intval($_POST['quantity']); // Quantity of products needed
        $selectedProductPrice = $_POST['product']; // Now we get the selected product price

        // Fetch delivery price from the selected delivery center
        $stmt = $conn->prepare("SELECT price FROM delivery_centers WHERE id = ?");
        $stmt->bind_param("i", $deliveryCenterId);
        $stmt->execute();
        $stmt->bind_result($deliveryPrice);
        $stmt->fetch();
        $stmt->close();

        // Calculate prices
        $printingPrice = $selectedProductPrice * $quantity; // Use the selected product price
        $totalPrice = $printingPrice + $deliveryPrice;

        // Update order with selected centers and calculated prices
        $stmt = $conn->prepare("UPDATE orders SET delivery_center_id = ?, printing_center_id = ?, printing_price = ?, delivery_price = ?, quantity = ?, total_price = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("iiiidii", $deliveryCenterId, $printingCenterId, $printingPrice, $deliveryPrice, $quantity, $totalPrice, $order_id);
        $stmt->execute();
        $stmt->close();
    }

    // Cancel order
    if (isset($_POST['cancel_order'])) {
        $cancellationReason = $_POST['cancellation_reason'];

        $stmt = $conn->prepare("UPDATE orders SET status = 'Cancelled', cancellation_reason = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $cancellationReason, $order_id);
        $stmt->execute();
        $stmt->close();
    }

    // Add issue
    if (isset($_POST['add_issue'])) {
        $issueDescription = $_POST['issue_description'];
        $reason = $_POST['reason'];

        $stmt = $conn->prepare("INSERT INTO order_issues (order_id, issue_description, reason) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $order_id, $issueDescription, $reason);
        $stmt->execute();
        $stmt->close();

        // Refresh issues after adding a new issue
        $issues = $conn->query("SELECT * FROM order_issues WHERE order_id = $order_id");
    }

    // Refresh the order details after any updates
    $stmt = $conn->prepare("SELECT o.*, u.name as customer_name, d.name as delivery_center, p.name as printing_center 
                            FROM orders o 
                            JOIN users u ON o.user_id = u.id 
                            LEFT JOIN delivery_centers d ON o.delivery_center_id = d.id
                            LEFT JOIN printing_centers p ON o.printing_center_id = p.id 
                            WHERE o.id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Admin Panel</title>
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
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        th,
        td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
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
            margin-top: 20px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
        }

        .button:hover {
            background-color: #0056b3;
        }

        .form-container {
            margin: 20px 0;
            padding: 20px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .issue-list {
            margin-top: 20px;
            padding: 10px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .issue-item {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }

        .issue-item:last-child {
            border-bottom: none;
        }

        .issue-description {
            font-weight: bold;
        }

        .issue-reason {
            color: #555;
        }
    </style>
</head>

<body>

    <h1>Order Details for Order ID: <?= htmlspecialchars($order['id']) ?></h1>

    <table>
        <tr>
            <th>Customer Name</th>
            <td><?= htmlspecialchars($order['customer_name']) ?></td>
        </tr>
        <tr>
            <th>File Path</th>
            <td>
                <?= htmlspecialchars($order['file_path']) ?>
                <a href="<?= htmlspecialchars($order['file_path']) ?>" target="_blank">
                    (Open File)
                </a>
            </td>
        </tr>


        <tr>
            <th>Status</th>
            <td><?= htmlspecialchars($order['status']) ?></td>
        </tr>
        <tr>
            <th>Delivery Center</th>
            <td><?= htmlspecialchars($order['delivery_center']) ?></td>
        </tr>
        <tr>
            <th>Printing Center</th>
            <td><?= htmlspecialchars($order['printing_center']) ?></td>
        </tr>
        <tr>
            <th>Quantity</th>
            <td><?= htmlspecialchars($order['quantity']) ?></td>
        </tr>
        <tr>
            <th>Printing Price</th>
            <td><?= htmlspecialchars($order['printing_price']) ?></td>
        </tr>
        <tr>
            <th>Delivery Price</th>
            <td><?= htmlspecialchars($order['delivery_price']) ?></td>
        </tr>
        <tr>
            <th>Total Price</th>
            <td><?= htmlspecialchars($order['total_price']) ?></td>
        </tr>
        <tr>
            <th>Created At</th>
            <td><?= htmlspecialchars($order['created_at']) ?></td>
        </tr>
            <th>Pending At</th>
            <td><?= htmlspecialchars($order['pending_at']) ?? 'N/A' ?></td>
            </tr>
        <tr>
                <th>Printed At</th>
                <td><?= htmlspecialchars($order['printed_at']) ?? 'N/A' ?></td>
            </tr>
        <tr>
                <th>Delivered At</th>
                <td><?= htmlspecialchars($order['delivered_at']) ?? 'N/A' ?></td>
        </tr>
        <tr>
                <th>Execution Time Printed</th>
                <td><?= formatExecutionTime($order['execution_time_printed']) ?? 'N/A' ?></td>
        </tr>
        <tr>
                <th>Execution Time Delivered</th>
                <td><?= formatExecutionTime($order['execution_time_delivered']) ?? 'N/A' ?></td>
        </tr>
    </table>

    <div class="form-container">
        <form method="POST">
            <h3>Update Order Status</h3>
            <select name="status" required>
                <option value="Pending" <?= $order['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                <option value="Printed" <?= $order['status'] == 'Printed' ? 'selected' : '' ?>>Printed</option>
                <option value="Delivered" <?= $order['status'] == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                <option value="Cancelled" <?= $order['status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
            <button type="submit" name="update_status" class="button">Update Status</button>
        </form>
    </div>

    <div class="form-container">
        <form method="POST">
            <h3>Update Delivery and Printing Center</h3>
            <label for="delivery_center">Select Delivery Center:</label>
            <select name="delivery_center" id="delivery_center" required>
                <?php while ($delivery = $deliveryCenters->fetch_assoc()): ?>
                    <option value="<?= $delivery['id'] ?>" <?= $order['delivery_center_id'] == $delivery['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($delivery['name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label for="printing_center">Select Printing Center:</label>
            <select name="printing_center" id="printing_center" onchange="fetchProducts(this.value)" required>
                <?php while ($printing = $printingCenters->fetch_assoc()): ?>
                    <option value="<?= $printing['id'] ?>" <?= $order['printing_center_id'] == $printing['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($printing['name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label for="product">Select Product:</label>
            <select name="product" id="product" required>
                <option value="">Select Product</option>
                <!-- Products will be populated based on the selected printing center -->
            </select>

            <label for="quantity">Quantity:</label>
            <input type="number" name="quantity" id="quantity" min="1"
                value="<?= htmlspecialchars($order['quantity']) ?>" required>

            <button type="submit" name="update_order" class="button">Update Order</button>
        </form>
    </div>

    <div class="form-container">
        <form method="POST">
            <h3>Cancel Order</h3>
            <label for="cancellation_reason">Cancellation Reason:</label>
            <textarea name="cancellation_reason" id="cancellation_reason" required></textarea>
            <button type="submit" name="cancel_order" class="button">Cancel Order</button>
        </form>
    </div>

    <div class="form-container">
        <h3>Report an Issue</h3>
        <form method="POST">
            <label for="issue_description">Issue Description:</label>
            <input type="text" name="issue_description" id="issue_description" required>
            <label for="reason">Reason:</label>
            <input type="text" name="reason" id="reason" required>
            <button type="submit" name="add_issue" class="button">Add Issue</button>
        </form>
    </div>

    <div class="issue-list">
        <h3>Reported Issues</h3>
        <?php while ($issue = $issues->fetch_assoc()): ?>
            <div class="issue-item">
                <span class="issue-description"><?= htmlspecialchars($issue['issue_description']) ?></span>
                <span class="issue-reason">(Reason: <?= htmlspecialchars($issue['reason']) ?>)</span>
            </div>
        <?php endwhile; ?>
    </div>

    <script>
        // Fetch products based on selected printing center on page load
        window.onload = function () {
            const printingCenterId = document.getElementById('printing_center').value;
            fetchProducts(printingCenterId);
        };

        function fetchProducts(printingCenterId) {
            const productSelect = document.getElementById('product');
            productSelect.innerHTML = '<option value="">Loading...</option>'; // Loading message

            if (printingCenterId) {
                const xhr = new XMLHttpRequest();
                xhr.open('GET', 'fetch_products.php?printing_center_id=' + printingCenterId, true);
                xhr.onload = function () {
                    if (this.status === 200) {
                        const response = JSON.parse(this.responseText);
                        productSelect.innerHTML = '<option value="">Select Product</option>'; // Reset options
                        response.forEach(product => {
                            productSelect.innerHTML += `<option value="${product.product_price}">${product.product_name} - Price: ${product.product_price}</option>`;
                        });
                    } else {
                        productSelect.innerHTML = '<option value="">Error loading products</option>';
                    }
                };
                xhr.send();
            } else {
                productSelect.innerHTML = '<option value="">Select Product</option>';
            }
        }
    </script>
<a href="admin.php" class="button">Back to Admin Panel</a>

</body>

</html>