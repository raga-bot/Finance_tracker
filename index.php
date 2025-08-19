<?php
session_start();
include 'db.php';

// --- Handle Logout ---
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// --- Handle Signup ---
if (isset($_POST['signup'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
}

// --- Handle Login ---
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $hashedPassword);
    $stmt->fetch();
    if ($stmt->num_rows > 0 && password_verify($password, $hashedPassword)) {
        $_SESSION['user_id'] = $id;
        $_SESSION['username'] = $username;
    }
}

// --- Handle Add Transaction ---
if (isset($_POST['add_transaction']) && isset($_SESSION['user_id'])) {
    $amount = $_POST['amount'];
    $category = $_POST['category'];
    $date = $_POST['date'];
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("INSERT INTO transactions (user_id, amount, category, date) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("idss", $user_id, $amount, $category, $date);
    $stmt->execute();
}

// --- Handle Delete Transaction ---
if (isset($_GET['delete']) && isset($_SESSION['user_id'])) {
    $transaction_id = $_GET['delete'];
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("DELETE FROM transactions WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $transaction_id, $user_id);
    $stmt->execute();
}

// --- Handle Edit Transaction (Show pre-filled form) ---
$edit_transaction = null;
if (isset($_GET['edit']) && isset($_SESSION['user_id'])) {
    $transaction_id = $_GET['edit'];
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT * FROM transactions WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $transaction_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_transaction = $result->fetch_assoc();
}

// --- Handle Update Transaction ---
if (isset($_POST['update_transaction']) && isset($_SESSION['user_id'])) {
    $transaction_id = $_POST['transaction_id'];
    $amount = $_POST['amount'];
    $category = $_POST['category'];
    $date = $_POST['date'];
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("UPDATE transactions SET amount=?, category=?, date=? WHERE id=? AND user_id=?");
    $stmt->bind_param("dssii", $amount, $category, $date, $transaction_id, $user_id);
    $stmt->execute();
    header("Location: index.php"); // avoid resubmission
}

// --- Fetch Transactions & Summary ---
$transactions = [];
$total_amount = 0;
$total_count = 0;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $res = $conn->query("SELECT * FROM transactions WHERE user_id=$user_id ORDER BY date DESC");
    while ($row = $res->fetch_assoc()) {
        $transactions[] = $row;
        $total_amount += $row['amount'];
        $total_count++;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Personal Finance Tracker</title>
    <style>
        body { font-family: Arial; margin:20px; background:#f9f9f9;}
        .container { max-width: 750px; margin:auto; background:#fff; padding:20px; border-radius:10px; box-shadow:0 0 10px rgba(0,0,0,0.1);}
        h2 { color:#333; }
        form { margin-bottom:15px; }
        input, select { padding:8px; margin:5px 0; width:100%; }
        button { padding:10px; background:#007bff; color:#fff; border:none; border-radius:5px; cursor:pointer; }
        button:hover { background:#0056b3; }
        table { width:100%; border-collapse:collapse; margin-top:20px; }
        th, td { padding:10px; border:1px solid #ddd; text-align:center; }
        a { color:red; text-decoration:none; }
        .edit-form { background:#eef; padding:15px; margin-bottom:20px; border-radius:5px; }
        .summary { background:#dff0d8; padding:15px; margin-bottom:20px; border-radius:5px; }
    </style>
</head>
<body>
<div class="container">
<h2>💰 Personal Finance Tracker</h2>

<?php if (!isset($_SESSION['user_id'])): ?>
    <!-- Login Form -->
    <h3>Login</h3>
    <form method="POST">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" name="login">Login</button>
    </form>

    <!-- Signup Form -->
    <h3>Signup</h3>
    <form method="POST">
        <input type="text" name="username" placeholder="Choose Username" required>
        <input type="password" name="password" placeholder="Choose Password" required>
        <button type="submit" name="signup">Signup</button>
    </form>

<?php else: ?>
    <p>Welcome, <b><?php echo $_SESSION['username']; ?></b> | <a href="?logout=1">Logout</a></p>

    <!-- Summary -->
    <div class="summary">
        <strong>Total Transactions:</strong> <?php echo $total_count; ?><br>
        <strong>Total Amount:</strong> ₹<?php echo number_format($total_amount,2); ?><br>
        <strong>Balance:</strong> ₹<?php echo number_format($total_amount,2); ?>
    </div>

    <!-- Edit Transaction Form -->
    <?php if ($edit_transaction): ?>
        <div class="edit-form">
            <h3>Edit Transaction ID <?php echo $edit_transaction['id']; ?></h3>
            <form method="POST">
                <input type="hidden" name="transaction_id" value="<?php echo $edit_transaction['id']; ?>">
                <input type="number" step="0.01" name="amount" value="<?php echo $edit_transaction['amount']; ?>" required>
                <input type="text" name="category" value="<?php echo $edit_transaction['category']; ?>" required>
                <input type="date" name="date" value="<?php echo $edit_transaction['date']; ?>" required>
                <button type="submit" name="update_transaction">Update Transaction</button>
            </form>
        </div>
    <?php endif; ?>

    <!-- Add Transaction Form -->
    <h3>Add Transaction</h3>
    <form method="POST">
        <input type="number" step="0.01" name="amount" placeholder="Amount" required>
        <input type="text" name="category" placeholder="Category" required>
        <input type="date" name="date" required>
        <button type="submit" name="add_transaction">Add Transaction</button>
    </form>

    <!-- Transactions Table -->
    <h3>Your Transactions</h3>
    <table>
        <tr>
            <th>ID</th><th>Amount</th><th>Category</th><th>Date</th><th>Actions</th>
        </tr>
        <?php foreach($transactions as $row): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['amount']; ?></td>
                <td><?php echo $row['category']; ?></td>
                <td><?php echo $row['date']; ?></td>
                <td>
                    <a href='?edit=<?php echo $row['id']; ?>'>Edit</a> | 
                    <a href='?delete=<?php echo $row['id']; ?>' onclick='return confirm("Are you sure?")'>Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
</div>
</body>
</html>



