<?php
header('Content-Type: application/json');

// Get POST values
$name  = $_POST['name'] ?? '';
$phone = $_POST['phone'] ?? '';
$email = $_POST['email'] ?? '';

// Validation
if(empty($name) || empty($phone) || empty($email)){
    echo json_encode(['success'=>false, 'message'=>'Missing fields']);
    exit;
}

// Prepare file
if(!is_dir('data')) mkdir('data', 0777, true);
$file = 'data/submissions.json';

// Read old data
if(file_exists($file)){
    $json = json_decode(file_get_contents($file), true);
    if(!is_array($json)) $json = [];
} else {
    $json = [];
}

// Add new entry
$json[] = [
    'name' => $name,
    'phone' => $phone,
    'email' => $email,
    'timestamp' => date('Y-m-d H:i:s')
];

// Save back to file
if(file_put_contents($file, json_encode($json, JSON_PRETTY_PRINT))){
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false,'message'=>'Unable to save']);
}
?>
