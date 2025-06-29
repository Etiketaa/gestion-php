<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["role"]) || $_SESSION["role"] !== 'admin') {
    header("location: login.php");
    exit;
}

require_once "db.php";

$name = $description = $price = $stock = $sku = $category_id = "";
$name_err = $description_err = $price_err = $stock_err = $category_err = "";
$existing_images = [];

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

    // Fetch old stock for movement logging
    $old_stock = 0;
    $sql_old_stock = "SELECT stock FROM products WHERE id = ?";
    if ($stmt_old_stock = $mysqli->prepare($sql_old_stock)) {
        $stmt_old_stock->bind_param("i", $id);
        $stmt_old_stock->execute();
        $stmt_old_stock->bind_result($old_stock);
        $stmt_old_stock->fetch();
        $stmt_old_stock->close();
    }

    // (Add validation for other fields)

    if (empty(trim($_POST["category_id"]))) {
        $category_err = "Please select a category.";
    } else {
        $category_id = trim($_POST["category_id"]);
    }

    // Process images
    $images_to_save = [];

    // Process pasted images first
    if (!empty($_POST['pasted_images_base64'])) {
        $pasted_images = json_decode($_POST['pasted_images_base64'], true);
        foreach ($pasted_images as $base64_data) {
            $images_to_save[] = substr($base64_data, strpos($base64_data, ',') + 1); // Remove data:image/jpeg;base64, prefix
        }
    }

    // Process uploaded files
    if (isset($_FILES['images']) && is_array($_FILES['images']['tmp_name'])) {
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] == 0) {
                $image_data = file_get_contents($tmp_name);
                $images_to_save[] = base64_encode($image_data);
            }
        }
    }

    // Delete selected existing images
    if (isset($_POST['delete_images']) && is_array($_POST['delete_images'])) {
        $sql_delete_images = "DELETE FROM product_images WHERE id = ?";
        if ($stmt_delete_images = $mysqli->prepare($sql_delete_images)) {
            foreach ($_POST['delete_images'] as $image_id) {
                $stmt_delete_images->bind_param("i", $image_id);
                $stmt_delete_images->execute();
            }
            $stmt_delete_images->close();
        }
    }

    if (empty($name_err) && empty($description_err) && empty($price_err) && empty($stock_err) && empty($category_err)) {
        $sql = "UPDATE products SET name=?, description=?, price=?, stock=?, sku=?, category_id=? WHERE id=?";

        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("ssdissi", $param_name, $param_description, $param_price, $param_stock, $param_sku, $param_category_id, $param_id);

            $param_name = $_POST['name'];
            $param_description = $_POST['description'];
            $param_price = $_POST['price'];
            $param_stock = $_POST['stock'];
            $param_sku = $_POST['sku'];
            $param_category_id = $category_id;
            $param_id = $id;

            if ($stmt->execute()) {
                // Insert new images into product_images table
                if (!empty($images_to_save)) {
                    $sql_insert_image = "INSERT INTO product_images (product_id, image_data) VALUES (?, ?)";
                    if ($stmt_insert_image = $mysqli->prepare($sql_insert_image)) {
                        foreach ($images_to_save as $img_data) {
                            $stmt_insert_image->bind_param("is", $id, $img_data);
                            $stmt_insert_image->execute();
                        }
                        $stmt_insert_image->close();
                    }
                }

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
                    $category_id = $row["category_id"];

                    // Fetch existing images for this product
                    $sql_fetch_images = "SELECT id, image_data FROM product_images WHERE product_id = ?";
                    if ($stmt_fetch_images = $mysqli->prepare($sql_fetch_images)) {
                        $stmt_fetch_images->bind_param("i", $id);
                        $stmt_fetch_images->execute();
                        $result_images = $stmt_fetch_images->get_result();
                        while ($img_row = $result_images->fetch_assoc()) {
                            $existing_images[] = $img_row;
                        }
                        $stmt_fetch_images->close();
                    }

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
                        <label class="text-white">SKU</label>
                        <input type="text" name="sku" class="form-control" value="<?php echo $sku; ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label class="text-white">Name</label>
                        <input type="text" name="name" class="form-control" value="<?php echo $name; ?>">
                    </div>
                    <div class="form-group">
                        <label class="text-white">Description</label>
                        <textarea name="description" class="form-control"><?php echo $description; ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="text-white">Price</label>
                        <input type="text" name="price" class="form-control" value="<?php echo $price; ?>">
                    </div>
                    <div class="form-group">
                        <label class="text-white">Stock</label>
                        <input type="number" name="stock" class="form-control" value="<?php echo $stock; ?>">
                    </div>
                    <div class="form-group">
                        <label class="text-white">Category</label>
                        <select name="category_id" class="form-control <?php echo (!empty($category_err)) ? 'is-invalid' : ''; ?>">
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo ($category['id'] == $category_id) ? 'selected' : ''; ?>><?php echo $category['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="invalid-feedback"><?php echo $category_err; ?></span>
                    </div>
                    <div class="form-group">
                        <label class="text-white">Current Images</label><br>
                        <div id="currentImagesContainer">
                            <?php if (!empty($existing_images)): ?>
                                <?php foreach ($existing_images as $img): ?>
                                    <div style="display: inline-block; margin-right: 10px; margin-bottom: 10px; border: 1px solid #555; padding: 5px;">
                                        <img src="data:image/jpeg;base64,<?php echo $img['image_data']; ?>" width="100" /><br>
                                        <input type="checkbox" name="delete_images[]" value="<?php echo $img['id']; ?>"> <span class="text-white">Delete</span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-white">No images uploaded for this product.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="text-white">Add New Images (Upload or Paste)</label>
                        <input type="file" name="images[]" id="imageUpload" class="form-control" multiple>
                        <br>
                        <div class="text-white">Or paste images here:</div>
                        <div id="pasteArea" style="border: 1px dashed #ccc; padding: 20px; min-height: 100px; text-align: center; cursor: pointer;" class="form-control">
                            Paste images here (Ctrl+V)
                        </div>
                        <div id="imagePreviews" style="margin-top: 10px;"></div>
                        <input type="hidden" name="pasted_images_base64" id="pastedImagesBase64">
                    </div>
                    <input type="hidden" name="id" value="<?php echo $id; ?>"/>
                    <input type="submit" class="btn btn-primary" value="Submit">
                    <a href="products.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    <?php include 'templates/footer.php'; ?>
    <script>
        let allPastedImages = [];

        function renderNewImagePreviews() {
            const previewsContainer = document.getElementById('imagePreviews');
            previewsContainer.innerHTML = '';
            allPastedImages.forEach((base64, index) => {
                const img = document.createElement('img');
                img.src = base64;
                img.style.maxWidth = '100px';
                img.style.maxHeight = '100px';
                img.style.marginRight = '10px';
                img.style.marginBottom = '10px';
                previewsContainer.appendChild(img);
            });
            document.getElementById('pastedImagesBase64').value = JSON.stringify(allPastedImages);
        }

        document.getElementById('pasteArea').addEventListener('paste', function(e) {
            e.preventDefault();
            const items = (e.clipboardData || e.originalEvent.clipboardData).items;
            for (let i = 0; i < items.length; i++) {
                if (items[i].type.indexOf('image') !== -1) {
                    const blob = items[i].getAsFile();
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        allPastedImages.push(event.target.result);
                        renderNewImagePreviews();
                        document.getElementById('imageUpload').value = ''; // Clear file input if image is pasted
                    };
                    reader.readAsDataURL(blob);
                    break;
                }
            }
        });

        document.getElementById('imageUpload').addEventListener('change', function() {
            allPastedImages = []; // Clear pasted images if files are uploaded
            const files = this.files;
            const previewsContainer = document.getElementById('imagePreviews');
            previewsContainer.innerHTML = '';

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        const img = document.createElement('img');
                        img.src = event.target.result;
                        img.style.maxWidth = '100px';
                        img.style.maxHeight = '100px';
                        img.style.marginRight = '10px';
                        img.style.marginBottom = '10px';
                        previewsContainer.appendChild(img);
                    };
                    reader.readAsDataURL(file);
                }
            }
            document.getElementById('pastedImagesBase64').value = ''; // Clear pasted images hidden input
        });
    </script>
</body>
</html>