<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// CRITICAL STOCK - below 50% of reorder level
$critical = mysqli_query($conn, "SELECT * FROM products 
    WHERE quantity <= (reorder_level * 0.5) 
    ORDER BY quantity ASC");
$critical_count = mysqli_num_rows($critical);

// LOW STOCK - below reorder level but above critical
$low = mysqli_query($conn, "SELECT * FROM products 
    WHERE quantity > (reorder_level * 0.5) 
    AND quantity <= reorder_level 
    ORDER BY quantity ASC");
$low_count = mysqli_num_rows($low);

// OVERSTOCK - above 4x reorder level
$over = mysqli_query($conn, "SELECT * FROM products 
    WHERE quantity > (reorder_level * 4) 
    ORDER BY quantity DESC");
$over_count = mysqli_num_rows($over);

// OK STOCK
$ok = mysqli_query($conn, "SELECT * FROM products 
    WHERE quantity > reorder_level 
    AND quantity <= (reorder_level * 4) 
    ORDER BY product_name ASC");
$ok_count = mysqli_num_rows($ok);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Alerts | Inventory System</title>
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

/* SUMMARY CARDS */
.cards { display: flex; gap: 18px; flex-wrap: wrap; margin-bottom: 30px; }
.card {
    background: white;
    border-radius: 10px;
    padding: 20px 25px;
    flex: 1;
    min-width: 180px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border-left: 5px solid #ccc;
}
.card.red    { border-left-color: #e74c3c; }
.card.orange { border-left-color: #f39c12; }
.card.purple { border-left-color: #8e44ad; }
.card.green  { border-left-color: #27ae60; }
.card .label { font-size: 12px; color: #888; text-transform: uppercase; margin-bottom: 8px; }
.card .value { font-size: 32px; font-weight: bold; color: #2c3e50; }
.card .sub   { font-size: 12px; color: #aaa; margin-top: 4px; }

/* ALERT SECTIONS */
.alert-section {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 25px;
}
.alert-section-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 15px;
    font-weight: bold;
    font-size: 15px;
}
.header-critical { background: #fde8e8; color: #c0392b; }
.header-low      { background: #fef3cd; color: #856404; }
.header-over     { background: #e8d5fb; color: #6c3483; }
.header-ok       { background: #d4edda; color: #155724; }

/* ALERT ITEMS */
.alert-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 10px;
    border: 1px solid #f0f0f0;
    flex-wrap: wrap;
    gap: 10px;
}
.alert-item:last-child { margin-bottom: 0; }

.alert-item.critical { border-left: 4px solid #e74c3c; background: #fff9f9; }
.alert-item.low      { border-left: 4px solid #f39c12; background: #fffdf5; }
.alert-item.over     { border-left: 4px solid #8e44ad; background: #fdf5ff; }
.alert-item.ok       { border-left: 4px solid #27ae60; background: #f5fff8; }

.alert-info h3 { font-size: 14px; color: #2c3e50; margin-bottom: 4px; }
.alert-info p  { font-size: 12px; color: #777; }

.alert-right { text-align: right; }
.alert-right .qty {
    font-size: 22px;
    font-weight: bold;
    color: #2c3e50;
}
.alert-right .qty-label { font-size: 11px; color: #aaa; }

.badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    display: inline-block;
    margin-top: 5px;
}
.badge.critical  { background: #fde8e8; color: #c0392b; }
.badge.low       { background: #fef3cd; color: #856404; }
.badge.ok        { background: #d4edda; color: #155724; }
.badge.overstock { background: #e8d5fb; color: #6c3483; }

/* SUGGESTED ACTION */
.action-tag {
    font-size: 11px;
    padding: 3px 10px;
    border-radius: 20px;
    display: inline-block;
    margin-top: 6px;
    font-weight: bold;
}
.action-tag.reorder  { background: #fde8e8; color: #c0392b; }
.action-tag.watch    { background: #fef3cd; color: #856404; }
.action-tag.discount { background: #e8d5fb; color: #6c3483; }
.action-tag.good     { background: #d4edda; color: #155724; }

.empty-state {
    text-align: center;
    padding: 20px;
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
    <a href="sales.php">Sales</a>
    <a href="alerts.php" class="active">Alerts</a>
    <a href="ai.php">AI Forecast</a>
    <a href="logout.php">Logout</a>
</div>

<div class="container">

    <!-- SUMMARY CARDS -->
    <div class="section-title">Alerts Overview</div>
    <div class="cards">
        <div class="card red">
            <div class="label">Critical Stock</div>
            <div class="value"><?php echo $critical_count; ?></div>
            <div class="sub">Reorder immediately</div>
        </div>
        <div class="card orange">
            <div class="label">Low Stock</div>
            <div class="value"><?php echo $low_count; ?></div>
            <div class="sub">Below reorder level</div>
        </div>
        <div class="card purple">
            <div class="label">Overstock</div>
            <div class="value"><?php echo $over_count; ?></div>
            <div class="sub">Excess inventory</div>
        </div>
        <div class="card green">
            <div class="label">Stock OK</div>
            <div class="value"><?php echo $ok_count; ?></div>
            <div class="sub">Healthy levels</div>
        </div>
    </div>

    <!-- CRITICAL ALERTS -->
    <div class="alert-section">
        <div class="alert-section-header header-critical">
            &#x1F6A8; Critical Stock — Reorder Immediately (<?php echo $critical_count; ?> items)
        </div>
        <?php if ($critical_count == 0): ?>
            <div class="empty-state">&#x2705; No critical stock items</div>
        <?php else: ?>
            <?php while($row = mysqli_fetch_assoc($critical)): 
                $needed = $row['reorder_level'] * 3 - $row['quantity'];
            ?>
            <div class="alert-item critical">
                <div class="alert-info">
                    <h3><?php echo $row['product_name']; ?></h3>
                    <p>Category: <?php echo $row['category']; ?> &nbsp;|&nbsp; Reorder Level: <?php echo $row['reorder_level']; ?> units</p>
                    <span class="badge critical">Critical</span>
                    <span class="action-tag reorder">&#x26A0; Suggested order: <?php echo $needed; ?> units</span>
                </div>
                <div class="alert-right">
                    <div class="qty"><?php echo $row['quantity']; ?></div>
                    <div class="qty-label">units left</div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

    <!-- LOW STOCK ALERTS -->
    <div class="alert-section">
        <div class="alert-section-header header-low">
            &#x26A0; Low Stock — Monitor Closely (<?php echo $low_count; ?> items)
        </div>
        <?php if ($low_count == 0): ?>
            <div class="empty-state">&#x2705; No low stock items</div>
        <?php else: ?>
            <?php while($row = mysqli_fetch_assoc($low)):
                $needed = $row['reorder_level'] * 3 - $row['quantity'];
            ?>
            <div class="alert-item low">
                <div class="alert-info">
                    <h3><?php echo $row['product_name']; ?></h3>
                    <p>Category: <?php echo $row['category']; ?> &nbsp;|&nbsp; Reorder Level: <?php echo $row['reorder_level']; ?> units</p>
                    <span class="badge low">Low Stock</span>
                    <span class="action-tag watch">&#x1F440; Plan reorder: <?php echo $needed; ?> units</span>
                </div>
                <div class="alert-right">
                    <div class="qty"><?php echo $row['quantity']; ?></div>
                    <div class="qty-label">units left</div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

    <!-- OVERSTOCK ALERTS -->
    <div class="alert-section">
        <div class="alert-section-header header-over">
            &#x1F4E6; Overstock — Excess Inventory (<?php echo $over_count; ?> items)
        </div>
        <?php if ($over_count == 0): ?>
            <div class="empty-state">&#x2705; No overstock items</div>
        <?php else: ?>
            <?php while($row = mysqli_fetch_assoc($over)):
                $excess = $row['quantity'] - ($row['reorder_level'] * 4);
            ?>
            <div class="alert-item over">
                <div class="alert-info">
                    <h3><?php echo $row['product_name']; ?></h3>
                    <p>Category: <?php echo $row['category']; ?> &nbsp;|&nbsp; Excess: <?php echo $excess; ?> units above maximum</p>
                    <span class="badge overstock">Overstock</span>
                    <span class="action-tag discount">&#x1F3F7; Consider discount or promotion</span>
                </div>
                <div class="alert-right">
                    <div class="qty"><?php echo $row['quantity']; ?></div>
                    <div class="qty-label">units in stock</div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

    <!-- OK STOCK -->
    <div class="alert-section">
        <div class="alert-section-header header-ok">
            &#x2705; Healthy Stock Levels (<?php echo $ok_count; ?> items)
        </div>
        <?php if ($ok_count == 0): ?>
            <div class="empty-state">No items at healthy stock levels</div>
        <?php else: ?>
            <?php while($row = mysqli_fetch_assoc($ok)): ?>
            <div class="alert-item ok">
                <div class="alert-info">
                    <h3><?php echo $row['product_name']; ?></h3>
                    <p>Category: <?php echo $row['category']; ?> &nbsp;|&nbsp; Reorder Level: <?php echo $row['reorder_level']; ?> units</p>
                    <span class="badge ok">OK</span>
                    <span class="action-tag good">&#x1F44D; No action needed</span>
                </div>
                <div class="alert-right">
                    <div class="qty"><?php echo $row['quantity']; ?></div>
                    <div class="qty-label">units in stock</div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

</div>

<div class="footer">AI-Based Inventory Management System &copy; <?php echo date('Y'); ?></div>

</body>
</html>