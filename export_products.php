<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["role"]) || $_SESSION["role"] !== 'admin') {
    header("location: login.php");
    exit;
}

require_once "db.php";

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="products.csv"');

$output = fopen('php://output', 'w');

fputcsv($output, array('ID', 'Name', 'Description', 'Price', 'Stock', 'SKU', 'Category'));

$sql = "SELECT p.id, p.name, p.description, p.price, p.stock, p.sku, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id";
$result = $mysqli->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
}

fclose($output);
$mysqli->close();
exit();
?>