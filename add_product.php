<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["role"]) || $_SESSION["role"] !== 'admin') {
    header("location: login.php");
    exit;
}

require_once "db.php";

function generate_sku() {
    return 'PROD-' . uniqid();
}

$name = $description = $price = $stock = $sku = $category_id = "";
$name_err = $description_err = $price_err = $stock_err = $image_err = $category_err = "";
$sku = generate_sku();

// Fetch categories
$categories = [];
$sql_categories = "SELECT id, name FROM categories";
if ($result_categories = $mysqli->query($sql_categories)) {
    while ($row = $result_categories->fetch_assoc()) {
        $categories[] = $row;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // ... (add more validation)

    if (empty(trim($_POST["category_id"]))) {
        $category_err = "Please select a category.";
    } else {
        $category_id = trim($_POST["category_id"]);
    }

    $image_base64 = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image_tmp_path = $_FILES['image']['tmp_name'];
        $image_data = file_get_contents($image_tmp_path);
        $image_base64 = base64_encode($image_data);
    } else {
        $image_err = "Please select an image to upload.";
    }

    if (empty($name_err) && empty($description_err) && empty($price_err) && empty($stock_err) && empty($image_err) && empty($category_err)) {
        $sql = "INSERT INTO products (name, description, price, stock, sku, image, category_id) VALUES (?, ?, ?, ?, ?, ?, ?)";

        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("ssdissi", $param_name, $param_description, $param_price, $param_stock, $param_sku, $param_image, $param_category_id);

            $param_name = $_POST['name'];
            $param_description = $_POST['description'];
            $param_price = $_POST['price'];
            $param_stock = $_POST['stock'];
            $param_sku = $_POST['sku'];
            $param_image = $image_base64;
            $param_category_id = $category_id;

            if ($stmt->execute()) {
                $new_product_id = $mysqli->insert_id;

                // Log initial stock movement
                $sql_movement = "INSERT INTO inventory_movements (product_id, quantity, reason) VALUES (?, ?, ?)";
                if ($stmt_movement = $mysqli->prepare($sql_movement)) {
                    $stmt_movement->bind_param("iis", $new_product_id, $param_stock, $reason);
                    $reason = "Initial Stock";
                    $stmt_movement->execute();
                    $stmt_movement->close();
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
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Product</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'templates/header.php'; ?>

    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h2 class="text-white">Add Product</h2>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>SKU</label>
                        <input type="text" name="sku" class="form-control" value="<?php echo $sku; ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Price</label>
                        <input type="text" name="price" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Stock</label>
                        <input type="number" name="stock" class="form-control">
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
                        <label class="text-white">Image</label>
                        <input type="file" name="image" class="form-control">
                        <span class="invalid-feedback"><?php echo $image_err; ?></span>
                    </div>
                    <input type="submit" class="btn btn-primary" value="Submit">
                    <a href="products.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    <?php include 'templates/footer.php'; ?>
</body>
</html>