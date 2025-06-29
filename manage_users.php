<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["role"]) || $_SESSION["role"] !== 'admin') {
    header("location: login.php");
    exit;
}

require_once "db.php";

// Handle user actions (approve, reject, change role, delete)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["action"]) && isset($_POST["user_id"])) {
        $user_id = $_POST["user_id"];
        $action = $_POST["action"];

        if ($action == "approve") {
            $sql = "UPDATE users SET status = 'approved' WHERE id = ?";
        } elseif ($action == "reject") {
            $sql = "UPDATE users SET status = 'rejected' WHERE id = ?";
        } elseif ($action == "set_admin") {
            $sql = "UPDATE users SET role = 'admin', status = 'approved' WHERE id = ?";
        } elseif ($action == "set_user") {
            $sql = "UPDATE users SET role = 'user', status = 'approved' WHERE id = ?";
        } elseif ($action == "delete") {
            $sql = "DELETE FROM users WHERE id = ?";
        }

        if (isset($sql) && $stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// Fetch all users
$users = [];
$sql_users = "SELECT id, username, first_name, last_name, email, phone_number, role, status FROM users";
if ($result_users = $mysqli->query($sql_users)) {
    while ($row = $result_users->fetch_assoc()) {
        $users[] = $row;
    }
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-dark">
    <?php include 'templates/header.php'; ?>

    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h2 class="text-white">Manage Users</h2>
                <table class="table table-bordered table-striped table-dark">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo $user['username']; ?></td>
                                    <td><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></td>
                                    <td><?php echo $user['email']; ?></td>
                                    <td><?php echo $user['phone_number']; ?></td>
                                    <td><?php echo $user['role']; ?></td>
                                    <td><?php echo $user['status']; ?></td>
                                    <td>
                                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" style="display:inline-block;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <?php if ($user['status'] == 'pending'): ?>
                                                <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">Approve</button>
                                                <button type="submit" name="action" value="reject" class="btn btn-warning btn-sm">Reject</button>
                                            <?php endif; ?>
                                            <?php if ($user['role'] == 'user' && $user['status'] == 'approved'): ?>
                                                <button type="submit" name="action" value="set_admin" class="btn btn-info btn-sm">Make Admin</button>
                                            <?php elseif ($user['role'] == 'admin' && $user['status'] == 'approved'): ?>
                                                <button type="submit" name="action" value="set_user" class="btn btn-secondary btn-sm">Make User</button>
                                            <?php endif; ?>
                                            <button type="submit" name="action" value="delete" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">No users found.</td>
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