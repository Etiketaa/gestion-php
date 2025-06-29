<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["role"]) || $_SESSION["role"] !== 'admin') {
    header("location: login.php");
    exit;
}

require_once "db.php";

$name = "";
$name_err = "";

// Process add/edit form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty(trim($_POST["name"]))) {
        $name_err = "Please enter a category name.";
    } else {
        $name = trim($_POST["name"]);
    }

    if (empty($name_err)) {
        if (isset($_POST["id"]) && !empty($_POST["id"])) {
            // Update category
            $sql = "UPDATE categories SET name=? WHERE id=?";
            if ($stmt = $mysqli->prepare($sql)) {
                $stmt->bind_param("si", $param_name, $param_id);
                $param_name = $name;
                $param_id = $_POST["id"];
                if ($stmt->execute()) {
                    header("location: categories.php");
                    exit();
                } else {
                    echo "Something went wrong. Please try again later.";
                }
                $stmt->close();
            }
        } else {
            // Add new category
            $sql = "INSERT INTO categories (name) VALUES (?)";
            if ($stmt = $mysqli->prepare($sql)) {
                $stmt->bind_param("s", $param_name);
                $param_name = $name;
                if ($stmt->execute()) {
                    header("location: categories.php");
                    exit();
                } else {
                    echo "Something went wrong. Please try again later.";
                }
                $stmt->close();
            }
        }
    }
}

// Delete category
if (isset($_GET["delete_id"]) && !empty(trim($_GET["delete_id"]))) {
    $sql = "DELETE FROM categories WHERE id = ?";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("i", $param_id);
        $param_id = trim($_GET["delete_id"]);
        if ($stmt->execute()) {
            header("location: categories.php");
            exit();
        } else {
            echo "Oops! Something went wrong. Please try again later.";
        }
        $stmt->close();
    }
}

// Fetch categories for display
$sql = "SELECT * FROM categories";
$result = $mysqli->query($sql);

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Categories</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'templates/header.php'; ?>

    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h2>Manage Categories</h2>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-group">
                        <label class="text-white">Category Name</label>
                        <input type="text" name="name" class="form-control <?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $name; ?>">
                        <span class="invalid-feedback"><?php echo $name_err; ?></span>
                    </div>
                    <input type="hidden" name="id" value="<?php echo (isset($_GET["edit_id"]) && !empty(trim($_GET["edit_id"]))) ? trim($_GET["edit_id"]) : ''; ?>">
                    <input type="submit" class="btn btn-primary" value="Save Category">
                </form>
            </div>
        </div>
        <br>
        <div class="row">
            <div class="col-md-12">
                <h3>Existing Categories</h3>
                <table class="table table-bordered table-striped table-dark">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo $row['name']; ?></td>
                                    <td>
                                        <a href="categories.php?edit_id=<?php echo $row['id']; ?>" class="btn btn-primary">Edit</a>
                                        <a href="categories.php?delete_id=<?php echo $row['id']; ?>" class="btn btn-danger">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center">No categories found.</td>
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