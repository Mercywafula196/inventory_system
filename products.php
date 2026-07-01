<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";

// ADD PRODUCT
if (isset($_POST['add_product'])) {
    $name     = $_POST['product_name'];
    $category = $_POST['category'];
    $price    = $_POST['price'];
    $quantity = $_POST['quantity'];
    $reorder  = $_POST['reorder_level'];

    $sql = "INSERT INTO products (product_name, category, price, quantity, reorder_level)
            VALUES ('$name', '$category', '$price', '$quantity', '$reorder')";

    if (mysqli_query($conn, $sql)) {
        $message = "success|Product added successfully!";
    } else {
        $message = "error|Failed to add product.";
    }
}

// UPDATE PRODUCT
if (isset($_POST['edit_product'])) {
    $id       = $_POST['product_id'];
    $name     = $_POST['product_name'];
    $category = $_POST['category'];
    $price    = $_POST['price'];
    $quantity = $_POST['quantity'];
    $reorder  = $_POST['reorder_level'];

    $sql = "UPDATE products SET product_name='$name', category='$category',
            price='$price', quantity='$quantity', reorder_level='$reorder'
            WHERE product_id='$id'";

    if (mysqli_query($conn, $sql)) {
        $message = "success|Product updated successfully!";
    } else {
        $message = "error|Failed to update product.";
    }
}

// DELETE PRODUCT
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    mysqli_query($conn, "DELETE FROM products WHERE product_id=$id");
    $message = "success|Product deleted.";
}

// GET PRODUCT TO EDIT
$edit_product = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $result = mysqli_query($conn, "SELECT * FROM products WHERE product_id=$id");
    $edit_product = mysqli_fetch_assoc($result);
}

// GET ALL PRODUCTS
$search = isset($_GET['search']) ? $_GET['search'] : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';

$sql = "SELECT * FROM products WHERE 1=1";
if ($search != '') {
    $sql .= " AND product_name LIKE '%$search%'";
}
$sql .= " ORDER BY product_id DESC";
$products = mysqli_query($conn, $sql);

// SPLIT MESSAGE
$msg_type = $msg_text = "";
if ($message != "") {
    $parts    = explode("|", $message);
    $msg_type = $parts[0];
    $msg_text = $parts[1];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Products | Inventory System</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }

body {
    font-family: Arial, sans-serif;
    background: #f0f2f5;
    color: #333;
}

.header {
    background: linear-gradient(135deg, #1a252f, #2c3e50);
    color: white;
    padding: 18px 30px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.header h1 { font-size: 20px; }
.header span { font-size: 13px; color: #aab; }

.nav {
    background: #2c3e50;
    padding: 0 30px;
    display: flex;
    gap: 5px;
}
.nav a {
    color: #ccc;
    text-decoration: none;
    padding: 12px 16px;
    font-size: 14px;
    display: inline-block;
    border-bottom: 3px solid transparent;
}
.nav a:hover, .nav a.active {
    color: white;
    border-bottom: 3px solid #1abc9c;
}

.container { max-width: 1100px; margin: 30px auto; padding: 0 20px; }

.section-title {
    font-size: 16px;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 15px;
    padding-bottom: 8px;
    border-bottom: 2px solid #e0e0e0;
}

/* ALERT MESSAGES */
.alert {
    padding: 12px 16px;
    border-radius: 6px;
    margin-bottom: 20px;
    font-size: 14px;
    font-weight: bold;
}
.alert.success { background: #d4edda; color: #155724; border-left: 4px solid #27ae60; }
.alert.error   { background: #fde8e8; color: #c0392b; border-left: 4px solid #e74c3c; }

/* FORM BOX */
.form-box {
    background: white;
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 30px;
}
.form-box h2 {
    font-size: 15px;
    color: #2c3e50;
    margin-bottom: 18px;
}
.form-row {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    margin-bottom: 15px;
}
.form-group {
    flex: 1;
    min-width: 160px;
}
.form-group label {
    display: block;
    font-size: 12px;
    color: #666;
    margin-bottom: 5px;
    text-transform: uppercase;
}
.form-group input,
.form-group select {
    width: 100%;
    padding: 9px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    outline: none;
}
.form-group input:focus,
.form-group select:focus {
    border-color: #2c3e50;
}
.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    font-weight: bold;
    text-decoration: none;
    display: inline-block;
}
.btn-primary { background: #2c3e50; color: white; }
.btn-primary:hover { background: #1a252f; }
.btn-success { background: #27ae60; color: white; }
.btn-success:hover { background: #1e8449; }
.btn-cancel  { background: #bbb; color: white; margin-left: 10px; }
.btn-cancel:hover { background: #999; }

/* SEARCH BAR */
.search-bar {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
    align-items: center;
}
.search-bar input {
    padding: 9px 14px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    width: 280px;
    outline: none;
}
.search-bar input:focus { border-color: #2c3e50; }

/* TABLE */
.table-box {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow-x: auto;
}

table { width: 100%; border-collapse: collapse; font-size: 14px; }
th {
    background: #2c3e50;
    color: white;
    padding: 11px 14px;
    text-align: left;
    font-size: 13px;
}
td { padding: 11px 14px; border-bottom: 1px solid #f0f0f0; }
tr:last-child td { border-bottom: none; }
tr:hover td { background: #fafafa; }

.badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    display: inline-block;
}
.badge.critical  { background: #fde8e8; color: #c0392b; }
.badge.low       { background: #fef3cd; color: #856404; }
.badge.ok        { background: #d4edda; color: #155724; }
.badge.overstock { background: #e8d5fb; color: #6c3483; }

.action-btns a {
    padding: 5px 12px;
    border-radius: 5px;
    font-size: 12px;
    font-weight: bold;
    text-decoration: none;
    display: inline-block;
    margin-right: 5px;
}
.btn-edit   { background: #f0ad4e; color: white; }
.btn-edit:hover { background: #e09b3d; }
.btn-delete { background: #e74c3c; color: white; }
.btn-delete:hover { background: #c0392b; }

.footer {
    text-align: center;
    padding: 20px;
    font-size: 12px;
    color: #aaa;
    margin-top: 20px;
}
</style>
</head>
<body>

<!-- HEADER -->
<div class="header">
    <h1>&#x1F6D2; AI Inventory Management System</h1>
    <span>Welcome, <?php echo $_SESSION['name']; ?> &nbsp;|&nbsp; <?php echo date('D, d M Y'); ?></span>
</div>

<!-- NAV -->
<div class="nav">
    <a href="dashboard.php">Dashboard</a>
    <a href="products.php" class="active">Products</a>
    <a href="sales.php">Sales</a>
    <a href="alerts.php">Alerts</a>
    <a href="ai.php">AI Forecast</a>
    <a href="logout.php">Logout</a>
</div>

<div class="container">

    <!-- MESSAGE -->
    <?php if ($msg_text != ""): ?>
    <div class="alert <?php echo $msg_type; ?>">
        <?php echo $msg_text; ?>
    </div>
    <?php endif; ?>

    <!-- ADD / EDIT FORM -->
    <div class="section-title">
        <?php echo $edit_product ? "Edit Product" : "Add New Product"; ?>
    </div>

    <div class="form-box">
        <form method="POST">

            <?php if ($edit_product): ?>
                <input type="hidden" name="product_id" value="<?php echo $edit_product['product_id']; ?>">
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group">
                    <label>Product Name</label>
                    <input type="text" name="product_name" required
                        value="<?php echo $edit_product ? $edit_product['product_name'] : ''; ?>"
                        placeholder="e.g. Whole Milk 2L">
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category" required>
                        <option value="">-- Select Category --</option>
                        <?php
                        $cats = ['Dairy','Bakery','Produce','Grains','Meat','Beverages','Pantry'];
                        foreach($cats as $cat):
                            $sel = ($edit_product && $edit_product['category'] == $cat) ? 'selected' : '';
                        ?>
                        <option value="<?php echo $cat; ?>" <?php echo $sel; ?>><?php echo $cat; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Price (KSh)</label>
                    <input type="number" step="0.01" name="price" required
                        value="<?php echo $edit_product ? $edit_product['price'] : ''; ?>"
                        placeholder="e.g. 120.00">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Quantity (units)</label>
                    <input type="number" name="quantity" required
                        value="<?php echo $edit_product ? $edit_product['quantity'] : ''; ?>"
                        placeholder="e.g. 100">
                </div>
                <div class="form-group">
                    <label>Reorder Level</label>
                    <input type="number" name="reorder_level" required
                        value="<?php echo $edit_product ? $edit_product['reorder_level'] : ''; ?>"
                        placeholder="e.g. 20">
                </div>
                <div class="form-group" style="display:flex; align-items:flex-end;">
                    <?php if ($edit_product): ?>
                        <button type="submit" name="edit_product" class="btn btn-success">Update Product</button>
                        <a href="products.php" class="btn btn-cancel">Cancel</a>
                    <?php else: ?>
                        <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
                    <?php endif; ?>
                </div>
            </div>

        </form>
    </div>

    <!-- SEARCH -->
    <div class="section-title">All Products</div>
    <form method="GET" class="search-bar">
        <input type="text" name="search" placeholder="Search product name..."
            value="<?php echo $search; ?>">
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if ($search): ?>
            <a href="products.php" class="btn btn-cancel">Clear</a>
        <?php endif; ?>
    </form>

    <!-- PRODUCTS TABLE -->
    <div class="table-box">
        <table>
            <tr>
                <th>#</th>
                <th>Product Name</th>
                <th>Category</th>
                <th>Price (KSh)</th>
                <th>Quantity</th>
                <th>Reorder Level</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            <?php
            $count = 1;
            while($row = mysqli_fetch_assoc($products)):
                $qty     = $row['quantity'];
                $reorder = $row['reorder_level'];
                $max     = $reorder * 4;

                if ($qty <= $reorder * 0.5) {
                    $status = 'critical'; $label = 'Critical';
                } elseif ($qty <= $reorder) {
                    $status = 'low'; $label = 'Low Stock';
                } elseif ($qty > $max) {
                    $status = 'overstock'; $label = 'Overstock';
                } else {
                    $status = 'ok'; $label = 'OK';
                }
            ?>
            <tr>
                <td><?php echo $count++; ?></td>
                <td><strong><?php echo $row['product_name']; ?></strong></td>
                <td><?php echo $row['category']; ?></td>
                <td>KSh <?php echo number_format($row['price'], 2); ?></td>
                <td><?php echo $qty; ?> units</td>
                <td><?php echo $reorder; ?> units</td>
                <td><span class="badge <?php echo $status; ?>"><?php echo $label; ?></span></td>
                <td class="action-btns">
                    <a href="products.php?edit=<?php echo $row['product_id']; ?>" class="btn-edit">Edit</a>
                    <a href="products.php?delete=<?php echo $row['product_id']; ?>"
                       class="btn-delete"
                       onclick="return confirm('Delete this product?')">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>

</div>

<div class="footer">AI-Based Inventory Management System &copy; <?php echo date('Y'); ?></div>

</body>
</html>