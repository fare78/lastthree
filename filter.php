<?php
include 'db.php';

$whereClauses = [];
$params = [];

// Filtering by customer name
if (!empty($_GET['customer_name'])) {
    $whereClauses[] = "u.name = ?";
    $params[] = $_GET['customer_name'];
}

// Filtering by status
if (!empty($_GET['status'])) {
    $whereClauses[] = "o.status = ?";
    $params[] = $_GET['status'];
}

// Filtering by delivery center
if (!empty($_GET['delivery_center'])) {
    $whereClauses[] = "d.name = ?";
    $params[] = $_GET['delivery_center'];
}

// Filtering by printing center
if (!empty($_GET['printing_center'])) {
    $whereClauses[] = "p.name = ?";
    $params[] = $_GET['printing_center'];
}

// Filtering by phone number
if (!empty($_GET['phone_number'])) {
    $whereClauses[] = "u.phone = ?";  // Use the 'phone' column here
    $params[] = $_GET['phone_number'];
}

$query = "SELECT o.*, u.name as customer_name, u.phone as customer_phone FROM orders o 
          JOIN users u ON o.user_id = u.id 
          LEFT JOIN delivery_centers d ON o.delivery_center_id = d.id 
          LEFT JOIN printing_centers p ON o.printing_center_id = p.id";

// Add filtering conditions
if (!empty($whereClauses)) {
    $query .= " WHERE " . implode(" AND ", $whereClauses);
}

$query .= " ORDER BY o.id DESC";

$stmt = $conn->prepare($query);
if ($params) {
    $stmt->bind_param(str_repeat("s", count($params)), ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Generate the rows for the table
while ($order = $result->fetch_assoc()): ?>
    <tr>
        <td><?= htmlspecialchars($order['id']) ?></td>
        <td><?= htmlspecialchars($order['customer_name']) ?></td>
        <td><?= htmlspecialchars($order['customer_phone']) ?></td> <!-- Phone Column -->
        <td><?= htmlspecialchars($order['file_path']) ?></td>
        <td><?= htmlspecialchars($order['status']) ?></td>
        <td><?= htmlspecialchars($order['created_at']) ?></td>
        <td><a href="order_details.php?order_id=<?= $order['id'] ?>" class="button">Details</a></td>
    </tr>
<?php endwhile;

$stmt->close();
?>