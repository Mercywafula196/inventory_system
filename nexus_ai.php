<?php

include 'config.php';
session_start();


if(!isset($_SESSION['user_id'])){

header("Location: login.php");
exit();

}


$response = "";

$user_question = "";



if(isset($_POST['question'])){


$user_question = $_POST['question'];

$question = strtolower($user_question);





// LOW STOCK

if(
strpos($question,"low") !== false ||
strpos($question,"restock") !== false ||
strpos($question,"reorder") !== false
){


$result=mysqli_query($conn,

"SELECT product_name,quantity,reorder_level
FROM products
WHERE quantity <= reorder_level"

);



if(mysqli_num_rows($result)>0){


$response="⚠️ <b>Products needing attention:</b><br><br>";



while($row=mysqli_fetch_assoc($result)){


$response .=

"📦 <b>".$row['product_name']."</b><br>
Stock: ".$row['quantity']." units<br>
Reorder at: ".$row['reorder_level']." units

<br><br>";

}


}

else{


$response="✅ All products have enough stock.";

}


}




// TOTAL PRODUCTS

elseif(strpos($question,"product") !== false){


$q=mysqli_query($conn,

"SELECT COUNT(*) total FROM products"

);


$data=mysqli_fetch_assoc($q);


$response=

"📦 Nexus currently has <b>"
.$data['total'].
"</b> products.";





}




// SALES

elseif(strpos($question,"sale") !== false){



$q=mysqli_query($conn,

"SELECT SUM(quantity_sold) total FROM sales"

);


$data=mysqli_fetch_assoc($q);



$response=

"📈 Total units sold:

<b>".$data['total']."</b>";



}





// SUMMARY


elseif(
strpos($question,"summary") !== false ||
strpos($question,"report") !== false

){



$p=mysqli_query($conn,

"SELECT COUNT(*) total FROM products"

);


$products=mysqli_fetch_assoc($p)['total'];




$s=mysqli_query($conn,

"SELECT SUM(quantity_sold) total FROM sales"

);


$sales=mysqli_fetch_assoc($s)['total'];



$l=mysqli_query($conn,

"SELECT COUNT(*) total 
FROM products
WHERE quantity <= reorder_level"

);


$low=mysqli_fetch_assoc($l)['total'];




$response=

"🤖 <b>Nexus Inventory Summary</b><br><br>

📦 Products: $products

<br>

📈 Sales: $sales

<br>

⚠ Low Stock: $low";




}




else{


$response=

"🤖 I understand inventory questions.

Try asking:

<br><br>

• Show low stock

<br>
• Total products

<br>
• Sales

<br>
• Give summary";


}




}





?>



<!DOCTYPE html>

<html>


<head>


<title>Nexus AI Assistant</title>


<style>


body{

font-family:Arial;

background:#f0f2f5;

}



.chat{

width:500px;

margin:40px auto;

background:white;

border-radius:15px;

padding:25px;

box-shadow:0 5px 15px #ccc;

}



h2{

color:#2c3e50;

}



.message{


padding:15px;

border-radius:10px;

margin-top:15px;

}



.user{


background:#2c3e50;

color:white;

text-align:right;


}



.bot{


background:#ecf0f1;

color:#333;


}



input{


width:75%;

padding:12px;


}



button{


padding:12px;

background:#1abc9c;

color:white;

border:none;

border-radius:5px;

cursor:pointer;


}



.back{


display:block;

margin-top:20px;

text-decoration:none;

color:#2c3e50;

}


</style>



</head>




<body>




<div class="chat">



<h2>

🤖 Nexus AI Assistant

</h2>



<p>

Ask Nexus about your inventory

</p>




<?php if($user_question!=""): ?>


<div class="message user">

You:

<br>

<?php echo $user_question; ?>


</div>



<div class="message bot">


Nexus:

<br><br>


<?php echo $response; ?>


</div>


<?php endif; ?>






<form method="POST">


<input

type="text"

name="question"

placeholder="Ask Nexus..."

required>


<button>

Send

</button>


</form>





<a class="back" href="dashboard.php">

⬅ Back to Dashboard

</a>



</div>



</body>


</html>