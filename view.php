<?php
// Step 1: Connect to the database
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "try";

$conn = new mysqli($host, $user, $pass, $dbname);

// Check for connection error
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Step 2: Fetch user data
$id = 1; // You can also get this from URL or form
$sql = "SELECT MCID FROM egm WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$MCID = "";

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $MCID = $row['mcid'];
}

$stmt->close();
$conn->close();
?>

<!-- Step 3: Show the username in a textbox -->
<!DOCTYPE html>
<html>
<head>
    <title>Prefill Textbox</title>
</head>
<body>


</body>
</html>
