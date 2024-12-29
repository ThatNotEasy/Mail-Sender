<?php
error_reporting(0);
date_default_timezone_set($_POST['config']['time_zone']);

// Required fields
$required = array(
    'letter', 'to', 'subject', 'header', 'config', 'note'
);

foreach ($required as $key => $value) {
    if (!empty($_POST[$value])) {
        $count[] = $_POST[$value];
    }
}

// Validate that all required data is provided
if (count($count) != count($_POST) || count($required) != count($count)) {
    $status = json_encode(array(
        'status'  => false,
        'message' => 'Some required data is missing'
    ));
    die($status);
}

// Set the date header
$_POST['header']['header']['Date'] = date("r (T)");

foreach ($_POST['header']['header'] as $key => $value) {
    $headers[] = $key . ": " . $value;
}

// Attempt to send the email
if (mail($_POST['to'], $_POST['subject'], base64_decode($_POST['letter']), implode("\r\n", $headers))) {
    $status = json_encode(array(
        'subject' => $_POST['subject'],
        'status'  => true,
        'message' => 'Email sent successfully',
        'note'    => $_POST['note'],
    ));
} else {
    $status = json_encode(array(
        'status'  => false,
        'message' => 'Failed to send email',
        'note'    => $_POST['note'],
    ));
}

// Return the status
if ($status) {
    die($status);
} else {
    $status = json_encode(array(
        'status'  => false,
        'message' => 'Server error occurred'
    ));
    die($status);
}
?>
