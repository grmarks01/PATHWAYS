<?php
session_start();

// Check if user is logged in and is an approved teacher (admin)
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login-page.php");
    exit();
}

$is_admin = ($_SESSION['user_role'] === 'teacher' && isset($_SESSION['user_active']) && $_SESSION['user_active'] === '1');

if (!$is_admin) {
    header("Location: index.php");
    exit();
}

// Include database configuration
require_once 'components/db-config.php';
$conn = getDBConnection();

$message = '';
$message_type = '';
$import_errors = [];

// Define helper functions for grade level normalization and validation
function normalizeGradeLevels($grade_levels) {
    $grade_levels = trim($grade_levels);
    
    // Convert to lowercase for consistent processing
    $grade_levels = strtolower($grade_levels);
    
    // Handle Excel date conversions (any month abbreviation)
    $month_conversions = [
        'jan' => '1', 'feb' => '2', 'mar' => '3', 'apr' => '4', 
        'may' => '5', 'jun' => '6', 'jul' => '7', 'aug' => '8',
        'sep' => '9', 'oct' => '10', 'nov' => '11', 'dec' => '12'
    ];
    
    foreach ($month_conversions as $month => $grade_num) {
        if (preg_match("/(\d+)-{$month}/", $grade_levels, $matches) || 
            preg_match("/{$month}-(\d+)/", $grade_levels, $matches)) {
            $day = $matches[1];
            // Convert date format to grade range (e.g., "12-nov" -> "11-12")
            $start_grade = min($grade_num, $day);
            $end_grade = max($grade_num, $day);
            return "{$start_grade}-{$end_grade}";
        }
    }
    
    // Handle "rising" format variations
    if (preg_match('/(\d+)\s*(rising|rise|rising grade|going into)/', $grade_levels, $matches)) {
        $current_grade = intval($matches[1]);
        $next_grade = $current_grade + 1;
        if ($next_grade <= 12) {
            return "{$current_grade}-{$next_grade}";
        }
        return (string)$current_grade;
    }
    
    // Handle "entering" or "starting" format
    if (preg_match('/(entering|starting|beginning)\s*(grade\s*)?(\d+)/', $grade_levels, $matches)) {
        $grade = intval($matches[3]);
        return (string)$grade;
    }
    
    // Handle "through" format (e.g., "6 through 12")
    if (preg_match('/(\d+)\s*through\s*(\d+)/', $grade_levels, $matches)) {
        return "{$matches[1]}-{$matches[2]}";
    }
    
    // Handle "to" format (e.g., "6 to 12")
    if (preg_match('/(\d+)\s*to\s*(\d+)/', $grade_levels, $matches)) {
        return "{$matches[1]}-{$matches[2]}";
    }
    
    // Handle en-dash and em-dash variations
    $grade_levels = str_replace(['â€"', 'â€"', 'â€•'], '-', $grade_levels);
    
    // Remove common suffixes
    $grade_levels = preg_replace('/(\d)(th|st|nd|rd)\b/', '$1', $grade_levels);
    
    // Remove "grade" prefix and extra spaces
    $grade_levels = preg_replace('/grade\s*/', '', $grade_levels);
    $grade_levels = preg_replace('/\s+/', '', $grade_levels);
    
    // Handle single grade with comma separation (e.g., "9,10,11,12")
    if (preg_match('/^(\d+)(,\d+)+$/', $grade_levels)) {
        $grades = explode(',', $grade_levels);
        $min_grade = min($grades);
        $max_grade = max($grades);
        return "{$min_grade}-{$max_grade}";
    }
    
    // Handle "and" conjunction (e.g., "11 and 12")
    if (preg_match('/(\d+)\s*and\s*(\d+)/', $grade_levels, $matches)) {
        return "{$matches[1]}-{$matches[2]}";
    }
    
    return $grade_levels;
}

function validateGradeLevels($grade_levels) {
    // Single grade validation (6, 7, 8, 9, 10, 11, 12)
    if (preg_match('/^(6|7|8|9|10|11|12)$/', $grade_levels)) {
        $grade = intval($grade_levels);
        if ($grade >= 6 && $grade <= 12) {
            return ['is_valid' => true];
        }
    }
    
    // Range validation (6-12, 9-12, etc.)
    if (preg_match('/^(\d+)-(\d+)$/', $grade_levels, $matches)) {
        $start = intval($matches[1]);
        $end = intval($matches[2]);
        
        if ($start < 6 || $end > 12) {
            return [
                'is_valid' => false,
                'message' => "must be between 6 and 12."
            ];
        }
        
        if ($start > $end) {
            return [
                'is_valid' => false, 
                'message' => "range is invalid: starting grade ($start) cannot be greater than ending grade ($end)."
            ];
        }
        
        return ['is_valid' => true];
    }
    
    return [
        'is_valid' => false,
        'message' => "must be in format: single grade (6-12) or range (6-12, 9-12, etc.)."
    ];
}

// Handle Export
if (isset($_POST['action']) && $_POST['action'] === 'export') {
    // Fetch all opportunities
    $query = "SELECT id, display_name, state, program_name, grade_levels, eligibility, cost_funding, 
              deadlines, website_link, category, field, delivery_context, notes 
              FROM pathways_opportunities ORDER BY state, program_name";
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        // Set headers for CSV download
        $filename = 'pathways_opportunities_' . date('Y-m-d_H-i-s') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        // Create output stream
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Add header row
        fputcsv($output, [
            'State',
            'Program/Opportunity Name',
            'Grade Levels',
            'Eligibility Requirements',
            'Cost/Funding',
            'Deadlines',
            'Website/Link',
            'Category (Type of Opportunity)',
            'Field (Area of Focus)',
            'Delivery Context (Where Offered)',
            'Notes'
        ]);
        
        // Add data rows
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['state'] ?? '',
                $row['program_name'] ?? '',
                $row['grade_levels'] ?? '',
                $row['eligibility'] ?? '',
                $row['cost_funding'] ?? '',
                $row['deadlines'] ?? '',
                $row['website_link'] ?? '',
                $row['category'] ?? '',
                $row['field'] ?? '',
                $row['delivery_context'] ?? '',
                $row['notes'] ?? ''
            ]);
        }
        
        fclose($output);
        exit();
    } else {
        $message = "No opportunities found to export.";
        $message_type = "error";
    }
}

// Handle Import
if (isset($_POST['action']) && $_POST['action'] === 'import' && isset($_FILES['import_file'])) {
    $file = $_FILES['import_file'];
    
    // Check if file was uploaded
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $message = "Error uploading file. Please try again.";
        $message_type = "error";
    } else {
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Check file extension - CSV ONLY
        if ($file_extension !== 'csv') {
            $message = "Invalid file format. Please upload a CSV file only. If you have an Excel file, open it in Excel and use 'Save As' → 'CSV (Comma delimited)'.";
            $message_type = "error";
        } else {
            // Try to read the file
            $file_handle = fopen($file['tmp_name'], 'r');
            
            if ($file_handle === false) {
                $message = "Error reading file. Please try again.";
                $message_type = "error";
            } else {
                $import_errors = [];
                $row_number = 0;
                $header_row = null;
                $valid_rows = [];
                
                // Valid values for certain fields
                $valid_states = [
                    'Alabama', 'Alaska', 'Arizona', 'Arkansas', 'California', 'Colorado', 'Connecticut',
                    'Delaware', 'Florida', 'Georgia', 'Hawaii', 'Idaho', 'Illinois', 'Indiana', 'Iowa',
                    'Kansas', 'Kentucky', 'Louisiana', 'Maine', 'Maryland', 'Massachusetts', 'Michigan',
                    'Minnesota', 'Mississippi', 'Missouri', 'Montana', 'Nebraska', 'Nevada', 'New Hampshire',
                    'New Jersey', 'New Mexico', 'New York', 'North Carolina', 'North Dakota', 'Ohio',
                    'Oklahoma', 'Oregon', 'Pennsylvania', 'Rhode Island', 'South Carolina', 'South Dakota',
                    'Tennessee', 'Texas', 'Utah', 'Vermont', 'Virginia', 'Washington', 'West Virginia',
                    'Wisconsin', 'Wyoming'
                ];
                
                $valid_categories = ['Club', 'Scholarship', 'Competition', 'Academic Program', 'Program', 'Athletics/Sports'];
                
                $header_row_number = 0;
                $is_first_row = true;

                // Read and validate file
                while (($data = fgetcsv($file_handle, 0, ",")) !== false) {
                    $row_number++;
                    
                    // Skip empty rows
                    if (empty(array_filter($data))) {
                        continue;
                    }
                    
                    $first_col = isset($data[0]) ? trim($data[0]) : '';
                    
                    // If this is the first non-empty row, assume it's the header
                    if ($is_first_row) {
                        $header_row = array_map('trim', $data);
                        $header_row_number = $row_number;
                        $is_first_row = false;
                        continue;
                    }
                    
                    // Check if first column is a valid US state - if so, this is a data row
                    if (in_array($first_col, $valid_states)) {
                        // This is a data row - process it
                        // Create associative array from row data
                        $row_data = [];
                        foreach ($header_row as $index => $column_name) {
                            $row_data[$column_name] = isset($data[$index]) ? trim($data[$index]) : '';
                        }
                        
                        // Normalize column names - find the actual column names flexibly
                        $program_name = '';
                        $state = '';
                        
                        foreach ($row_data as $key => $value) {
                            if (stripos($key, 'State') !== false && empty($state)) {
                                $state = $value;
                            }
                            if (stripos($key, 'Program') !== false && stripos($key, 'Name') !== false && empty($program_name)) {
                                $program_name = $value;
                            }
                        }
                        
                        // Validate row
                        $row_errors = [];
                        
                        // Check required fields
                        if (empty($state)) {
                            $row_errors[] = "State is required";
                        }
                        if (empty($program_name)) {
                            $row_errors[] = "Program Name is required";
                        }
                        
                        // Validate State
                        if (!empty($state) && !in_array($state, $valid_states)) {
                            $row_errors[] = "Invalid state '{$state}'. Must be a valid US state name.";
                        }
                        
                        // Validate Category
                        $category = $row_data['Category (Type of Opportunity)'] ?? $row_data['Category'] ?? '';
                        if (!empty($category) && !in_array($category, $valid_categories)) {
                            $row_errors[] = "Invalid category '{$category}'. Must be one of: " . implode(', ', $valid_categories);
                        }
                        
                        // Validate Grade Levels (comprehensive solution)
                        $grade_levels = '';
                        foreach ($row_data as $key => $value) {
                            if (stripos($key, 'Grade') !== false && stripos($key, 'Level') !== false) {
                                $grade_levels = $value;
                                break;
                            }
                        }

                        if (!empty($grade_levels)) {
                            $original_grade_levels = $grade_levels;
                            $grade_levels = trim($grade_levels);
                            
                            // Normalize various formats
                            $normalized_grade = normalizeGradeLevels($grade_levels);
                            
                            // Validate the normalized format
                            $validation_result = validateGradeLevels($normalized_grade);
                            
                            if (!$validation_result['is_valid']) {
                                $row_errors[] = "Grade Levels " . $validation_result['message'] . " Found: '" . $original_grade_levels . "'";
                            } else {
                                // Update with normalized version if different
                                if ($original_grade_levels !== $normalized_grade) {
                                    foreach ($row_data as $key => $value) {
                                        if (stripos($key, 'Grade') !== false && stripos($key, 'Level') !== false) {
                                            $row_data[$key] = $normalized_grade;
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                        
                        // Validate Cost/Funding (must be numeric if provided, after removing $ and commas)
                        $cost_funding = $row_data['Cost/Funding'] ?? '';
                        if (!empty($cost_funding)) {
                            // Remove $ sign, commas, and whitespace
                            $cleaned_cost = trim(str_replace(['$', ',', ' '], '', $cost_funding));
                            
                            // Check if the cleaned value is numeric
                            if (!is_numeric($cleaned_cost)) {
                                $row_errors[] = "Cost/Funding must be a number (optionally with $ sign)";
                            } else {
                                // Update the row_data with the cleaned numeric value
                                $row_data['Cost/Funding'] = $cleaned_cost;
                            }
                        }
                        
                        // Validate Deadlines (must be valid date, "Rolling", or empty)
                        $deadlines = '';
                        foreach ($row_data as $key => $value) {
                            if (stripos($key, 'Deadline') !== false) {
                                $deadlines = $value;
                                break;
                            }
                        }
                        if (!empty($deadlines)) {
							// Accept "Rolling" or "rolling" as valid
							if (strtolower(trim($deadlines)) === 'rolling') {
								$deadlines = ''; // Store as empty/null in database
								} else {
								// Try YYYY-MM-DD format first
								$date = DateTime::createFromFormat('Y-m-d', $deadlines);
								if ($date && $date->format('Y-m-d') === $deadlines) {
								// Already in correct format, keep as is
								} else {
								// Try M/D/Y format
								$date = DateTime::createFromFormat('m/d/Y', $deadlines);
								if ($date) {
								// Convert to YYYY-MM-DD format for MySQL
								$deadlines = $date->format('Y-m-d');
								} else {
								$row_errors[] = "Deadlines must be in YYYY-MM-DD, MM/DD/YYYY format, or 'Rolling'";
								}
								}
								}
						}
                        
                        // Check for errors
                        if (!empty($row_errors)) {
                            $import_errors[] = "Row $row_number ($state - $program_name): " . implode('; ', $row_errors);
                            // Continue processing other rows even if this one has errors
                        } else {
                            // Store normalized data - find all columns flexibly
                            $eligibility = '';
                            $website_link = '';
                            $field = '';
                            $delivery_context = '';
                            $notes = '';
                            
                            foreach ($row_data as $key => $value) {
                                if (stripos($key, 'Eligibility') !== false && empty($eligibility)) {
                                    $eligibility = $value;
                                }
                                if (stripos($key, 'Website') !== false || stripos($key, 'Link') !== false && empty($website_link)) {
                                    $website_link = $value;
                                }
                                if (stripos($key, 'Field') !== false && stripos($key, 'Focus') !== false && empty($field)) {
                                    $field = $value;
                                }
                                if (stripos($key, 'Delivery') !== false && stripos($key, 'Context') !== false && empty($delivery_context)) {
                                    $delivery_context = $value;
                                }
                                if (stripos($key, 'Note') !== false && empty($notes)) {
                                    $notes = $value;
                                }
                            }
                            
                            $valid_rows[] = [
                                'state' => $state,
                                'program_name' => $program_name,
                                'grade_levels' => $grade_levels,
                                'eligibility' => $eligibility,
                                'cost_funding' => $cost_funding,
                                'deadlines' => $deadlines,
                                'website_link' => $website_link,
                                'category' => $category,
                                'field' => $field,
                                'delivery_context' => $delivery_context,
                                'notes' => $notes
                            ];
                        }
                    } else if ($header_row === null) {
                        // Not a state and we haven't found header yet - this might be the header row
                        // Store this as potential header
                        $header_row = array_map('trim', $data);
                        $header_row_number = $row_number;
                        continue;
                    } else {
                        // We have a header but this row doesn't start with a state - skip it
                        continue;
                    }
                }
                
                fclose($file_handle);
                
                // Check if we found any data rows
                if ($header_row === null) {
                    $import_errors[] = "No data rows found. The file must contain rows where the first column is a valid US state name (e.g., Alabama, Alaska, etc.).";
                }
                
                // If there are errors, display them
                if (!empty($import_errors)) {
                    $message = "Import failed due to validation errors. Please fix the following issues:";
                    $message_type = "error";
                } else if (empty($valid_rows)) {
                    $message = "No valid data rows found. Header found at row: " . ($header_row_number > 0 ? $header_row_number : "none") . ". Total rows processed: $row_number. Please ensure data rows start with a valid US state name.";
                    $message_type = "error";
                } else {
                    // Import data
                    $insert_mode = $_POST['insert_mode'] ?? 'skip';
                    $imported_count = 0;
                    $skipped_count = 0;
                    
                    foreach ($valid_rows as $row) {
                        // Check if opportunity already exists (by state + program_name)
                        $check_stmt = $conn->prepare("SELECT id FROM pathways_opportunities WHERE state = ? AND program_name = ?");
                        $check_stmt->bind_param("ss", $row['state'], $row['program_name']);
                        $check_stmt->execute();
                        $result = $check_stmt->get_result();
                        $existing = $result->fetch_assoc();
                        $check_stmt->close();
                        
                        if ($existing) {
                            if ($insert_mode === 'create_new') {
                                // Create new record even though one exists (user wants to keep both)
                                $stmt = $conn->prepare("INSERT INTO pathways_opportunities 
                                    (display_name, state, program_name, grade_levels, eligibility, cost_funding, 
                                    deadlines, website_link, category, field, delivery_context, notes) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                                
                                $display_name = null;
                                $cost_funding = !empty($row['cost_funding']) ? floatval($row['cost_funding']) : null;
                                $deadlines = !empty($row['deadlines']) ? $row['deadlines'] : null;
                                $grade_levels = !empty($row['grade_levels']) ? $row['grade_levels'] : null;
                                $eligibility = !empty($row['eligibility']) ? $row['eligibility'] : null;
                                $website_link = !empty($row['website_link']) ? $row['website_link'] : null;
                                $category = !empty($row['category']) ? $row['category'] : null;
                                $field = !empty($row['field']) ? $row['field'] : null;
                                $delivery_context = !empty($row['delivery_context']) ? $row['delivery_context'] : null;
                                $notes = !empty($row['notes']) ? $row['notes'] : null;
                                
                                $stmt->bind_param("sssssdssssss",
                                    $display_name,
                                    $row['state'],
                                    $row['program_name'],
                                    $grade_levels,
                                    $eligibility,
                                    $cost_funding,
                                    $deadlines,
                                    $website_link,
                                    $category,
                                    $field,
                                    $delivery_context,
                                    $notes
                                );
                                
                                if ($stmt->execute()) {
                                    $imported_count++;
                                }
                                $stmt->close();
                            } else {
                                $skipped_count++;
                            }
                        } else {
                            // Insert new record
                            $stmt = $conn->prepare("INSERT INTO pathways_opportunities 
                                (display_name, state, program_name, grade_levels, eligibility, cost_funding, 
                                deadlines, website_link, category, field, delivery_context, notes) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                            
                            $display_name = null;
                            $cost_funding = !empty($row['cost_funding']) ? floatval($row['cost_funding']) : null;
                            $deadlines = !empty($row['deadlines']) ? $row['deadlines'] : null;
                            $grade_levels = !empty($row['grade_levels']) ? $row['grade_levels'] : null;
                            $eligibility = !empty($row['eligibility']) ? $row['eligibility'] : null;
                            $website_link = !empty($row['website_link']) ? $row['website_link'] : null;
                            $category = !empty($row['category']) ? $row['category'] : null;
                            $field = !empty($row['field']) ? $row['field'] : null;
                            $delivery_context = !empty($row['delivery_context']) ? $row['delivery_context'] : null;
                            $notes = !empty($row['notes']) ? $row['notes'] : null;
                            
                            $stmt->bind_param("sssssdssssss",
                                $display_name,
                                $row['state'],
                                $row['program_name'],
                                $grade_levels,
                                $eligibility,
                                $cost_funding,
                                $deadlines,
                                $website_link,
                                $category,
                                $field,
                                $delivery_context,
                                $notes
                            );
                            
                            if ($stmt->execute()) {
                                $imported_count++;
                            }
                            $stmt->close();
                        }
                    }
                    
                    $message = "Import completed successfully! ";
                    if ($imported_count > 0) $message .= "$imported_count new opportunities added. ";
                    if ($skipped_count > 0) $message .= "$skipped_count existing opportunities skipped.";
                    $message_type = "success";
                }
            }
        }
    }
}

// Get total count for statistics
$count_query = "SELECT COUNT(*) as total FROM pathways_opportunities";
$count_result = $conn->query($count_query);
$total_opportunities = $count_result->fetch_assoc()['total'];

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Import/Export - Pathways</title>

  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/css/main.css" rel="stylesheet">

  <style>
    .admin-section {
      padding: 80px 0 60px;
      min-height: 100vh;
    }
    
    .admin-header {
      background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
      color: white;
      padding: 40px;
      border-radius: 10px;
      margin-bottom: 40px;
    }
    
    .admin-header h1 {
      margin: 0 0 10px 0;
      font-size: 32px;
      font-weight: 700;
    }
    
    .alert {
      padding: 15px 20px;
      border-radius: 5px;
      margin-bottom: 20px;
    }
    
    .alert-success {
      background: #d4edda;
      border-left: 4px solid #28a745;
      color: #155724;
    }
    
    .alert-error {
      background: #f8d7da;
      border-left: 4px solid #dc3545;
      color: #721c24;
    }
    
    .stats-card {
      background: white;
      padding: 25px;
      border-radius: 10px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
      margin-bottom: 30px;
      text-align: center;
    }
    
    .stats-card h3 {
      font-size: 48px;
      font-weight: 700;
      color: #28a745;
      margin: 0 0 10px 0;
      line-height: 1;
      font-variant-numeric: lining-nums;
      -webkit-font-feature-settings: "lnum" 1;
      -moz-font-feature-settings: "lnum" 1;
      -ms-font-feature-settings: "lnum" 1;
      font-feature-settings: "lnum" 1;
    }
    
    .stats-card p {
      color: #666;
      margin: 0;
      font-size: 16px;
    }
    
    .action-card {
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
      margin-bottom: 30px;
    }
    
    .action-card h3 {
      color: #333;
      margin: 0 0 20px 0;
      font-size: 24px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .action-card p {
      color: #666;
      margin-bottom: 20px;
      line-height: 1.6;
    }
    
    .btn-action {
      padding: 15px 30px;
      border: none;
      border-radius: 5px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      font-size: 16px;
      display: inline-flex;
      align-items: center;
      gap: 10px;
    }
    
    .btn-export {
      background: #28a745;
      color: white;
    }
    
    .btn-export:hover {
      background: #218838;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
    }
    
    .btn-import {
      background: #007bff;
      color: white;
    }
    
    .btn-import:hover {
      background: #0056b3;
    }
    
    .file-input-wrapper {
      position: relative;
      margin-bottom: 15px;
    }
    
    .file-input-wrapper input[type="file"] {
      width: 100%;
      padding: 12px;
      border: 2px dashed #ddd;
      border-radius: 5px;
      cursor: pointer;
    }
    
    .file-input-wrapper input[type="file"]:hover {
      border-color: #007bff;
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 8px;
      color: #333;
      font-weight: 600;
    }
    
    .form-group select {
      width: 100%;
      padding: 12px;
      border: 1px solid #ddd;
      border-radius: 5px;
      font-size: 15px;
    }
    
    .info-box {
      background: #e3f2fd;
      padding: 15px;
      border-radius: 5px;
      border-left: 4px solid #2196f3;
      margin-bottom: 20px;
      font-size: 14px;
    }
    
    .info-box ul {
      margin: 10px 0 0 0;
      padding-left: 20px;
    }
    
    .info-box li {
      margin-bottom: 5px;
    }
    
    .error-list {
      background: #fff;
      padding: 20px;
      border-radius: 5px;
      border: 1px solid #dc3545;
      margin-top: 15px;
      max-height: 400px;
      overflow-y: auto;
    }
    
    .error-list h4 {
      color: #dc3545;
      margin: 0 0 15px 0;
      font-size: 18px;
    }
    
    .error-list ul {
      margin: 0;
      padding-left: 20px;
    }
    
    .error-list li {
      color: #721c24;
      margin-bottom: 8px;
      line-height: 1.5;
    }
    
    .template-download {
      background: #fff3cd;
      padding: 15px;
      border-radius: 5px;
      border-left: 4px solid #ffc107;
      margin-bottom: 20px;
    }
    
    .template-download strong {
      color: #856404;
    }
    
    .csv-instructions {
      background: #d1ecf1;
      padding: 20px;
      border-radius: 5px;
      border-left: 4px solid #17a2b8;
      margin-bottom: 20px;
    }
    
    .csv-instructions h4 {
      color: #0c5460;
      margin: 0 0 10px 0;
      font-size: 16px;
      font-weight: 600;
    }
    
    .csv-instructions ol {
      margin: 10px 0 0 0;
      padding-left: 25px;
      color: #0c5460;
    }
    
    .csv-instructions li {
      margin-bottom: 8px;
      line-height: 1.5;
    }
    
    .csv-instructions strong {
      color: #0c5460;
    }
  </style>
</head>

<body>

<?php include('components/header.php'); ?>

<main class="main">
  <section class="admin-section">
    <div class="container">
      
      <div class="admin-header" data-aos="fade-down">
        <h1><i class="bi bi-arrow-down-up"></i> Import/Export Opportunities</h1>
        <p>Export opportunities to CSV or import from a CSV file</p>
      </div>
      
      <?php if ($message): ?>
      <div class="alert alert-<?php echo $message_type; ?>" data-aos="fade-up">
        <i class="bi bi-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
        <?php echo htmlspecialchars($message); ?>
      </div>
      <?php endif; ?>
      
      <?php if (!empty($import_errors)): ?>
      <div class="error-list" data-aos="fade-up">
        <h4><i class="bi bi-exclamation-triangle"></i> Validation Errors</h4>
        <ul>
          <?php foreach ($import_errors as $error): ?>
            <li><?php echo htmlspecialchars($error); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>
      
      <div class="stats-card" data-aos="fade-up">
        <h3><?php echo $total_opportunities; ?></h3>
        <p>Total Opportunities in Database</p>
      </div>
      
      <div class="row">
        <div class="col-md-6">
          <div class="action-card" data-aos="fade-right">
            <h3><i class="bi bi-download"></i> Export Opportunities</h3>
            <p>Download all opportunities from the database as a CSV file. This file can be opened in Microsoft Excel, Google Sheets, or any spreadsheet application.</p>
            
            <div class="info-box">
              <i class="bi bi-info-circle"></i> <strong>Export includes:</strong>
              <ul>
                <li>All opportunity data fields</li>
                <li>CSV format (opens directly in Excel)</li>
                <li>Can be edited and re-imported</li>
              </ul>
            </div>
            
            <form method="POST">
              <input type="hidden" name="action" value="export">
              <button type="submit" class="btn-action btn-export">
                <i class="bi bi-file-earmark-spreadsheet"></i> Export to CSV
              </button>
            </form>
          </div>
        </div>
        
        <div class="col-md-6">
          <div class="action-card" data-aos="fade-left">
            <h3><i class="bi bi-upload"></i> Import Opportunities</h3>
            <p>Upload a CSV file to add or update opportunities in the database.</p>
            
            <div class="template-download">
              <i class="bi bi-lightbulb"></i> <strong>Tip:</strong> Export the current database first to get a properly formatted CSV template with all required columns.
            </div>
            
            <div class="csv-instructions">
              <h4><i class="bi bi-info-circle-fill"></i> How to Convert Excel to CSV:</h4>
              <ol>
                <li>Open your Excel file (.xlsx or .xls)</li>
                <li>Click <strong>File</strong> → <strong>Save As</strong></li>
                <li>In "Save as type" dropdown, select <strong>CSV (Comma delimited) (*.csv)</strong></li>
                <li>Click <strong>Save</strong></li>
                <li>If Excel warns about features, click <strong>Yes</strong> or <strong>OK</strong></li>
                <li>Upload the saved .csv file here</li>
              </ol>
            </div>
            
            <div class="info-box">
              <i class="bi bi-info-circle"></i> <strong>File Requirements:</strong>
              <ul>
                <li><strong>Must be CSV format only</strong> - if you have Excel, use "Save As CSV" first</li>
                <li><strong>IMPORTANT:</strong> The system will automatically detect data rows by looking for valid US state names in the first column (Alabama, Alaska, Arizona, etc.)</li>
                <li>The row immediately before the first data row will be treated as the header row</li>
                <li>Required columns: <strong>State</strong> (first column) and a column containing both "Program" and "Name"</li>
                <li>State must be a valid US state name</li>
                <li>Category must be one of: Club, Scholarship, Competition, Academic Program, Program, Athletics/Sports</li>
                <li><strong>Grade Levels</strong> must be: single number (6-12), number-number (e.g. 6-12), or number to number (e.g. 6 to 12)</li>
                <li><strong>Deadlines</strong> must be in YYYY-MM-DD format, MM/DD/YYYY format, or "Rolling"</li>
                <li>Cost/Funding must be a number</li>
              </ul>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
              <input type="hidden" name="action" value="import">
              
              <div class="file-input-wrapper">
                <label><i class="bi bi-file-earmark-arrow-up"></i> Select CSV File</label>
                <input type="file" name="import_file" accept=".csv" required>
              </div>
              
              <div class="form-group">
                <label>If opportunity exists (same State + Program Name):</label>
                <select name="insert_mode">
                  <option value="skip">Skip (don't import)</option>
                  <option value="create_new">Create new record (keep both versions)</option>
                </select>
              </div>
              
              <button type="submit" class="btn-action btn-import">
                <i class="bi bi-upload"></i> Import from CSV
              </button>
            </form>
          </div>
        </div>
      </div>
      
    </div>
  </section>
</main>

<?php include('components/footer.php'); ?>

<a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/vendor/aos/aos.js"></script>
<script src="assets/js/main.js"></script>

<script>
AOS.init({
  duration: 800,
  easing: 'ease-in-out',
  once: true
});
</script>

</body>
</html>