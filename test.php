<?php
// Load intents from JSON file
$intentsFile = 'intents.json';
if (!file_exists($intentsFile)) {
    die("Error: Cannot find intents.json file.");
}
$intentsData = json_decode(file_get_contents($intentsFile), true);
$intents = $intentsData['intents'];

// Convert all patterns to lowercase
foreach ($intents as &$intent) {
    foreach ($intent['patterns'] as &$pattern) {
        $pattern = strtolower($pattern); // Convert pattern to lowercase
    }
}

// Function to remove suffixes
function removeSuffixes($inputText) {
    $suffixes = ["ын", "ийн", "д", "т", "г", "аар", "ээр", "тай", "той", "оор", "өөс", "өөсө", "уу", "дээр", "аас", "ээс"];
    foreach ($suffixes as $suffix) {
        if (substr($inputText, -strlen($suffix)) === $suffix) {
            $inputText = substr($inputText, 0, -strlen($suffix));
            break; // Remove only one suffix at a time
        }
    }
    return $inputText;
}

// Function to preprocess user input
function preprocessInput($userInput) {
    // Tokenize and convert user input to lowercase
    $words = preg_split('/\s+/', strtolower(trim($userInput)));
    $processedWords = [];
    foreach ($words as $word) {
        $processedWords[] = removeSuffixes($word); // Remove suffixes
    }
    return $processedWords;
}

// Function to predict intent
function predictIntent($userInput) {
    global $intents;
    $words = preprocessInput($userInput);

    foreach ($intents as $intent) {
        foreach ($intent['patterns'] as $pattern) {
            // Tokenize pattern and compare with user input
            $patternWords = preg_split('/\s+/', $pattern); 
            if (count(array_intersect($words, $patternWords)) === count($patternWords)) {
                return $intent['tag'];
            }
        }
    }
    return "default";
}

// Function to generate a response
function generateResponse($userInput) {
    global $intents;
    $tag = predictIntent($userInput);

    foreach ($intents as $intent) {
        if ($intent['tag'] === $tag) {
            $responses = $intent['responses'];
            return $responses[array_rand($responses)];
        }
    }
    return "Sorry, I don't understand.";
}

// Start or continue the conversation
session_start(); // Start a session to persist the conversation

// Initialize conversation history if it doesn't exist
if (!isset($_SESSION['conversation'])) {
    $_SESSION['conversation'] = [];
}

// Handle user input from the form
$response = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['message'])) {
        $userInput = trim($_POST['message']);
        if (!empty($userInput)) {
            $response = generateResponse($userInput);
            
            // Add user and bot messages to the conversation history
            $_SESSION['conversation'][] = ['user' => $userInput, 'bot' => $response];
        }
    }
    
    // Check if the "Delete History" button is clicked
    if (isset($_POST['delete_history'])) {
        // Delete conversation history
        unset($_SESSION['conversation']);
    }
}

// Display the conversation history
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chatbot</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            color: #333;
            margin: 0;
            padding: 0;
        }
        h2 {
            text-align: center;
            color: #2a5d84;
        }
        .chat-container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .chat-box {
            border: 1px solid #ddd;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
            max-height: 400px;
            overflow-y: auto;
        }
        .chat-box div {
            margin-bottom: 10px;
        }
        .chat-box div strong {
            color: #2a5d84;
        }
        .input-container {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        input[type="text"] {
            width: 80%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        button {
            padding: 10px 20px;
            border: none;
            background-color: #2a5d84;
            color: white;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #1f4577;
        }
        .history-button {
            display: block;
            margin: 20px auto;
            background-color: #e74c3c;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-align: center;
            text-decoration: none;
        }
        .history-button:hover {
            background-color: #c0392b;
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <h2>Chatbot</h2>
        
        <div class="chat-box">
            <?php
            // Display all past messages (user and bot)
            if (isset($_SESSION['conversation'])) {
                foreach ($_SESSION['conversation'] as $dialog) {
                    echo "<div><strong>You:</strong> " . htmlspecialchars($dialog['user']) . "</div>";
                    echo "<div><strong>Bot:</strong> " . htmlspecialchars($dialog['bot']) . "</div>";
                }
            } else {
                echo "<p>No conversation history.</p>";
            }
            ?>
        </div>

        <form method="POST" class="input-container">
            <input type="text" id="message" name="message" placeholder="Type a message..." required>
            <button type="submit">Send</button>
        </form>

        <!-- Button to delete conversation history -->
        <form method="POST">
            <button class="history-button" type="submit" name="delete_history">Delete History</button>
        </form>
    </div>
</body>
</html>
s