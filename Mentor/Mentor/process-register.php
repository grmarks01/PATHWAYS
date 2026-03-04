<?php
// Start session
session_start();

// Include database configuration
require_once 'components/db-config.php';
$conn = getDBConnection();

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Get and sanitize form data
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    $userType = $_POST['userType'];
    
    // Secondary contact data
    $hasSecondaryContact = isset($_POST['hasSecondaryContact']);
    $secondaryName = $hasSecondaryContact ? trim($_POST['secondaryName']) : null;
    $secondaryEmail = $hasSecondaryContact ? trim($_POST['secondaryEmail']) : null;
    $secondaryPhone = $hasSecondaryContact ? trim($_POST['secondaryPhone']) : null;
    $secondaryContactActive = 0;
    
    // Initialize error array
    $errors = [];
    
    // Validate inputs
    if (empty($firstName)) {
        $errors[] = "First name is required";
    }
    
    if (empty($lastName)) {
        $errors[] = "Last name is required";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    
    if (empty($password) || strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($userType)) {
        $errors[] = "User type is required";
    }
    
    if (!isset($_POST['terms'])) {
        $errors[] = "You must agree to the terms and conditions";
    }
    
    // Validate secondary contact if provided
    if ($hasSecondaryContact) {
        if (empty($secondaryName)) {
            $errors[] = "Secondary contact name is required when adding a secondary contact";
        }
        if (!empty($secondaryEmail) && !filter_var($secondaryEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Secondary contact email must be valid";
        }
        // If secondary contact fields are filled, set as active
        if (!empty($secondaryName) || !empty($secondaryEmail) || !empty($secondaryPhone)) {
            $secondaryContactActive = 1;
        }
    }
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT User_Id FROM users WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $errors[] = "Email already registered";
    }
    $stmt->close();
    
    // If no errors, proceed with registration
    if (empty($errors)) {
        
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Determine if user needs approval
        // Teachers need approval (Active = NULL), others don't need approval (Active = '1')
        $active = ($userType === 'teacher') ? NULL : '1';
        
        // Prepare SQL statement with new column names and secondary contact fields
        if ($active === NULL) {
            $stmt = $conn->prepare("INSERT INTO users (Email, Hash, Role, First_Name, Last_Name, Phone, Active, secondary_contact_name, secondary_contact_email, secondary_contact_phone, secondary_contact_active, password_changed_at, Created_Time) VALUES (?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->bind_param("sssssssssi", $email, $hashedPassword, $userType, $firstName, $lastName, $phone, $secondaryName, $secondaryEmail, $secondaryPhone, $secondaryContactActive);
        } else {
            $stmt = $conn->prepare("INSERT INTO users (Email, Hash, Role, First_Name, Last_Name, Phone, Active, secondary_contact_name, secondary_contact_email, secondary_contact_phone, secondary_contact_active, password_changed_at, Created_Time) VALUES (?, ?, ?, ?, ?, ?, '1', ?, ?, ?, ?, NOW(), NOW())");
            $stmt->bind_param("sssssssssi", $email, $hashedPassword, $userType, $firstName, $lastName, $phone, $secondaryName, $secondaryEmail, $secondaryPhone, $secondaryContactActive);
        }
        
        // Execute the statement
        if ($stmt->execute()) {
            // Registration successful
            if ($userType === 'teacher') {
                $_SESSION['success_message'] = "Registration successful! Your account is pending approval from an administrator. You will be able to login once approved.";
            } else {
                $_SESSION['success_message'] = "Registration successful! Please login.";
            }
            $_SESSION['registered_email'] = $email;
            header("Location: login-page.php");
            exit();
        } else {
            $errors[] = "Registration failed: " . $stmt->error;
        }
        
        $stmt->close();
    }
    
    // If there are errors, store them in session and redirect back
    if (!empty($errors)) {
        $_SESSION['registration_errors'] = $errors;
        $_SESSION['form_data'] = $_POST; // Save form data to repopulate
        header("Location: register-page.php");
        exit();
    }
}

closeDBConnection($conn);
?>