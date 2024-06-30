<?php
// USSD application code

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include dbUSSD.php for database connection and sanitation
include("dbUSSD.php");

// Mock data for buses
$buses = [
    ["CarId" => "VY8A", "journeyname" => "Abokobi - Accra", "destination" => "Accra Central", "DepartureTime" => "08:00 AM", "amount" => "20"],
    ["CarId" => "OH4X", "journeyname" => "Madina - Accra Central", "destination" => "Accra Central", "DepartureTime" => "09:00 AM", "amount" => "22"]
];

// Read the variables sent via POST from our API
$sessionId   = isset($_POST["sessionId"]) ? $_POST["sessionId"] : "";
$serviceCode = isset($_POST["serviceCode"]) ? $_POST["serviceCode"] : "";
$phoneNumber = isset($_POST["phoneNumber"]) ? $_POST["phoneNumber"] : "";
$text        = isset($_POST["text"]) ? $_POST["text"] : "";

// Initialize response variable
$response = "";

// Function to log errors
function log_error($message) {
    file_put_contents('error_log.txt', date('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
}

// Processing the USSD request
try {
    if ($text == "") {
        // Initial request: Prompt user to enter details
        $response  = "CON Welcome to our booking service.\n";
        $response .= "Please enter your name:";

    } else {
        // Split the USSD text input to extract booking details
        $input = explode("*", $text);

        // Determine the level of the USSD session
        $level = count($input);

        if ($level == 1) {
            // First level response: Prompt for destination
            $response = "CON Please enter your destination:";

        } else if ($level == 2) {
            // Second level response: Display available buses
            $response = "CON Available Buses:\n";
            foreach ($buses as $index => $bus) {
                $response .= ($index + 1) . ". " . $bus['journeyname'] . " - " . $bus['destination'] . " (" . $bus['DepartureTime'] . ")\n";
            }
            $response .= "Please select a bus (1, 2, ...):";

        } else if ($level == 3) {
            // Third level response: Confirm details
            $selectedBusIndex = (int)$input[2] - 1;
            if ($selectedBusIndex >= 0 && $selectedBusIndex < count($buses)) {
                $response = "CON Confirm your details:\n";
                $response .= "Name: " . sanitize($conn, $input[0]) . "\n";
                $response .= "Destination: " . sanitize($conn, $input[1]) . "\n";
                $response .= "Bus: " . $buses[$selectedBusIndex]['journeyname'] . " - " . $buses[$selectedBusIndex]['destination'] . " (" . $buses[$selectedBusIndex]['DepartureTime'] . ")\n";
                $response .= "Amount: " . $buses[$selectedBusIndex]['amount'] . "\n";
                $response .= "Reply with:\n";
                $response .= "1. Confirm\n";
                $response .= "2. Cancel";
            } else {
                $response = "END Invalid bus selection. Please try again.";
            }

        } else if ($level == 4) {
            // Fourth level response: Process and save booking details
            if ($input[3] == "1") {
                $name = sanitize($conn, $input[0]);
                $destination = sanitize($conn, $input[1]);
                $selectedBusIndex = (int)$input[2] - 1;
                $selectedBus = $buses[$selectedBusIndex];

                log_error("Attempting to insert booking: Name = $name, Destination = $destination, Vehicle = " . $selectedBus['CarId'] . ", Amount = " . $selectedBus['amount']);

                // Insert details into the database
                $sql = "INSERT INTO Commuters (name, destination, vehicle, amount) VALUES ('$name', '$destination', '" . $selectedBus['CarId'] . "', '" . $selectedBus['amount'] . "')";
                
                if ($conn->query($sql) === TRUE) {
                    // Booking successful response
                    $response = "END Booking successful. Thank you!\n";
                } else {
                    // Database error response
                    log_error("Database Error: " . $conn->error);
                    $response = "END Error: " . $conn->error;
                }
            } else {
                // User canceled the transaction
                $response = "END Transaction canceled by user.";
            }

        } else {
            // Invalid input response
            $response = "END Invalid input. Please try again.";
        }
    }
} catch (Exception $e) {
    // Handle unexpected errors
    log_error("Exception: " . $e->getMessage());
    $response = $e;
}

// Clear any output before setting the header
ob_clean();

// Echo the response back to the API
header('Content-type: text/plain');
echo  $response;

// Close database connection
$conn->close();
?>