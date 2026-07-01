<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";

// RECORD A SALE
if (isset($_POST['add_sale'])) {
    $product_id    = $_POST['product_id'];
    $quantity_sold = $_POST['quantity_sold'];
    $sale_date     = $_POST['sale_date'];

    // CHECK ENOUGH STOCK
    $check = mysqli_query($conn, "SELECT quantity, product_name FROM products WHERE product_id = $product_id");
    $stock = mysqli_fetch_assoc($check);

    if ($quantity_sold > $stock['quantity']) {
        $message = "error|Not enough stock! Only {$stock['quantity']} units available for {$stock['product_name']}.";
    } else {
        // INSERT SALE
        $sql = "INSERT INTO sales (product_id, quantity_sold, sale_date)
                VALUES ('$product_id', '$quantity_sold', '$sale_date')";

        if (mysqli_query($conn, $sql)) {
            // REDUCE STOCK
            mysqli_query($conn, "UPDATE products SET quantity = quantity - $quantity_sold WHERE product_id = $product_id");
            $message = "success|Sale recorded successfully! Stock updated.";
        } else {
            $message = "error|Failed to record sale.";
        }
    }
}

// DELETE SALE
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    // GET SALE DETAILS FIRST TO RESTORE STOCK
    $sale = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM sales WHERE sale_id = $id"));
    if ($sale) {
        mysqli_query($conn, "UPDATE products SET quantity = quantity + {$sale['quantity_sold']} WHERE product_id = {$sale['product_id']}");
        mysqli_query($conn, "DELETE FROM sales WHERE sale_id = $id");
        $message = "success|Sale deleted and stock restored.";
    }
}

// GET ALL PRODUCTS FOR DROPDOWN
$products_list = mysqli_query($conn, "SELECT product_id, product_name, quantity FROM products ORDER BY product_name ASC");

// GET ALL SALES WITH PRODUCT NAME
$sales = mysqli_query($conn, "
    SELECT s.sale_id, p.product_name, p.category, s.quantity_sold, s.sale_date
    FROM sales s
    JOIN products p ON s.product_id = p.product_id
    ORDER BY s.sale_date DESC, s.sale_id DESC
");

// SALES SUMMARY
$r1 = mysqli_query($conn, "SELECT COUNT(*) as t FROM sales");
$total_transactions = mysqli_fetch_assoc($r1)['t'];

$r2 = mysqli_query($conn, "SELECT SUM(quantity_sold) as t FROM sales");
$row2 = mysqli_fetch_assoc($r2);
$total_units = $row2['t'] ? $row2['t'] : 0;

$r3 = mysqli_query($conn, "SELECT SUM(s.quantity_sold * p.price) as t FROM sales s JOIN products p ON s.product_id = p.product_id");
$row3 = mysqli_fetch_assoc($r3);
$total_revenue = $row3['t'] ? $row3['t'] : 0;

$r4 = mysqli_query($conn, "SELECT COUNT(*) as t FROM sales WHERE sale_date = CURDATE()");
$today_sales = mysqli_fetch_assoc($r4)['t'];

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
<title>Sales | Inventory System</title>
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
    margin-top: 30px;
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

/* STAT CARDS */
.cards { display: flex; gap: 18px; flex-wrap: wrap; margin-bottom: 20px; }
.card {
    background: white;
    border-radius: 10px;
    padding: 20px 25px;
    flex: 1;
    min-width: 180px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border-left: 5px solid #ccc;
    transition: 0.2s;
}
.card:hover { transform: translateY(-3px); box-shadow: 0 6px 16px rgba(0,0,0,0.12); }
.card.blue   { border-left-color: #2980b9; }
.card.green  { border-left-color: #27ae60; }
.card.teal   { border-left-color: #1abc9c; }
.card.orange { border-left-color: #f39c12; }
.card .label { font-size: 12px; color: #888; text-transform: uppercase; margin-bottom: 8px; }
.card .value { font-size: 28px; font-weight: bold; color: #2c3e50; }
.card .sub   { font-size: 12px; color: #aaa; margin-top: 4px; }

/* FORM */
.form-box {
    background: white;
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 30px;
}
.form-row {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
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
.form-group select:focus { border-color: #1abc9c; }

.btn {
    padding: 10px 24px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    font-weight: bold;
    display: inline-block;
    text-decoration: none;
}
.btn-teal   { background: #1abc9c; color: white; }
.btn-teal:hover { background: #16a085; }
.btn-delete { background: #e74c3c; color: white; }
.btn-delete:hover { background: #c0392b; }

/* STOCK HINT */
.stock-hint {
    font-size: 12px;
    color: #888;
    margin-top: 5px;
}

/* TABLE */
.table-box {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow-x: auto;
    margin-bottom: 30px;
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

.empty-state {
    text-align: center;
    padding: 30px;
    color: #aaa;
    font-size: 14px;
}

.footer {
    text-align: center;
    padding: 20px;
    font-size: 12px;
    color: #aaa;
    margin-top: 20px;
}
</style>

<script>
function showStock() {
    var select = document.getElementById('product_select');
    var option = select.options[select.selectedIndex];
    var stock  = option.getAttribute('data-stock');
    if (stock) {
        document.getElementById('stock-hint').innerText = 'Available stock: ' + stock + ' units';
    } else {
        document.getElementById('stock-hint').innerText = '';
    }
}
</script>
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
    <a href="products.php">Products</a>
    <a href="sales.php" class="active">Sales</a>
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

    <!-- SUMMARY CARDS -->
    <div class="section-title">Sales Overview</div>
    <div class="cards">
        <div class="card blue">
            <div class="label">Total Transactions</div>
            <div class="value"><?php echo $total_transactions; ?></div>
            <div class="sub">All time</div>
        </div>
        <div class="card teal">
            <div class="label">Total Units Sold</div>
            <div class="value"><?php echo $total_units; ?></div>
            <div class="sub">All time</div>
        </div>
        <div class="card green">
            <div class="label">Total Revenue</div>
            <div class="value">KSh <?php echo number_format($total_revenue, 0); ?></div>
            <div class="sub">All time</div>
        </div>
        <div class="card orange">
            <div class="label">Sales Today</div>
            <div class="value"><?php echo $today_sales; ?></div>
            <div class="sub"><?php echo date('d M Y'); ?></div>
        </div>
    </div>

    <!-- RECORD SALE FORM -->
    <div class="section-title">Record a Sale</div>
    <div class="form-box">
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Select Product</label>
                    <select name="product_id" id="product_select" onchange="showStock()" required>
                        <option value="">-- Select Product --</option>
                        <?php
                        while($p = mysqli_fetch_assoc($products_list)):
                        ?>
                        <option value="<?php echo $p['product_id']; ?>"
                            data-stock="<?php echo $p['quantity']; ?>">
                            <?php echo $p['product_name']; ?> (<?php echo $p['quantity']; ?> in stock)
                        </option>
                        <?php endwhile; ?>
                    </select>
                    <div class="stock-hint" id="stock-hint"></div>
                </div>
                <div class="form-group">
                    <label>Quantity Sold</label>
                    <input type="number" name="quantity_sold" min="1" placeholder="e.g. 5" required>
                </div>
                <div class="form-group">
                    <label>Sale Date</label>
                    <input type="date" name="sale_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group" style="display:flex; align-items:flex-end;">
                    <button type="submit" name="add_sale" class="btn btn-teal">
                        &#x2713; Record Sale
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- SALES HISTORY TABLE -->
    <div class="section-title">Sales History</div>
    <div class="table-box">
        <?php if (mysqli_num_rows($sales) == 0): ?>
            <div class="empty-state">
                &#x1F4CB; No sales recorded yet. Record your first sale above.
            </div>
        <?php else: ?>
        <table>
            <tr>
                <th>#</th>
                <th>Product</th>
                <th>Category</th>
                <th>Quantity Sold</th>
                <th>Sale Date</th>
                <th>Action</th>
            </tr>
            <?php
            $count = 1;
            while($row = mysqli_fetch_assoc($sales)):
            ?>
            <tr>
                <td><?php echo $count++; ?></td>
                <td><strong><?php echo $row['product_name']; ?></strong></td>
                <td><?php echo $row['category']; ?></td>
                <td><?php echo $row['quantity_sold']; ?> units</td>
                <td><?php echo date('d M Y', strtotime($row['sale_date'])); ?></td>
                <td>
                    <a href="sales.php?delete=<?php echo $row['sale_id']; ?>"
                       class="btn btn-delete"
                       style="font-size:12px; padding:5px 12px;"
                       onclick="return confirm('Delete this sale? Stock will be restored.')">
                       Delete
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
        <?php endif; ?>
    </div>

</div>

<div class="footer">AI-Based Inventory Management System &copy; <?php echo date('Y'); ?></div>

</body>
</html>