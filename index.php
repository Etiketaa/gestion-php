<?php
session_start();

// Check if the user is logged in, otherwise redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once "db.php";

// Fetch Dashboard Data

// Total Products
$total_products = 0;
$sql_total_products = "SELECT COUNT(*) AS total FROM products";
if ($result_total_products = $mysqli->query($sql_total_products)) {
    $row = $result_total_products->fetch_assoc();
    $total_products = $row['total'];
}

// Total Stock Quantity
$total_stock_quantity = 0;
$sql_total_stock = "SELECT SUM(stock) AS total_stock FROM products";
if ($result_total_stock = $mysqli->query($sql_total_stock)) {
    $row = $result_total_stock->fetch_assoc();
    $total_stock_quantity = $row['total_stock'];
}

// Low Stock Products (threshold: 5)
$low_stock_products = [];
$sql_low_stock = "SELECT id, name, stock FROM products WHERE stock <= 5 ORDER BY stock ASC LIMIT 10";
if ($result_low_stock = $mysqli->query($sql_low_stock)) {
    while ($row = $result_low_stock->fetch_assoc()) {
        $low_stock_products[] = $row;
    }
}

// Recently Added Products
$recent_products = [];
$sql_recent_products = "SELECT id, name, created_at FROM products ORDER BY created_at DESC LIMIT 5";
if ($result_recent_products = $mysqli->query($sql_recent_products)) {
    while ($row = $result_recent_products->fetch_assoc()) {
        $recent_products[] = $row;
    }
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'templates/header.php'; ?>

    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h1 class="text-white">Welcome, <b><?php echo htmlspecialchars($_SESSION["username"]); ?></b>!</h1>
                <hr>
            </div>
        </div>

        <div class="row">
            <?php if (isset($_SESSION["role"]) && $_SESSION["role"] === 'admin' && $pending_users_count > 0): ?>
            <div class="col-md-12">
                <div class="alert alert-warning" role="alert">
                    You have <strong><?php echo $pending_users_count; ?></strong> new user(s) awaiting approval. <a href="manage_users.php" class="alert-link">Manage Users</a>
                </div>
            </div>
            <?php endif; ?>
            <div class="col-md-6">
                <div class="card text-white bg-primary mb-3">
                    <div class="card-header">Total Products</div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $total_products; ?></h5>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card text-white bg-success mb-3">
                    <div class="card-header">Total Stock Quantity</div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $total_stock_quantity; ?></h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <h3>Low Stock Alerts</h3>
                <?php if (!empty($low_stock_products)): ?>
                <table class="table table-bordered table-striped table-dark">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($low_stock_products as $product): ?>
                            <tr>
                                <td><?php echo $product['name']; ?></td>
                                <td><?php echo $product['stock']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <p>No products with low stock.</p>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <h3>Recently Added Products</h3>
                <?php if (!empty($recent_products)): ?>
                <table class="table table-bordered table-striped table-dark">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Added On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_products as $product): ?>
                            <tr>
                                <td><?php echo $product['name']; ?></td>
                                <td><?php echo date('Y-m-d', strtotime($product['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <p class="text-white">No recently added products.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'templates/footer.php'; ?>
</body>
</html>