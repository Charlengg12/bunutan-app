<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Admin Panel - Bunutan</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <button class="theme-toggle" onclick="toggleDarkMode()" title="Toggle dark mode" aria-label="Toggle dark mode">🌙</button>
            <h1><a href="../index.html">🎄 Bunutan Admin Panel</a></h1>
            <p class="subtitle">Christmas Gift Exchange Management</p>
        </header>

        <main class="admin-main">
            <!-- Statistics Dashboard -->
            <section class="card" id="statistics-section" style="display: none;">
                <h2>📊 Statistics</h2>
                <div class="stats-grid" id="stats-grid"></div>
                <div class="progress-container">
                    <div class="progress-bar">
                        <div class="progress-fill" id="progress-fill" style="width: 0%"></div>
                    </div>
                </div>
            </section>

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
                            minlength="1"
                        >
                        <button type="submit" class="btn btn-primary">Add Participant</button>
                    </div>
                    <div class="error-message"></div>
                </form>
                <div class="bulk-import-section">
                    <button type="button" class="bulk-import-toggle" onclick="toggleBulkImport()">📋 Bulk Import (one per line or comma-separated)</button>
                    <form id="bulk-import-form" class="bulk-import-form">
                        <div class="form-group">
                            <textarea 
                                id="bulk-names" 
                                placeholder="Enter names, one per line or separated by commas&#10;Example:&#10;John Doe&#10;Jane Smith&#10;Bob Johnson"
                                rows="6"
                            ></textarea>
                        </div>
                        <button type="submit" class="btn btn-secondary">Import All</button>
                    </form>
                </div>
                <div id="add-participant-message" class="message"></div>
            </section>

            <!-- Participants List -->
            <section class="card">
                <h2>Participants (<span id="participant-count">0</span>)</h2>
                <div class="search-box">
                    <span class="search-icon">🔍</span>
                    <input 
                        type="text" 
                        id="participant-search" 
                        placeholder="Search participants..."
                        oninput="filterParticipants()"
                    >
                </div>
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
                <div style="margin-bottom: 16px;">
                    <button onclick="exportData()" class="btn btn-export">📥 Export Data</button>
                </div>
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
            loadStatistics();
            
            // Set up bulk import toggle
            window.toggleBulkImport = function() {
                const form = document.getElementById('bulk-import-form');
                form.classList.toggle('active');
            };
            
            // Bulk import form
            document.getElementById('bulk-import-form').addEventListener('submit', async function(e) {
                e.preventDefault();
                const namesText = document.getElementById('bulk-names').value.trim();
                
                if (!namesText) {
                    toast('Please enter at least one name', 'warning');
                    return;
                }
                
                try {
                    const response = await fetch('api.php?action=bulk_add_participants', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ names: namesText })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        toast(`${data.added_count} participant(s) added${data.skipped_count > 0 ? `, ${data.skipped_count} skipped (duplicates)` : ''}`, 'success');
                        document.getElementById('bulk-names').value = '';
                        document.getElementById('bulk-import-form').classList.remove('active');
                        loadParticipants();
                        loadStatistics();
                    } else {
                        toast(data.message, 'error');
                    }
                } catch (error) {
                    toast('Error importing participants', 'error');
                }
            });

            // Add participant form
            document.getElementById('add-participant-form').addEventListener('submit', async function(e) {
                e.preventDefault();
                const nameInput = document.getElementById('participant-name');
                const name = nameInput.value.trim();
                
                if (!validateForm(nameInput, 1)) {
                    toast('Please enter a valid name', 'warning');
                    return;
                }

                const submitBtn = e.target.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.textContent = 'Adding...';

                try {
                    const response = await fetch('api.php?action=add_participant', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ name })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        toast('Participant added successfully!', 'success');
                        nameInput.value = '';
                        nameInput.classList.remove('input-success');
                        loadParticipants();
                        loadStatistics();
                    } else {
                        toast(data.message, 'error');
                    }
                } catch (error) {
                    toast('Error adding participant', 'error');
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Add Participant';
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
                        toast('Gift rules saved successfully!', 'success');
                    } else {
                        toast(data.message, 'error');
                    }
                } catch (error) {
                    toast('Error saving gift rules', 'error');
                }
            });

            // Generate draw button
            document.getElementById('generate-draw-btn').addEventListener('click', async function() {
                if (!confirm('Are you sure you want to generate the draw? You cannot add more participants after this.')) {
                    return;
                }

                const btn = this;
                btn.disabled = true;
                btn.textContent = 'Generating...';

                try {
                    const response = await fetch('api.php?action=generate_draw', {
                        method: 'POST'
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        toast('Draw generated successfully! 🎉', 'success');
                        displayDrawResults(data.draws);
                        document.getElementById('add-participant-section').style.display = 'none';
                        btn.disabled = true;
                        btn.textContent = 'Draw Generated';
                        loadStatistics();
                    } else {
                        toast(data.message, 'error');
                        btn.disabled = false;
                        btn.textContent = 'Generate Draw';
                    }
                } catch (error) {
                    toast('Error generating draw', 'error');
                    btn.disabled = false;
                    btn.textContent = 'Generate Draw';
                }
            });

            // Reset all button
            document.getElementById('reset-all-btn').addEventListener('click', async function() {
                if (!confirm('Are you sure you want to reset ALL data? This cannot be undone!')) {
                    return;
                }

                const btn = this;
                btn.disabled = true;
                btn.textContent = 'Resetting...';

                try {
                    const response = await fetch('api.php?action=reset_all', {
                        method: 'POST'
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        toast('All data has been reset successfully!', 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        toast('Error resetting data', 'error');
                        btn.disabled = false;
                        btn.textContent = 'Reset All Data';
                    }
                } catch (error) {
                    toast('Error resetting data', 'error');
                    btn.disabled = false;
                    btn.textContent = 'Reset All Data';
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
                            `<div class="participant-item" data-name="${p.name.toLowerCase()}">
                                <span>${p.name}</span>
                                <button class="btn-delete" onclick="deleteParticipant('${p.id}', '${p.name.replace(/'/g, "\\'")}')" title="Delete participant">🗑️</button>
                            </div>`
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
                        loadStatistics();
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
            
            resultsElement.innerHTML = draws.map(draw => {
                const link = `${baseUrl}?token=${draw.token}`;
                return `
                <div class="draw-result-item">
                    <div class="draw-result-name">${draw.giver_name}</div>
                    <div class="draw-result-link">
                        <input 
                            type="text" 
                            value="${link}" 
                            readonly 
                            class="link-input"
                            onclick="this.select()"
                        >
                        <button class="btn-copy" onclick="copyLink('${link.replace(/'/g, "\\'")}', this)">Copy</button>
                    </div>
                    <div class="draw-result-status ${draw.revealed ? 'revealed' : 'not-revealed'}">
                        ${draw.revealed ? '✓ Revealed' : '○ Not revealed yet'}
                    </div>
                </div>
            `;
            }).join('');
        }

        // Delete participant
        window.deleteParticipant = async function(id, name) {
            if (!confirm(`Are you sure you want to delete "${name}"?`)) {
                return;
            }

            try {
                const response = await fetch('api.php?action=delete_participant', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    toast('Participant deleted successfully', 'success');
                    loadParticipants();
                    loadStatistics();
                } else {
                    toast(data.message, 'error');
                }
            } catch (error) {
                toast('Error deleting participant', 'error');
            }
        };

        // Filter participants
        window.filterParticipants = function() {
            const searchTerm = document.getElementById('participant-search').value.toLowerCase();
            const items = document.querySelectorAll('.participant-item');
            
            items.forEach(item => {
                const name = item.getAttribute('data-name') || '';
                if (name.includes(searchTerm)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        };

        // Load statistics
        async function loadStatistics() {
            try {
                const response = await fetch('api.php?action=get_statistics');
                const data = await response.json();
                
                if (data.success) {
                    const stats = data.statistics;
                    const statsSection = document.getElementById('statistics-section');
                    const statsGrid = document.getElementById('stats-grid');
                    const progressFill = document.getElementById('progress-fill');
                    
                    if (stats.draw_generated) {
                        statsSection.style.display = 'block';
                        statsGrid.innerHTML = `
                            <div class="stat-card">
                                <div class="stat-value">${stats.total_participants}</div>
                                <div class="stat-label">Total Participants</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value">${stats.revealed_count}</div>
                                <div class="stat-label">Revealed</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value">${stats.pending_reveals}</div>
                                <div class="stat-label">Pending</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value">${stats.completion_percentage}%</div>
                                <div class="stat-label">Complete</div>
                            </div>
                        `;
                        progressFill.style.width = stats.completion_percentage + '%';
                    } else {
                        statsSection.style.display = 'none';
                    }
                }
            } catch (error) {
                console.error('Error loading statistics:', error);
            }
        }

        // Export data
        window.exportData = function() {
            window.open('api.php?action=export_data', '_blank');
            toast('Export started', 'info');
        };

        function copyLink(link, buttonElement) {
            copyToClipboard(link, buttonElement);
        }
    </script>
</body>
</html>
