<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once "db.php";

// Add to cart logic
if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] += $quantity;
    } else {
        $_SESSION['cart'][$product_id] = $quantity;
    }
}

// Remove from cart logic
if (isset($_GET['remove'])) {
    $product_id = $_GET['remove'];
    unset($_SESSION['cart'][$product_id]);
}

// Clear cart logic
if (isset($_GET['clear'])) {
    unset($_SESSION['cart']);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cart</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'templates/header.php'; ?>

    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h2>Cart</h2>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Total</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($_SESSION['cart'])): ?>
                            <?php
                            $total = 0;
                            foreach ($_SESSION['cart'] as $product_id => $quantity) {
                                $sql = "SELECT * FROM products WHERE id = $product_id";
                                $result = $mysqli->query($sql);
                                $product = $result->fetch_assoc();
                                $sub_total = $product['price'] * $quantity;
                                $total += $sub_total;
                                ?>
                                <tr>
                                    <td><?php echo $product['name']; ?></td>
                                    <td><?php echo $quantity; ?></td>
                                    <td>$<?php echo $product['price']; ?></td>
                                    <td>$<?php echo number_format($sub_total, 2); ?></td>
                                    <td>
                                        <a href="cart.php?remove=<?php echo $product_id; ?>" class="btn btn-danger">Remove</a>
                                    </td>
                                </tr>
                            <?php }
                            ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">Your cart is empty.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-right"><strong>Total</strong></td>
                            <td>$<?php echo isset($total) ? number_format($total, 2) : '0.00'; ?></td>
                            <td>
                                <a href="cart.php?clear=1" class="btn btn-warning">Clear Cart</a>
                                <a href="checkout.php" class="btn btn-success">Checkout</a>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <?php include 'templates/footer.php'; ?>
</body>
</html>