<?php
session_start();
include 'include/header.php';
require_once 'config/config.php';
// Replace with your PayMongo Secret Key
$secretKey = "sk_test_RADMKwi3KgnpCMGAzmW1RbqU";
if (isset($_SESSION['sUser'])) {
    $username = $_SESSION['sUser'];
    $email = $_SESSION['sEmail'] ?? '';

    $stmt = $connection->prepare("SELECT user_id, subscription_type FROM tbl_user WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $userID = $row['user_id'];
        $currentSubscription = $row['subscription_type'];
        $isPremium = ($currentSubscription === 'Premium');
       
        // Set the session variable for navigation
        $_SESSION['sSubscription'] = $currentSubscription;
    } else {
        echo "User not found.";
        exit;
    }
}
if ($isPremium) {
    echo "<h2>You are already a Premium member.</h2>";
    header("Location: index.php");
    exit;
}
// Collect form data
$amount = 179 * 100; // Convert to centavo

// Define the data payload for creating a Payment Link
$data = [
        "data" => [
            "attributes" => [
                "amount" => $amount,
                "currency" => "PHP",
                "description" => "Gakumon Premium Subscription for >>>" . $username,
                "remarks" => "Monthly subscription for Gakumon Premium",
                "billing" => [
                    "name" => $username,
                    "email" => $email
                ]
            ]
        ]
    ];

// Initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.paymongo.com/v1/links");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Basic " . base64_encode($secretKey . ":")
]);

// Execute the cURL request
$result = curl_exec($ch);
curl_close($ch);

// Decode the response
$response = json_decode($result, true);

// Check if the Payment Link was created successfully
if (isset($response['data']['attributes']['checkout_url'])) {
    // Redirect to the checkout URL for payment
    header("Location: " . $response['data']['attributes']['checkout_url']);
    exit();
} else {
    // Output the error if there was an issue creating the Payment Link
    echo "Error creating payment link: " . print_r($response, true);
}
?>