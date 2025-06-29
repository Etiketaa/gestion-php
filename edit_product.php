<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["role"]) || $_SESSION["role"] !== 'admin') {
    header("location: login.php");
    exit;
}

require_once "db.php";

$name = $description = $price = $stock = $sku = $image_base64 = $category_id = "";
$name_err = $description_err = $price_err = $stock_err = $image_err = $category_err = "";

// Fetch categories
$categories = [];
$sql_categories = "SELECT id, name FROM categories";
if ($result_categories = $mysqli->query($sql_categories)) {
    while ($row = $result_categories->fetch_assoc()) {
        $categories[] = $row;
    }
}

if (isset($_POST["id"]) && !empty($_POST["id"])) {
    $id = $_POST["id"];

    // (Add validation)

    if (empty(trim($_POST["category_id"]))) {
        $category_err = "Please select a category.";
    } else {
        $category_id = trim($_POST["category_id"]);
    }

    $image_base64 = $_POST['existing_image']; // Keep existing image if no new one is uploaded
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image_tmp_path = $_FILES['image']['tmp_name'];
        $image_data = file_get_contents($image_tmp_path);
        $image_base64 = base64_encode($image_data);
    }

    if (empty($name_err) && empty($description_err) && empty($price_err) && empty($stock_err) && empty($category_err)) {
        $sql = "UPDATE products SET name=?, description=?, price=?, stock=?, sku=?, image=?, category_id=? WHERE id=?";

        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("ssdissii", $param_name, $param_description, $param_price, $param_stock, $param_sku, $param_image, $param_category_id, $param_id);

            $param_name = $_POST['name'];
            $param_description = $_POST['description'];
            $param_price = $_POST['price'];
            $param_stock = $_POST['stock'];
            $param_sku = $_POST['sku'];
            $param_image = $image_base64;
            $param_category_id = $category_id;
            $param_id = $id;

            if ($stmt->execute()) {
                // Log stock movement if stock changed
                if ($param_stock != $old_stock) {
                    $stock_change = $param_stock - $old_stock;
                    $reason = ($stock_change > 0) ? "Stock Increase (Manual Adjustment)" : "Stock Decrease (Manual Adjustment)";
                    $sql_movement = "INSERT INTO inventory_movements (product_id, quantity, reason) VALUES (?, ?, ?)";
                    if ($stmt_movement = $mysqli->prepare($sql_movement)) {
                        $stmt_movement->bind_param("iis", $param_id, $stock_change, $reason);
                        $stmt_movement->execute();
                        $stmt_movement->close();
                    }
                }

                header("location: products.php");
                exit();
            } else {
                echo "Something went wrong. Please try again later.";
            }

            $stmt->close();
        }
    }

    $mysqli->close();
} else {
    if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
        $id =  trim($_GET["id"]);

        $sql = "SELECT * FROM products WHERE id = ?";
        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("i", $param_id);
            $param_id = $id;

            if ($stmt->execute()) {
                $result = $stmt->get_result();

                if ($result->num_rows == 1) {
                    $row = $result->fetch_array(MYSQLI_ASSOC);

                    $name = $row["name"];
                    $description = $row["description"];
                    $price = $row["price"];
                    $stock = $row["stock"];
                    $sku = $row["sku"];
                    $image_base64 = $row["image"];
                    $category_id = $row["category_id"];
                } else {
                    header("location: error.php");
                    exit();
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            $stmt->close();
        }
    } else {
        header("location: error.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'templates/header.php'; ?>

    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h2 class="text-white">Edit Product</h2>
                <form action="<?php echo htmlspecialchars(basename($_SERVER['REQUEST_URI'])); ?>" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>SKU</label>
                        <input type="text" name="sku" class="form-control" value="<?php echo $sku; ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" class="form-control" value="<?php echo $name; ?>">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control"><?php echo $description; ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Price</label>
                        <input type="text" name="price" class="form-control" value="<?php echo $price; ?>">
                    </div>
                    <div class="form-group">
                        <label>Stock</label>
                        <input type="number" name="stock" class="form-control" value="<?php echo $stock; ?>">
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category_id" class="form-control <?php echo (!empty($category_err)) ? 'is-invalid' : ''; ?>">
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo ($category['id'] == $category_id) ? 'selected' : ''; ?>><?php echo $category['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="invalid-feedback"><?php echo $category_err; ?></span>
                    </div>
                    <div class="form-group">
                        <label>Current Image</label><br>
                        <?php if (!empty($image_base64)): ?>
                            <img src="data:image/jpeg;base64,<?php echo $image_base64; ?>" width="100" /><br>
                            <input type="hidden" name="existing_image" value="<?php echo $image_base64; ?>">
                        <?php else: ?>
                            No image uploaded.
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="text-white">Upload New Image (optional)</label>
                        <input type="file" name="image" class="form-control">
                        <span class="invalid-feedback"><?php echo $image_err; ?></span>
                    </div>
                    <input type="hidden" name="id" value="<?php echo $id; ?>"/>
                    <input type="submit" class="btn btn-primary" value="Submit">
                    <a href="products.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    <?php include 'templates/footer.php'; ?>
</body>
</html>