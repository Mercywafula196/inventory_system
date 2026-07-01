<?php

include 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}


$answer = "";


if(isset($_POST['question'])){


$question = strtolower($_POST['question']);



if(strpos($question,"low stock") !== false ||
   strpos($question,"low") !== false){


$result = mysqli_query($conn,

"SELECT product_name, quantity 
 FROM products 
 WHERE quantity <= reorder_level");


if(mysqli_num_rows($result)>0){


$answer .= "⚠️ These products need attention:<br><br>";


while($row=mysqli_fetch_assoc($result)){


$answer .= 
"📦 ".$row['product_name'].
" - ".$row['quantity']." units<br>";

}


}else{


$answer =
"✅ Great! All products have enough stock.";

}



}




elseif(strpos($question,"total") !== false ||
strpos($question,"how many") !== false){



$result=mysqli_query($conn,

"SELECT COUNT(*) as total FROM products");


$data=mysqli_fetch_assoc($result);


$answer =
"📦 You currently have "
.$data['total'].
" products in your inventory.";


}




elseif(strpos($question,"sales") !== false){



$result=mysqli_query($conn,

"SELECT SUM(quantity_sold) as total FROM sales");


$data=mysqli_fetch_assoc($result);



$answer =
"📈 Total units sold: "
.$data['total'];



}




elseif(strpos($question,"summary") !== false){



$p=mysqli_query($conn,

"SELECT COUNT(*) as total FROM products");


$s=mysqli_query($conn,

"SELECT SUM(quantity_sold) as sold FROM sales");


$product=mysqli_fetch_assoc($p);

$sales=mysqli_fetch_assoc($s);



$answer =
"📊 Inventory Summary<br><br>"
."Products: ".$product['total']."<br>"
."Units Sold: ".$sales['sold'];



}



else{


$answer =
"🤖 Try asking:<br><br>
• Which products are low stock?<br>
• How many products do I have?<br>
• Show sales<br>
• Give inventory summary";


}


}



?>



<!DOCTYPE html>

<html>

<head>

<title>AI Assistant</title>


<style>


body{

font-family:Arial;
background:#eef2f3;
padding:30px;

}



.chat{


width:500px;
margin:auto;
background:white;
border-radius:15px;
padding:20px;
box-shadow:0 5px 15px #ccc;


}



h2{

color:#2c3e50;

}



.bot{


background:#2c3e50;
color:white;
padding:15px;
border-radius:10px;
margin:20px 0;


}



input{


width:75%;
padding:12px;
border-radius:20px;
border:1px solid #ccc;


}



button{


padding:12px 20px;
border:none;
background:#1abc9c;
color:white;
border-radius:20px;


}



</style>


</head>



<body>


<div class="chat">


<h2>🤖 AI Inventory Assistant</h2>



<div class="bot">


<?php

if($answer!=""){

echo $answer;

}else{

echo "Hello 👋 Ask me about your inventory.";

}

?>


</div>




<form method="POST">


<input 
name="question"
placeholder="Ask AI..."
required>


<button>
Send
</button>



</form>



</div>


</body>


</html>