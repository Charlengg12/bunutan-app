<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Bunutan</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>🎄 Bunutan Admin Panel</h1>
            <p class="subtitle">Christmas Gift Exchange Management</p>
        </header>

        <main class="admin-main">
            <!-- Add Participant Section -->
            <section class="card" id="add-participant-section">
                <h2>Add Participants</h2>
                <form id="add-participant-form">
                    <div class="form-group">
                        <input 
                            type="text" 
                            id="participant-name" 
                            placeholder="Enter participant name"
                            required
                        >
                        <button type="submit" class="btn btn-primary">Add Participant</button>
                    </div>
                </form>
                <div id="add-participant-message" class="message"></div>
            </section>

            <!-- Participants List -->
            <section class="card">
                <h2>Participants (<span id="participant-count">0</span>)</h2>
                <div id="participants-list" class="participants-list">
                    <p class="empty-state">No participants added yet</p>
                </div>
            </section>

            <!-- Gift Value Rules Section -->
            <section class="card">
                <h2>Gift Value Rules</h2>
                <form id="gift-rules-form">
                    <div class="form-group">
                        <textarea 
                            id="gift-rules" 
                            placeholder="e.g., Gift value should be between $10 - $30"
                            rows="4"
                        ></textarea>
                        <button type="submit" class="btn btn-secondary">Save Rules</button>
                    </div>
                </form>
                <div id="gift-rules-message" class="message"></div>
            </section>

            <!-- Generate Draw Section -->
            <section class="card">
                <h2>Generate Draw</h2>
                <p class="info-text">Once generated, you cannot add more participants. Make sure all participants are added first.</p>
                <button id="generate-draw-btn" class="btn btn-success">Generate Draw</button>
                <div id="generate-draw-message" class="message"></div>
            </section>

            <!-- Draw Results Section -->
            <section class="card" id="draw-results-section" style="display: none;">
                <h2>Draw Results & Participant Links</h2>
                <p class="info-text">Share these unique links with each participant. They can click to reveal their secret gift partner.</p>
                <div id="draw-results" class="draw-results"></div>
            </section>

            <!-- Reset Section -->
            <section class="card danger-zone">
                <h2>⚠️ Danger Zone</h2>
                <p class="info-text">Reset all data including participants, draw results, and settings. This action cannot be undone.</p>
                <button id="reset-all-btn" class="btn btn-danger">Reset All Data</button>
            </section>
        </main>
    </div>

    <script src="js/main.js"></script>
    <script>
        // Admin-specific functionality
        document.addEventListener('DOMContentLoaded', function() {
            loadParticipants();
            loadSettings();
            checkDrawStatus();

            // Add participant form
            document.getElementById('add-participant-form').addEventListener('submit', async function(e) {
                e.preventDefault();
                const nameInput = document.getElementById('participant-name');
                const name = nameInput.value.trim();
                
                if (!name) return;

                try {
                    const response = await fetch('api.php?action=add_participant', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ name })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        showMessage('add-participant-message', 'Participant added successfully!', 'success');
                        nameInput.value = '';
                        loadParticipants();
                    } else {
                        showMessage('add-participant-message', data.message, 'error');
                    }
                } catch (error) {
                    showMessage('add-participant-message', 'Error adding participant', 'error');
                }
            });

            // Gift rules form
            document.getElementById('gift-rules-form').addEventListener('submit', async function(e) {
                e.preventDefault();
                const rules = document.getElementById('gift-rules').value.trim();

                try {
                    const response = await fetch('api.php?action=set_gift_rules', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ rules })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        showMessage('gift-rules-message', 'Gift rules saved successfully!', 'success');
                    } else {
                        showMessage('gift-rules-message', data.message, 'error');
                    }
                } catch (error) {
                    showMessage('gift-rules-message', 'Error saving gift rules', 'error');
                }
            });

            // Generate draw button
            document.getElementById('generate-draw-btn').addEventListener('click', async function() {
                if (!confirm('Are you sure you want to generate the draw? You cannot add more participants after this.')) {
                    return;
                }

                try {
                    const response = await fetch('api.php?action=generate_draw', {
                        method: 'POST'
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        showMessage('generate-draw-message', 'Draw generated successfully!', 'success');
                        displayDrawResults(data.draws);
                        document.getElementById('add-participant-section').style.display = 'none';
                        document.getElementById('generate-draw-btn').disabled = true;
                    } else {
                        showMessage('generate-draw-message', data.message, 'error');
                    }
                } catch (error) {
                    showMessage('generate-draw-message', 'Error generating draw', 'error');
                }
            });

            // Reset all button
            document.getElementById('reset-all-btn').addEventListener('click', async function() {
                if (!confirm('Are you sure you want to reset ALL data? This cannot be undone!')) {
                    return;
                }

                try {
                    const response = await fetch('api.php?action=reset_all', {
                        method: 'POST'
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        alert('All data has been reset successfully!');
                        location.reload();
                    }
                } catch (error) {
                    alert('Error resetting data');
                }
            });
        });

        async function loadParticipants() {
            try {
                const response = await fetch('api.php?action=get_participants');
                const data = await response.json();
                
                if (data.success) {
                    const participants = data.participants;
                    const listElement = document.getElementById('participants-list');
                    const countElement = document.getElementById('participant-count');
                    
                    countElement.textContent = participants.length;
                    
                    if (participants.length === 0) {
                        listElement.innerHTML = '<p class="empty-state">No participants added yet</p>';
                    } else {
                        listElement.innerHTML = participants.map(p => 
                            `<div class="participant-item">${p.name}</div>`
                        ).join('');
                    }
                }
            } catch (error) {
                console.error('Error loading participants:', error);
            }
        }

        async function loadSettings() {
            try {
                const response = await fetch('api.php?action=get_settings');
                const data = await response.json();
                
                if (data.success) {
                    const settings = data.settings;
                    document.getElementById('gift-rules').value = settings.gift_value_rules || '';
                }
            } catch (error) {
                console.error('Error loading settings:', error);
            }
        }

        async function checkDrawStatus() {
            try {
                const response = await fetch('api.php?action=get_settings');
                const data = await response.json();
                
                if (data.success && data.settings.draw_generated) {
                    document.getElementById('add-participant-section').style.display = 'none';
                    document.getElementById('generate-draw-btn').disabled = true;
                    
                    // Load and display draw results
                    const drawResponse = await fetch('api.php?action=get_draw');
                    const drawData = await drawResponse.json();
                    
                    if (drawData.success) {
                        displayDrawResults(drawData.draws);
                    }
                }
            } catch (error) {
                console.error('Error checking draw status:', error);
            }
        }

        function displayDrawResults(draws) {
            const resultsSection = document.getElementById('draw-results-section');
            const resultsElement = document.getElementById('draw-results');
            
            resultsSection.style.display = 'block';
            
            const baseUrl = window.location.origin + window.location.pathname.replace('admin.php', 'index.php');
            
            resultsElement.innerHTML = draws.map(draw => `
                <div class="draw-result-item">
                    <div class="draw-result-name">${draw.giver_name}</div>
                    <div class="draw-result-link">
                        <input 
                            type="text" 
                            value="${baseUrl}?token=${draw.token}" 
                            readonly 
                            class="link-input"
                            onclick="this.select()"
                        >
                        <button class="btn-copy" onclick="copyLink('${baseUrl}?token=${draw.token}')">Copy</button>
                    </div>
                    <div class="draw-result-status ${draw.revealed ? 'revealed' : 'not-revealed'}">
                        ${draw.revealed ? '✓ Revealed' : '○ Not revealed yet'}
                    </div>
                </div>
            `).join('');
        }

        function copyLink(link) {
            navigator.clipboard.writeText(link).then(() => {
                alert('Link copied to clipboard!');
            }).catch(() => {
                alert('Failed to copy link');
            });
        }

        function showMessage(elementId, message, type) {
            const element = document.getElementById(elementId);
            element.textContent = message;
            element.className = `message ${type}`;
            element.style.display = 'block';
            
            setTimeout(() => {
                element.style.display = 'none';
            }, 5000);
        }
    </script>
</body>
</html>
