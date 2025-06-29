<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once "db.php";

$sql = "SELECT p.*, c.name as category_name, (SELECT image_data FROM product_images WHERE
     product_id = p.id LIMIT 1) as first_image FROM products p LEFT JOIN categories c ON
     p.category_id = c.id";
$result = $mysqli->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Products</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
    $(document).ready(function(){
        $("#searchInput").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $("#productsTable tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });
    });
    </script>
</head>
<body class="bg-dark">
    <?php include 'templates/header.php'; ?>

    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h2>Products</h2>
                <?php if (isset($_SESSION["role"]) && $_SESSION["role"] === 'admin'): ?>
                <a href="add_product.php" class="btn btn-success pull-right"><i class="fa fa-plus"></i> Add New Product</a>
                <a href="export_products.php" class="btn btn-info pull-right" style="margin-right: 10px;">Export to CSV</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <input type="text" id="searchInput" class="form-control" placeholder="Search for products...">
                <br>
                <table class="table table-bordered table-striped table-dark">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Image</th>
                            <th>SKU</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Category</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="productsTable">
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td>
                                        <?php if (!empty($row['first_image'])): ?>
                                            <img src="data:image/jpeg;base64,<?php echo $row['first_image']; ?>" width="100" />
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $row['sku']; ?></td>
                                    <td><?php echo $row['name']; ?></td>
                                    <td><?php echo $row['description']; ?></td>
                                    <td><?php echo $row['price']; ?></td>
                                    <td><?php echo $row['stock']; ?></td>
                                    <td><?php echo $row['category_name'] ?? 'N/A'; ?></td>
                                    <td>
                                        <?php if (isset($_SESSION["role"]) && $_SESSION["role"] === 'admin'): ?>
                                        <a href="edit_product.php?id=<?php echo $row['id']; ?>" class="btn btn-primary">Edit</a>
                                        <a href="delete_product.php?id=<?php echo $row['id']; ?>" class="btn btn-danger">Delete</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">No products found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php include 'templates/footer.php'; ?>
</body>
</html>