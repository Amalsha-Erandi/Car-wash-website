<?php
// Include the database connection class
include('Database_Connection.php');

// Create an instance of the Database class
$db = new Database();
$conn = $db->getConnection(); // Get the database connection

// Flag to trigger alert after success
$successMessage = "";

// Insert new staff when form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addStaff'])) {
    // Get form data
    $staffName = mysqli_real_escape_string($conn, $_POST['staffName']);
    $staffEmail = mysqli_real_escape_string($conn, $_POST['staffEmail']);
    $staffRole = mysqli_real_escape_string($conn, $_POST['staffRole']);

    // Insert data into the database
    $query = "INSERT INTO staff (name, email, role) VALUES ('$staffName', '$staffEmail', '$staffRole')";
    if (mysqli_query($conn, $query)) {
        $successMessage = "New staff added successfully!";
    } else {
        $successMessage = "Error: " . mysqli_error($conn);
    }
}

// Update staff when form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editStaff'])) {
    // Get form data
    $staffId = mysqli_real_escape_string($conn, $_POST['staffId']);
    $staffName = mysqli_real_escape_string($conn, $_POST['staffName']);
    $staffEmail = mysqli_real_escape_string($conn, $_POST['staffEmail']);
    $staffRole = mysqli_real_escape_string($conn, $_POST['staffRole']);

    // Update the database
    $query = "UPDATE staff SET name='$staffName', email='$staffEmail', role='$staffRole' WHERE id='$staffId'";
    if (mysqli_query($conn, $query)) {
        $successMessage = "Staff updated successfully!";
    } else {
        $successMessage = "Error: " . mysqli_error($conn);
    }
}

// Delete staff when delete button is clicked
if (isset($_GET['delete'])) {
    $staffId = mysqli_real_escape_string($conn, $_GET['delete']);
    $query = "DELETE FROM staff WHERE id = '$staffId'";
    if (mysqli_query($conn, $query)) {
        $successMessage = "Staff deleted successfully!";
    } else {
        $successMessage = "Error: " . mysqli_error($conn);
    }
}

// Fetch all staff data
$query = "SELECT * FROM staff";
$result = mysqli_query($conn, $query);

// Close the database connection
$db->closeConnection();

// Redirect to manage-staff.php with success message
if ($successMessage) {
    header("Location: manage-staff.php?message=" . urlencode($successMessage));
    exit();
}
?>

