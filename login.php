<?php
// Database connection
$servername = "localhost:3306";
$username = "root";
$password = "1234"; // Assuming empty password
$dbname = "doorlock";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to get the name associated with the RFID UID
function getName($rfid_uid)
{
    global $conn;
    $stmt = $conn->prepare("SELECT name FROM member WHERE UPPER(rfid_uid) = ?");
    $stmt->bind_param("s", $rfid_uid);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['name'];
    } else {
        return null;
    }
}

// Function to check if RFID UID belongs to a member
function isMember($rfid_uid)
{
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM member WHERE UPPER(rfid_uid) = ?");
    $stmt->bind_param("s", $rfid_uid);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// Function to toggle entry/exit based on previous attendance records
function toggleEntryExit($rfid_uid)
{
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM attendance WHERE UPPER(rfid_uid) = ? ORDER BY entry_time DESC LIMIT 1");
    $stmt->bind_param("s", $rfid_uid);
    $stmt->execute();
    $result = $stmt->get_result();
    $last_entry = $result->fetch_assoc();

    if ($last_entry && $last_entry['exit_time'] === null) {
        // Last record found and exit time is NULL, so update exit time
        $stmt = $conn->prepare("UPDATE attendance SET exit_time = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->bind_param("i", $last_entry['id']);
        return $stmt->execute();
    } else {
        // Last record not found or exit time is already set, so insert new entry
        $name = getName($rfid_uid);
        if ($name) {
            $stmt = $conn->prepare("INSERT INTO attendance (rfid_uid, name, entry_time) VALUES (?, ?, CURRENT_TIMESTAMP)");
            $stmt->bind_param("ss", $rfid_uid, $name);
            return $stmt->execute();
        } else {
            return false;
        }
    }
}

// Check if request method is POST and action is set
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"])) {
    switch ($_POST['action']) {
        case 'insertRecord':
            if (isset($_POST['cardid'])) {
                $rfid_uid = strtoupper($_POST['cardid']); // Convert RFID UID to uppercase

                // Check if RFID UID belongs to a member
                if (isMember($rfid_uid)) {
                    // RFID UID found in member database, proceed with attendance
                    if (toggleEntryExit($rfid_uid)) {
                        http_response_code(200);
                        echo "success";
                    } else {
                        http_response_code(500);
                        echo "Internal Server Error";
                    }
                } else {
                    // RFID UID not found in member database, ignore entry
                    http_response_code(404);
                    echo "RFID not recognized";
                }
            } else {
                http_response_code(400);
                echo "Bad Request: cardid not set";
            }
            break;
        default:
            http_response_code(400);
            echo "Bad Request: action not recognized";
            break;
    }
} else {
    http_response_code(405);
    echo "Method Not Allowed";
}
?>
