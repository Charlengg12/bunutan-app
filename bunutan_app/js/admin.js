/**
 * Admin Panel JavaScript
 * Uses Supabase for all backend operations
 */

document.addEventListener('DOMContentLoaded', async function() {
    // Initialize Supabase
    await initSupabase();

    // Load initial data
    await loadParticipants();
    await loadSettings();
    await checkDrawStatus();
    await loadStatistics();

    // Set up bulk import toggle
    window.toggleBulkImport = function() {
        const form = document.getElementById('bulk-import-form');
        form.classList.toggle('active');
    };

    // Bulk import form handler
    document.getElementById('bulk-import-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        const namesText = document.getElementById('bulk-names').value.trim();

        if (!namesText) {
            toast('Please enter at least one name', 'warning');
            return;
        }

        // Parse names (split by newlines or commas)
        const names = namesText
            .split(/[\n\r,]+/)
            .map(n => n.trim())
            .filter(n => n.length > 0);

        if (names.length === 0) {
            toast('No valid names found', 'warning');
            return;
        }

        try {
            const added = await SupabaseAPI.bulkAddParticipants(names);
            const skipped = names.length - added.length;

            toast(
                `${added.length} participant(s) added${skipped > 0 ? `, ${skipped} skipped (duplicates)` : ''}`,
                'success'
            );

            document.getElementById('bulk-names').value = '';
            document.getElementById('bulk-import-form').classList.remove('active');
            await loadParticipants();
            await loadStatistics();
        } catch (error) {
            console.error('Error:', error);
            toast(error.message || 'Error importing participants', 'error');
        }
    });

    // Add participant form handler
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
            await SupabaseAPI.addParticipant(name);
            toast('Participant added successfully!', 'success');
            nameInput.value = '';
            nameInput.classList.remove('input-success');
            await loadParticipants();
            await loadStatistics();
        } catch (error) {
            console.error('Error:', error);
            toast(error.message || 'Error adding participant', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Add Participant';
        }
    });

    // Gift rules form handler
    document.getElementById('gift-rules-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        const rules = document.getElementById('gift-rules').value.trim();

        try {
            await SupabaseAPI.setGiftRules(rules);
            toast('Gift rules saved successfully!', 'success');
        } catch (error) {
            console.error('Error:', error);
            toast(error.message || 'Error saving gift rules', 'error');
        }
    });

    // Generate draw button handler
    document.getElementById('generate-draw-btn').addEventListener('click', async function() {
        if (!confirm('Are you sure you want to generate the draw? You cannot add more participants after this.')) {
            return;
        }

        const btn = this;
        btn.disabled = true;
        btn.textContent = 'Generating...';

        try {
            const draws = await SupabaseAPI.generateDraw();
            toast('Draw generated successfully!', 'success');
            displayDrawResults(draws);
            document.getElementById('add-participant-section').style.display = 'none';
            btn.disabled = true;
            btn.textContent = 'Draw Generated';
            await loadStatistics();
        } catch (error) {
            console.error('Error:', error);
            toast(error.message || 'Error generating draw', 'error');
            btn.disabled = false;
            btn.textContent = 'Generate Draw';
        }
    });

    // Reset all button handler
    document.getElementById('reset-all-btn').addEventListener('click', async function() {
        if (!confirm('Are you sure you want to reset ALL data? This cannot be undone!')) {
            return;
        }

        const btn = this;
        btn.disabled = true;
        btn.textContent = 'Resetting...';

        try {
            await SupabaseAPI.resetAll();
            toast('All data has been reset successfully!', 'success');
            setTimeout(() => location.reload(), 1500);
        } catch (error) {
            console.error('Error:', error);
            toast('Error resetting data', 'error');
            btn.disabled = false;
            btn.textContent = 'Reset All Data';
        }
    });
});

// Load participants
async function loadParticipants() {
    try {
        const participants = await SupabaseAPI.getParticipants();
        const listElement = document.getElementById('participants-list');
        const countElement = document.getElementById('participant-count');

        countElement.textContent = participants.length;

        if (participants.length === 0) {
            listElement.innerHTML = '<p class="empty-state">No participants added yet</p>';
        } else {
            listElement.innerHTML = participants.map(p =>
                `<div class="participant-item" data-name="${p.name.toLowerCase()}">
                    <span>${p.name}</span>
                    <button class="btn-delete" onclick="deleteParticipant('${p.id}', '${p.name.replace(/'/g, "\\'")}')" title="Delete participant">Delete</button>
                </div>`
            ).join('');
        }
    } catch (error) {
        console.error('Error loading participants:', error);
        toast('Error loading participants', 'error');
    }
}

// Load settings
async function loadSettings() {
    try {
        const settings = await SupabaseAPI.getSettings();
        document.getElementById('gift-rules').value = settings.gift_value_rules || '';
    } catch (error) {
        console.error('Error loading settings:', error);
    }
}

// Check draw status
async function checkDrawStatus() {
    try {
        const settings = await SupabaseAPI.getSettings();

        if (settings.draw_generated === 'true') {
            document.getElementById('add-participant-section').style.display = 'none';
            document.getElementById('generate-draw-btn').disabled = true;
            document.getElementById('generate-draw-btn').textContent = 'Draw Generated';

            // Load and display draw results
            const draws = await SupabaseAPI.getDraw();
            displayDrawResults(draws);
            await loadStatistics();
        }
    } catch (error) {
        console.error('Error checking draw status:', error);
    }
}

// Display draw results
function displayDrawResults(draws) {
    const resultsSection = document.getElementById('draw-results-section');
    const resultsElement = document.getElementById('draw-results');

    resultsSection.style.display = 'block';

    const baseUrl = window.location.origin + window.location.pathname.replace('admin.html', 'index.html');

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
                ${draw.revealed ? 'Revealed' : 'Not revealed yet'}
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
        await SupabaseAPI.deleteParticipant(id);
        toast('Participant deleted successfully', 'success');
        await loadParticipants();
        await loadStatistics();
    } catch (error) {
        console.error('Error:', error);
        toast(error.message || 'Error deleting participant', 'error');
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
        const stats = await SupabaseAPI.getStatistics();
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
    } catch (error) {
        console.error('Error loading statistics:', error);
    }
}

// Export data
window.exportData = async function() {
    try {
        const data = await SupabaseAPI.exportData();
        const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `bunutan_export_${new Date().toISOString().split('T')[0]}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        toast('Export started', 'success');
    } catch (error) {
        console.error('Error:', error);
        toast('Error exporting data', 'error');
    }
};

// Copy link helper
function copyLink(link, buttonElement) {
    copyToClipboard(link, buttonElement);
}
