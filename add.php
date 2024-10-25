<?php
session_start();
include 'db.php'; // Include your database connection file

// Redirect to login if not logged in or not admin
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit();
}

// Handle adding a printing center
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_printing_center'])) {
    $name = $_POST['name'];
    $contactNumber = $_POST['contact_number'];

    // Check if the center already exists by name
    $checkStmt = $conn->prepare("SELECT id FROM printing_centers WHERE name = ?");
    $checkStmt->bind_param("s", $name);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        $_SESSION['message'] = "Printing center already exists.";
    } else {
        $stmt = $conn->prepare("INSERT INTO printing_centers (name, contact_number) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $contactNumber);
        $stmt->execute();
        $stmt->close();
        $_SESSION['message'] = "Printing center added successfully.";
    }

    $checkStmt->close();
}

// Handle adding a delivery center
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_delivery_center'])) {
    $name = $_POST['name'];
    $contactNumber = $_POST['contact_number'];
    $price = $_POST['price'];

    $stmt = $conn->prepare("INSERT INTO delivery_centers (name, contact_number, price) VALUES (?, ?, ?)");
    $stmt->bind_param("ssd", $name, $contactNumber, $price);
    $stmt->execute();
    $stmt->close();
    $_SESSION['message'] = "Delivery center added successfully.";
}

// Handle adding a product to a printing center
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $centerId = $_POST['center_id'];
    $productName = $_POST['product_name'];
    $productPrice = $_POST['product_price'];

    $stmt = $conn->prepare("INSERT INTO products (printing_center_id, product_name, product_price) VALUES (?, ?, ?)");
    $stmt->bind_param("isd", $centerId, $productName, $productPrice);
    $stmt->execute();
    $stmt->close();
    $_SESSION['message'] = "Product added successfully.";
}

// Fetch printing centers
$printingCenters = $conn->query("SELECT * FROM printing_centers");

// Fetch delivery centers
$deliveryCenters = $conn->query("SELECT * FROM delivery_centers");

// Fetch products for each center
$products = $conn->query("SELECT p.product_name, p.product_price, c.name AS center_name FROM products p JOIN printing_centers c ON p.printing_center_id = c.id");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Centers</title>
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

        th,
        td {
            padding: 10px;
            border: 1px solid #ddd;
        }

        th {
            background-color: #007BFF;
            color: white;
        }

        .form-container {
            margin: 20px 0;
        }

        .form-container input,
        .form-container button,
        .form-container select {
            padding: 10px;
            margin: 5px;
            border-radius: 5px;
            border: 1px solid #ccc;
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

        .back-button {
            margin-bottom: 20px;
            display: block;
            text-align: center;
        }

        .message {
            color: red;
            text-align: center;
        }
    </style>
</head>

<body>

    <h1>Manage Printing and Delivery Centers</h1>

    <div class="back-button">
        <a href="admin.php" class="button">Back to Admin Panel</a>
    </div>

    <?php if (isset($_SESSION['message'])): ?>
        <p class="message"><?= htmlspecialchars($_SESSION['message']) ?></p>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <h2>Add Printing Center</h2>
    <form method="POST" class="form-container">
        <input type="text" name="name" placeholder="Center Name" required>
        <input type="text" name="contact_number" placeholder="Contact Number" required>
        <button type="submit" name="add_printing_center" class="button">Add Printing Center</button>
    </form>



    <h2>Printing Centers</h2>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Contact Number</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($center = $printingCenters->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($center['name']) ?></td>
                    <td><?= htmlspecialchars($center['contact_number']) ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <h2>Add Product to Printing Center</h2>
    <form method="POST" class="form-container">
        <select name="center_id" required>
            <option value="">Select Center</option>
            <?php foreach ($printingCenters as $center): ?>
                <option value="<?= $center['id'] ?>"><?= htmlspecialchars($center['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="product_name" placeholder="Product Name" required>
        <input type="number" step="0.01" name="product_price" placeholder="Product Price" required>
        <button type="submit" name="add_product" class="button">Add Product</button>
    </form>

    <h2>Products</h2>
    <table>
        <thead>
            <tr>
                <th>Center</th>
                <th>Product</th>
                <th>Price</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($product = $products->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($product['center_name']) ?></td>
                    <td><?= htmlspecialchars($product['product_name']) ?></td>
                    <td><?= htmlspecialchars($product['product_price']) ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <h2>Add Delivery Center</h2>
    <form method="POST" class="form-container">
        <input type="text" name="name" placeholder="Center Name" required>
        <input type="text" name="contact_number" placeholder="Contact Number" required>
        <input type="number" step="0.01" name="price" placeholder="Price" required>
        <button type="submit" name="add_delivery_center" class="button">Add Delivery Center</button>
    </form>
    <h2>Delivery Centers</h2>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Contact Number</th>
                <th>Price</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($center = $deliveryCenters->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($center['name']) ?></td>
                    <td><?= htmlspecialchars($center['contact_number']) ?></td>
                    <td><?= htmlspecialchars($center['price']) ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

</body>

</html>