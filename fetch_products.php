<?php
session_start();
include 'db.php'; // Include your database connection file

// Check if printing center ID is provided
if (isset($_GET['printing_center_id'])) {
    $printingCenterId = intval($_GET['printing_center_id']);

    // Prepare statement to fetch products from the products table
    $stmt = $conn->prepare("SELECT product_name, product_price FROM products WHERE printing_center_id = ?");
    $stmt->bind_param("i", $printingCenterId);
    $stmt->execute();
    $stmt->store_result();

    // Check if there are any products
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($product, $productPrice);
        $products = [];
        while ($stmt->fetch()) {
            $products[] = [
                'product_name' => $product,
                'product_price' => $productPrice,
            ];
        }
        echo json_encode($products);
    } else {
        echo json_encode([]); // Return empty array if no products found
    }
    $stmt->close();
    exit();
}

// If no printing center ID is provided, return an empty JSON array
echo json_encode([]);
