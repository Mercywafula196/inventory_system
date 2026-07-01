<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// STATS
$r1 = mysqli_query($conn, "SELECT COUNT(*) as t FROM products");
$total_products = mysqli_fetch_assoc($r1)['t'];

$r2 = mysqli_query($conn, "SELECT SUM(quantity_sold) as t FROM sales");
$row2 = mysqli_fetch_assoc($r2);
$total_sales = $row2['t'] ? $row2['t'] : 0;

$r3 = mysqli_query($conn, "SELECT COUNT(*) as t FROM products WHERE quantity <= reorder_level");
$low_stock = mysqli_fetch_assoc($r3)['t'];

$r4 = mysqli_query($conn, "SELECT COUNT(*) as t FROM products WHERE quantity <= (reorder_level * 0.5)");
$critical = mysqli_fetch_assoc($r4)['t'];

$ai_products = mysqli_query($conn, "SELECT * FROM products ORDER BY quantity ASC LIMIT 6");

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<title>Dashboard | Nexus Inventory System</title>


<style>

* { 
margin:0; 
padding:0; 
box-sizing:border-box; 
}


body {

font-family: Arial, sans-serif;
background:#f0f2f5;
color:#333;

}



.header {

background:linear-gradient(135deg,#1a252f,#2c3e50);
color:white;
padding:18px 30px;
display:flex;
align-items:center;
justify-content:space-between;

}


.header h1 {

font-size:20px;

}



.header span {

font-size:13px;
color:#aab;

}



.nav {

background:#2c3e50;
padding:0 30px;
display:flex;
gap:5px;

}



.nav a {

color:#ccc;
text-decoration:none;
padding:12px 16px;
font-size:14px;
display:inline-block;
border-bottom:3px solid transparent;

}



.nav a:hover,
.nav a.active {

color:white;
border-bottom:3px solid #1abc9c;

}



.container {

max-width:1100px;
margin:30px auto;
padding:0 20px;

}



.section-title {

font-size:16px;
font-weight:bold;
color:#2c3e50;
margin-bottom:15px;
margin-top:30px;
padding-bottom:8px;
border-bottom:2px solid #e0e0e0;

}



.cards {

display:flex;
gap:18px;
flex-wrap:wrap;
margin-bottom:20px;

}



.card {

background:white;
border-radius:10px;
padding:20px 25px;
flex:1;
min-width:180px;
box-shadow:0 2px 8px rgba(0,0,0,0.08);
border-left:5px solid #ccc;

}



.card.blue {
border-left-color:#2980b9;
}


.card.green {
border-left-color:#27ae60;
}


.card.orange {
border-left-color:#f39c12;
}


.card.red {
border-left-color:#e74c3c;
}



.card .label {

font-size:12px;
color:#888;
text-transform:uppercase;

}



.card .value {

font-size:32px;
font-weight:bold;
color:#2c3e50;

}



.card .sub {

font-size:12px;
color:#aaa;

}



.table-box {

background:white;
border-radius:10px;
padding:20px;
box-shadow:0 2px 8px rgba(0,0,0,0.08);
overflow-x:auto;

}



table {

width:100%;
border-collapse:collapse;
font-size:14px;

}


th {

background:#2c3e50;
color:white;
padding:11px 14px;
text-align:left;

}


td {

padding:11px 14px;
border-bottom:1px solid #f0f0f0;

}


.badge {

padding:4px 12px;
border-radius:20px;
font-size:12px;
font-weight:bold;

}


.badge.critical {

background:#fde8e8;
color:#c0392b;

}


.badge.low {

background:#fef3cd;
color:#856404;

}


.badge.ok {

background:#d4edda;
color:#155724;

}


.bar-wrap {

background:#eee;
height:8px;
width:100px;

}


.bar-fill {

height:8px;

}


.ai-grid {

display:flex;
gap:18px;
flex-wrap:wrap;

}


.ai-card {

background:white;
padding:18px;
border-radius:10px;
flex:1;
min-width:200px;
box-shadow:0 2px 8px rgba(0,0,0,0.08);
border-top:4px solid #2980b9;

}


.ai-card h3 {

color:#2c3e50;

}


.reorder-alert {

margin-top:8px;
background:#fde8e8;
color:#c0392b;
padding:6px;

}


.stock-ok {

margin-top:8px;
background:#d4edda;
color:#155724;
padding:6px;

}


.footer {

text-align:center;
padding:20px;
color:#aaa;

}


</style>

</head>
<body>


<!-- HEADER -->

<div class="header">

<h1>🔗 Nexus Inventory System</h1>

<span>
Welcome, 
<?php echo $_SESSION['name']; ?> 
&nbsp;|&nbsp;
<?php echo date('D, d M Y'); ?>

</span>

</div>



<!-- NAV -->

<div class="nav">

<a href="dashboard.php" class="active">
Dashboard
</a>

<a href="products.php">
Products
</a>

<a href="sales.php">
Sales
</a>

<a href="alerts.php">
Alerts
</a>

<a href="ai.php">
🤖 Nexus AI Assistant
</a>

<a href="logout.php">
Logout
</a>


</div>





<div class="container">



<!-- OVERVIEW -->


<div class="section-title">
Overview
</div>


<div class="cards">


<div class="card blue">

<div class="label">
Total Products
</div>

<div class="value">
<?php echo $total_products; ?>
</div>

<div class="sub">
Active SKUs
</div>

</div>




<div class="card green">

<div class="label">
Total Units Sold
</div>

<div class="value">
<?php echo $total_sales; ?>
</div>

<div class="sub">
All time
</div>

</div>





<div class="card orange">

<div class="label">
Low Stock Items
</div>

<div class="value">
<?php echo $low_stock; ?>
</div>

<div class="sub">
Need attention
</div>

</div>





<div class="card red">

<div class="label">
Critical Stock
</div>

<div class="value">
<?php echo $critical; ?>
</div>

<div class="sub">
Reorder urgently
</div>

</div>



</div>







<!-- STOCK TABLE -->


<div class="section-title">

Stock Status

</div>



<div class="table-box">


<table>


<tr>

<th>
Product
</th>

<th>
Category
</th>

<th>
Quantity
</th>

<th>
Reorder Level
</th>

<th>
Stock Level
</th>

<th>
Status
</th>

</tr>




<?php


$products = mysqli_query($conn, 
"SELECT * FROM products ORDER BY quantity ASC");


while($row = mysqli_fetch_assoc($products)):


$qty = $row['quantity'];

$reorder = $row['reorder_level'];

$max = $reorder * 4;


$pct = $max > 0 ? min(100, round(($qty/$max)*100)) : 0;



if($qty <= $reorder * 0.5){


$status="critical";

$label="Critical";

$color="#e74c3c";


}

elseif($qty <= $reorder){


$status="low";

$label="Low Stock";

$color="#f39c12";


}

else{


$status="ok";

$label="OK";

$color="#27ae60";


}



?>



<tr>


<td>

<strong>

<?php echo $row['product_name']; ?>

</strong>

</td>


<td>

<?php echo $row['category']; ?>

</td>


<td>

<?php echo $qty; ?> units

</td>



<td>

<?php echo $reorder; ?> units

</td>



<td>


<div class="bar-wrap">


<div class="bar-fill"

style="width:<?php echo $pct;?>%;
background:<?php echo $color;?>">

</div>


</div>


</td>



<td>


<span class="badge <?php echo $status;?>">

<?php echo $label; ?>

</span>


</td>



</tr>



<?php endwhile; ?>


</table>


</div>








<!-- AI FORECAST -->


<div class="section-title">

🤖 Nexus AI Stock Insights (7-Day Forecast)

</div>



<div class="ai-grid">


<?php



while($p = mysqli_fetch_assoc($ai_products)):



$res = mysqli_query($conn,

"SELECT SUM(quantity_sold) as ts,
COUNT(*) as days 
FROM sales 
WHERE product_id=".$p['product_id']);


$s = mysqli_fetch_assoc($res);



$avg = ($s['days'] > 0)

? $s['ts']/$s['days']

:0;



$forecast = round($avg*7,1);



?>




<div class="ai-card">


<h3>

<?php echo $p['product_name']; ?>

</h3>


<p>

📦 Current Stock:

<strong>

<?php echo $p['quantity']; ?>

</strong>

</p>



<p>

📈 Avg Daily Sales:

<strong>

<?php echo round($avg,1); ?>

</strong>


</p>




<p>

🔮 7-Day Forecast:

<strong>

<?php echo $forecast; ?> units

</strong>


</p>





<?php if($p['quantity'] <= $p['reorder_level']): ?>


<div class="reorder-alert">

⚠ REORDER REQUIRED

</div>


<?php else: ?>


<div class="stock-ok">

✅ Stock Level OK

</div>


<?php endif; ?>



</div>



<?php endwhile; ?>



</div>




</div>





<div class="footer">


🔗 Nexus Inventory System © 

<?php echo date('Y'); ?>


</div>



</body>


</html>