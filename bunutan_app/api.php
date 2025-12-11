<?php
/**
 * API Backend for Bunutan App
 * Handles all backend operations via JSON responses
 */

require_once 'config.php';

header('Content-Type: application/json');

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Route requests
switch ($action) {
    case 'add_participant':
        if ($method === 'POST') {
            addParticipant();
        }
        break;
    
    case 'get_participants':
        if ($method === 'GET') {
            getParticipants();
        }
        break;
    
    case 'set_gift_rules':
        if ($method === 'POST') {
            setGiftRules();
        }
        break;
    
    case 'get_settings':
        if ($method === 'GET') {
            getSettings();
        }
        break;
    
    case 'generate_draw':
        if ($method === 'POST') {
            generateDraw();
        }
        break;
    
    case 'get_draw':
        if ($method === 'GET') {
            getDraw();
        }
        break;
    
    case 'reveal_partner':
        if ($method === 'POST') {
            revealPartner();
        }
        break;
    
    case 'reset_all':
        if ($method === 'POST') {
            resetAll();
        }
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

/**
 * Add a new participant
 */
function addParticipant() {
    $input = json_decode(file_get_contents('php://input'), true);
    $name = trim($input['name'] ?? '');
    
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Name is required']);
        return;
    }
    
    $participants = loadData(PARTICIPANTS_FILE);
    
    // Check if draw already generated
    $settings = loadData(SETTINGS_FILE);
    if ($settings['draw_generated'] ?? false) {
        echo json_encode(['success' => false, 'message' => 'Cannot add participants after draw is generated']);
        return;
    }
    
    // Check for duplicate names
    foreach ($participants as $participant) {
        if (strcasecmp($participant['name'], $name) === 0) {
            echo json_encode(['success' => false, 'message' => 'Participant already exists']);
            return;
        }
    }
    
    $participant = [
        'id' => uniqid(),
        'name' => $name,
        'added_at' => date('Y-m-d H:i:s')
    ];
    
    $participants[] = $participant;
    saveData(PARTICIPANTS_FILE, $participants);
    
    echo json_encode(['success' => true, 'participant' => $participant]);
}

/**
 * Get all participants
 */
function getParticipants() {
    $participants = loadData(PARTICIPANTS_FILE);
    echo json_encode(['success' => true, 'participants' => $participants]);
}

/**
 * Set gift value rules
 */
function setGiftRules() {
    $input = json_decode(file_get_contents('php://input'), true);
    $rules = trim($input['rules'] ?? '');
    
    $settings = loadData(SETTINGS_FILE);
    $settings['gift_value_rules'] = $rules;
    saveData(SETTINGS_FILE, $settings);
    
    echo json_encode(['success' => true, 'message' => 'Gift rules updated']);
}

/**
 * Get settings including gift rules
 */
function getSettings() {
    $settings = loadData(SETTINGS_FILE);
    echo json_encode(['success' => true, 'settings' => $settings]);
}

/**
 * Generate the draw
 */
function generateDraw() {
    $participants = loadData(PARTICIPANTS_FILE);
    
    if (count($participants) < 2) {
        echo json_encode(['success' => false, 'message' => 'At least 2 participants are required']);
        return;
    }
    
    // Check if draw already exists
    $settings = loadData(SETTINGS_FILE);
    if ($settings['draw_generated'] ?? false) {
        echo json_encode(['success' => false, 'message' => 'Draw already generated']);
        return;
    }
    
    // Perform the draw using derangement algorithm
    $givers = $participants;
    $receivers = $participants;
    
    // Shuffle receivers until we get a valid derangement (no one gets themselves)
    $maxAttempts = 1000;
    $attempt = 0;
    $validDraw = false;
    
    while (!$validDraw && $attempt < $maxAttempts) {
        shuffle($receivers);
        $validDraw = true;
        
        // Check if anyone got themselves
        for ($i = 0; $i < count($givers); $i++) {
            if ($givers[$i]['id'] === $receivers[$i]['id']) {
                $validDraw = false;
                break;
            }
        }
        
        $attempt++;
    }
    
    if (!$validDraw) {
        echo json_encode(['success' => false, 'message' => 'Failed to generate valid draw']);
        return;
    }
    
    // Create draw results with unique tokens
    $draws = [];
    for ($i = 0; $i < count($givers); $i++) {
        $token = generateToken();
        $draws[] = [
            'giver_id' => $givers[$i]['id'],
            'giver_name' => $givers[$i]['name'],
            'receiver_id' => $receivers[$i]['id'],
            'receiver_name' => $receivers[$i]['name'],
            'token' => $token,
            'revealed' => false,
            'revealed_at' => null
        ];
    }
    
    saveData(DRAWS_FILE, $draws);
    
    // Update settings
    $settings['draw_generated'] = true;
    $settings['draw_date'] = date('Y-m-d H:i:s');
    saveData(SETTINGS_FILE, $settings);
    
    echo json_encode(['success' => true, 'message' => 'Draw generated successfully', 'draws' => $draws]);
}

/**
 * Get draw results (admin only)
 */
function getDraw() {
    $draws = loadData(DRAWS_FILE);
    echo json_encode(['success' => true, 'draws' => $draws]);
}

/**
 * Reveal partner for a participant
 */
function revealPartner() {
    $input = json_decode(file_get_contents('php://input'), true);
    $token = trim($input['token'] ?? '');
    
    if (empty($token)) {
        echo json_encode(['success' => false, 'message' => 'Invalid token']);
        return;
    }
    
    $draws = loadData(DRAWS_FILE);
    $found = false;
    
    foreach ($draws as &$draw) {
        if ($draw['token'] === $token) {
            $found = true;
            
            // Mark as revealed if not already
            if (!$draw['revealed']) {
                $draw['revealed'] = true;
                $draw['revealed_at'] = date('Y-m-d H:i:s');
                saveData(DRAWS_FILE, $draws);
            }
            
            // Get gift rules
            $settings = loadData(SETTINGS_FILE);
            
            echo json_encode([
                'success' => true,
                'giver_name' => $draw['giver_name'],
                'receiver_name' => $draw['receiver_name'],
                'gift_rules' => $settings['gift_value_rules'] ?? '',
                'revealed_at' => $draw['revealed_at']
            ]);
            return;
        }
    }
    
    if (!$found) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired token']);
    }
}

/**
 * Reset all data (admin only)
 */
function resetAll() {
    saveData(PARTICIPANTS_FILE, []);
    saveData(DRAWS_FILE, []);
    saveData(SETTINGS_FILE, [
        'gift_value_rules' => '',
        'draw_generated' => false,
        'draw_date' => null
    ]);
    
    echo json_encode(['success' => true, 'message' => 'All data reset successfully']);
}
?>
