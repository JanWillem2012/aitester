<?php
session_start();

// Database connection settings
$db_host = 'localhost';
$db_username = 'root';
$db_database = 'ai_chatbot';

// Connect to the database
$conn = new mysqli($db_host, $db_username, '', $db_database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Define constants for chatbot settings
define('CHATBOT_MODEL', 'LLaMA-3:8B');
define('CHATBOT_TEMPERATURE', 0.5);
define('CHATBOT_MAX_RESPONSES', 10);

// Define function to generate response from chatbot
function generateResponse($input) {
    // Send request to Ollama API for generating response
    $data = array('prompt' => $input, 'model_name' => CHATBOT_MODEL, 'temperature' => CHATBOT_TEMPERATURE);
    $jsonData = json_encode($data);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/generate');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

    $response = curl_exec($ch);
    curl_close($ch);

    // Parse response from Ollama API
    $data = json_decode($response, true);

    return $data['response'];
}

// Define function to handle user input
function handleUserInput() {
    global $conn;

    // Get user input
    $input = $_POST['message'];

    // Validate user input
    if (strlen($input) < 3) {
        echo 'Error: Please enter a valid message.';
        return;
    }

    // Generate response from chatbot
    $response = generateResponse($input);

    // Store response in database
    $query = "INSERT INTO responses (user_input, response) VALUES ('$input', '$response')";
    mysqli_query($conn, $query);

    // Output response to user
    echo '<div class="message">' . $response . '</div>';
}

// Define function to handle new messages from chatbot
function handleNewMessages() {
    global $conn;

    // Get new messages from database
    $query = "SELECT * FROM responses WHERE viewed=0";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            echo '<div class="message">' . $row['response'] . '</div>';
        }
    } else {
        echo 'No new messages available.';
    }
}

// Register user account
if (isset($_POST['register'])) {
    // Check for duplicate username or email
    $query = "SELECT * FROM users WHERE username='".$_POST['username']."' OR email='".$_POST['email']."'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        echo 'Error: Username or email already taken.';
    } else {
        // Insert user into database
        $query = "INSERT INTO users (username, email, password) VALUES ('".$_POST['username']."', '".$_POST['email']."', '".md5($_POST['password'])."')";
        mysqli_query($conn, $query);

        // Login user
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $_POST['username'];
    }
}

// Login user account
if (isset($_POST['login'])) {
    // Check for valid username and password
    $query = "SELECT * FROM users WHERE username='".$_POST['username']."' AND password='".md5($_POST['password'])."'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        // Login user
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $_POST['username'];
    } else {
        echo 'Error: Invalid username or password.';
    }
}

// Check if user is logged in
if (isset($_SESSION['logged_in'])) {
    // Display chatbot interface
    echo '<div class="chat-container">';
    echo '<ul id="messages"></ul>';
    echo '<form action="" method="post">';
    echo '<input type="text" name="message" placeholder="Type your message...">';
    echo '<button type="submit">Send</button>';
    echo '</form>';
} else {
    // Display login and registration forms
    echo '<h1>Login or Register</h1>';
    echo '<form action="" method="post" id="login-form">';
    echo '<label for="username">Username:</label><input type="text" name="username"><br>';
    echo '<label for="password">Password:</label><input type="password" name="password"><br>';
    echo '<button type="submit" name="login">Login</button>';
    echo '</form>';
    echo '<form action="" method="post" id="register-form">';
    echo '<label for="username">Username:</label><input type="text" name="username"><br>';
    echo '<label for="email">Email:</label><input type="email" name="email"><br>';
    echo '<label for="password">Password:</label><input type="password" name="password"><br>';
    echo '<button type="submit" name="register">Register</button>';
    echo '</form>';
}

// Close database connection
$conn->close();
?>

<!-- HTML Structure -->
<!DOCTYPE html>
<html>
<head>
    <title>Lamp Chat</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Chatbot interface -->
    <div class="chat-container"></div>

    <!-- Login and registration forms -->
    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" id="login-form">
        <label for="username">Username:</label><input type="text" name="username"><br>
        <label for="password">Password:</label><input type="password" name="password"><br>
        <button type="submit" name="login">Login</button>
    </form>

    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" id="register-form">
        <label for="username">Username:</label><input type="text" name="username"><br>
        <label for="email">Email:</label><input type="email" name="email"><br>
        <label for="password">Password:</label><input type="password" name="password"><br>
        <button type="submit" name="register">Register</button>
    </form>

    <!-- JavaScript code -->
    <script src="script.js"></script>
</body>
</html>
