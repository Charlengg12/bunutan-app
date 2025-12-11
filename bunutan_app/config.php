<?php
/**
 * Configuration file for Bunutan App
 * Online Christmas Exchange Gift Draw System
 */

// Data directory path
define('DATA_DIR', __DIR__ . '/data/');

// Data files
define('PARTICIPANTS_FILE', DATA_DIR . 'participants.json');
define('DRAWS_FILE', DATA_DIR . 'draws.json');
define('SETTINGS_FILE', DATA_DIR . 'settings.json');

// Application settings
define('APP_NAME', 'Bunutan - Christmas Gift Exchange');
define('TIMEZONE', 'UTC');

// Set timezone
date_default_timezone_set(TIMEZONE);

// Create data directory if it doesn't exist
if (!file_exists(DATA_DIR)) {
    mkdir(DATA_DIR, 0755, true);
}

// Initialize data files if they don't exist
if (!file_exists(PARTICIPANTS_FILE)) {
    file_put_contents(PARTICIPANTS_FILE, json_encode([]));
}

if (!file_exists(DRAWS_FILE)) {
    file_put_contents(DRAWS_FILE, json_encode([]));
}

if (!file_exists(SETTINGS_FILE)) {
    file_put_contents(SETTINGS_FILE, json_encode([
        'gift_value_rules' => '',
        'draw_generated' => false,
        'draw_date' => null
    ]));
}

/**
 * Generate a unique token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Load JSON data from file
 */
function loadData($file) {
    if (!file_exists($file)) {
        return [];
    }
    $content = file_get_contents($file);
    return json_decode($content, true) ?: [];
}

/**
 * Save JSON data to file
 */
function saveData($file, $data) {
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}
?>
