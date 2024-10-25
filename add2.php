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

    // Check if the printing center already exists
    $stmt = $conn->prepare("SELECT * FROM printing_centers WHERE name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $errorMessage = "Printing center '$name' already exists.";
    } else {
        $stmt = $conn->prepare("INSERT INTO printing_centers (name, contact_number) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $contactNumber);
        $stmt->execute();
        $printingCenterId = $conn->insert_id;
        $successMessage = "Printing center '$name' added successfully.";
    }
    $stmt->close();
}

// Handle adding a product to a printing center
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $printingCenterId = $_POST['printing_center_id'];
    $productName = $_POST['product_name'];
    $productPrice = $_POST['product_price'];

    $stmt = $conn->prepare("INSERT INTO products (printing_center_id, product_name, product_price) VALUES (?, ?, ?)");
    $stmt->bind_param("isd", $printingCenterId, $productName, $productPrice);
    $stmt->execute();
    $stmt->close();
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
}

// Handle updating a product
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_product'])) {
    $productId = $_POST['product_id'];
    $productName = $_POST['product_name'];
    $productPrice = $_POST['product_price'];

    $stmt = $conn->prepare("UPDATE products SET product_name = ?, product_price = ? WHERE id = ?");
    $stmt->bind_param("sdi", $productName, $productPrice, $productId);
    $stmt->execute();
    $stmt->close();
}

// Handle updating a printing center
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_printing_center'])) {
    $centerId = $_POST['center_id'];
    $centerName = $_POST['center_name'];
    $contactNumber = $_POST['contact_number'];

    $stmt = $conn->prepare("UPDATE printing_centers SET name = ?, contact_number = ? WHERE id = ?");
    $stmt->bind_param("ssi", $centerName, $contactNumber, $centerId);
    $stmt->execute();
    $stmt->close();
}

// Handle updating a delivery center
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_delivery_center'])) {
    $centerId = $_POST['delivery_center_id'];
    $centerName = $_POST['delivery_center_name'];
    $contactNumber = $_POST['delivery_contact_number'];
    $price = $_POST['delivery_price'];

    $stmt = $conn->prepare("UPDATE delivery_centers SET name = ?, contact_number = ?, price = ? WHERE id = ?");
    $stmt->bind_param("ssdi", $centerName, $contactNumber, $price, $centerId);
    $stmt->execute();
    $stmt->close();
}

// Fetch printing centers
$printingCenters = $conn->query("SELECT * FROM printing_centers");

// Fetch delivery centers
$deliveryCenters = $conn->query("SELECT * FROM delivery_centers");

// Fetch products
$products = $conn->query("SELECT p.*, pc.name AS center_name FROM products p JOIN printing_centers pc ON p.printing_center_id = pc.id");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Centers</title>
    <style>
        /* Include existing styles */
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
        .form-container button {
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
    </style>
</head>

<body>

    <h1>Manage Printing and Delivery Centers</h1>

    <div class="back-button">
        <a href="admin.php" class="button">Back to Admin Panel</a>
    </div>

    <?php if (isset($errorMessage)): ?>
        <p style="color: red;"><?= htmlspecialchars($errorMessage) ?></p>
    <?php elseif (isset($successMessage)): ?>
        <p style="color: green;"><?= htmlspecialchars($successMessage) ?></p>
    <?php endif; ?>

    <h2>Add Printing Center</h2>
    <form method="POST" class="form-container">
        <input type="text" name="name" placeholder="Center Name" required>
        <input type="text" name="contact_number" placeholder="Contact Number" required>
        <button type="submit" name="add_printing_center" class="button">Add Printing Center</button>
    </form>

    <h2>Add Product to Printing Center</h2>
    <form method="POST" class="form-container">
        <select name="printing_center_id" required>
            <option value="">Select Printing Center</option>
            <?php while ($center = $printingCenters->fetch_assoc()): ?>
                <option value="<?= $center['id'] ?>"><?= htmlspecialchars($center['name']) ?></option>
            <?php endwhile; ?>
        </select>
        <input type="text" name="product_name" placeholder="Product Name" required>
        <input type="number" step="0.01" name="product_price" placeholder="Product Price" required>
        <button type="submit" name="add_product" class="button">Add Product</button>
    </form>

    <h2>Printing Centers</h2>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Contact Number</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($center = $printingCenters->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($center['name']) ?></td>
                    <td><?= htmlspecialchars($center['contact_number']) ?></td>
                    <td>
                        <button
                            onclick="document.getElementById('editCenterId').value = '<?= $center['id'] ?>'; document.getElementById('editCenterName').value = '<?= htmlspecialchars($center['name']) ?>'; document.getElementById('editContactNumber').value = '<?= htmlspecialchars($center['contact_number']) ?>';">Edit</button>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <h2>Edit Printing Center</h2>
    <form method="POST" class="form-container">
        <input type="hidden" id="editCenterId" name="center_id">
        <input type="text" id="editCenterName" name="center_name" placeholder="Center Name" required>
        <input type="text" id="editContactNumber" name="contact_number" placeholder="Contact Number" required>
        <button type="submit" name="update_printing_center" class="button">Update Printing Center</button>
    </form>

    <h2>Delivery Centers</h2>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Contact Number</th>
                <th>Price</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($center = $deliveryCenters->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($center['name']) ?></td>
                    <td><?= htmlspecialchars($center['contact_number']) ?></td>
                    <td><?= htmlspecialchars($center['price']) ?></td>
                    <td>
                        <button
                            onclick="document.getElementById('editDeliveryCenterId').value = '<?= $center['id'] ?>'; document.getElementById('editDeliveryCenterName').value = '<?= htmlspecialchars($center['name']) ?>'; document.getElementById('editDeliveryContactNumber').value = '<?= htmlspecialchars($center['contact_number']) ?>'; document.getElementById('editDeliveryPrice').value = '<?= $center['price'] ?>';">Edit</button>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <h2>Edit Delivery Center</h2>
    <form method="POST" class="form-container">
        <input type="hidden" id="editDeliveryCenterId" name="delivery_center_id">
        <input type="text" id="editDeliveryCenterName" name="delivery_center_name" placeholder="Center Name" required>
        <input type="text" id="editDeliveryContactNumber" name="delivery_contact_number" placeholder="Contact Number"
            required>
        <input type="number" step="0.01" id="editDeliveryPrice" name="delivery_price" placeholder="Price" required>
        <button type="submit" name="update_delivery_center" class="button">Update Delivery Center</button>
    </form>

    <h2>Products</h2>
    <table>
        <thead>
            <tr>
                <th>Product Name</th>
                <th>Price</th>
                <th>Center Name</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($product = $products->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($product['product_name']) ?></td>
                    <td><?= htmlspecialchars($product['product_price']) ?></td>
                    <td><?= htmlspecialchars($product['center_name']) ?></td>
                    <td>
                        <button
                            onclick="document.getElementById('editProductId').value = '<?= $product['id'] ?>'; document.getElementById('editProductName').value = '<?= htmlspecialchars($product['product_name']) ?>'; document.getElementById('editProductPrice').value = '<?= $product['product_price'] ?>';">Edit</button>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <h2>Edit Product</h2>
    <form method="POST" class="form-container">
        <input type="hidden" id="editProductId" name="product_id">
        <input type="text" id="editProductName" name="product_name" placeholder="Product Name" required>
        <input type="number" step="0.01" id="editProductPrice" name="product_price" placeholder="Product Price"
            required>
        <button type="submit" name="update_product" class="button">Update Product</button>
    </form>

</body>

</html>