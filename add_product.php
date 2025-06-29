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

    if (empty($images_to_save)) {
        $image_err = "Please upload or paste at least one image.";
    }

    if (empty($name_err) && empty($description_err) && empty($price_err) && empty($stock_err) && empty($image_err) && empty($category_err)) {
        $sql = "INSERT INTO products (name, description, price, stock, sku, category_id) VALUES (?, ?, ?, ?, ?, ?)";

        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("ssdisi", $param_name, $param_description, $param_price, $param_stock, $param_sku, $param_category_id);

            $param_name = $_POST['name'];
            $param_description = $_POST['description'];
            $param_price = $_POST['price'];
            $param_stock = $_POST['stock'];
            $param_sku = $_POST['sku'];
            $param_category_id = $category_id;

            if ($stmt->execute()) {
                $new_product_id = $mysqli->insert_id;

                // Insert images into product_images table
                $sql_insert_image = "INSERT INTO product_images (product_id, image_data) VALUES (?, ?)";
                if ($stmt_insert_image = $mysqli->prepare($sql_insert_image)) {
                    foreach ($images_to_save as $img_data) {
                        $stmt_insert_image->bind_param("is", $new_product_id, $img_data);
                        $stmt_insert_image->execute();
                    }
                    $stmt_insert_image->close();
                }

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
                        <label class="text-white">Images (Upload or Paste)</label>
                        <input type="file" name="images[]" id="imageUpload" class="form-control" multiple>
                        <span class="invalid-feedback"><?php echo $image_err; ?></span>
                        <br>
                        <div class="text-white">Or paste images here:</div>
                        <div id="pasteArea" style="border: 1px dashed #ccc; padding: 20px; min-height: 100px; text-align: center; cursor: pointer;" class="form-control">
                            Paste images here (Ctrl+V)
                        </div>
                        <div id="imagePreviews" style="margin-top: 10px;"></div>
                        <input type="hidden" name="pasted_images_base64" id="pastedImagesBase64">
                    </div>
                    <input type="submit" class="btn btn-primary" value="Submit">
                    <a href="products.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    <?php include 'templates/footer.php'; ?>
    <script>
        let allPastedImages = [];

        function renderPreviews() {
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
                        renderPreviews();
                        document.getElementById('imageUpload').value = ''; // Clear file input if image is pasted
                    };
                    reader.readAsDataURL(blob);
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