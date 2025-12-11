<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bunutan - Christmas Gift Exchange</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="participant-view">
    <div class="container">
        <div class="reveal-container" id="reveal-container">
            <!-- Initial State -->
            <div id="initial-state" class="reveal-state">
                <div class="reveal-icon">🎁</div>
                <h1 class="reveal-title">Christmas Gift Exchange</h1>
                <p class="reveal-subtitle">You've been invited to participate in a secret gift exchange</p>
                <button id="reveal-btn" class="btn btn-reveal">Click to Reveal Your Secret Partner</button>
            </div>

            <!-- Loading State -->
            <div id="loading-state" class="reveal-state" style="display: none;">
                <div class="loading-spinner"></div>
                <p class="reveal-subtitle">Revealing...</p>
            </div>

            <!-- Revealed State -->
            <div id="revealed-state" class="reveal-state" style="display: none;">
                <div class="reveal-icon">🎄</div>
                <h2 class="reveal-greeting">Hello, <span id="giver-name"></span>!</h2>
                <div class="reveal-result">
                    <p class="reveal-label">You are the Secret Santa for:</p>
                    <h1 class="reveal-partner" id="receiver-name"></h1>
                </div>
                <div id="gift-rules-container" class="gift-rules-container" style="display: none;">
                    <h3>Gift Guidelines</h3>
                    <p id="gift-rules-text"></p>
                </div>
                <p class="reveal-footer">Keep it a secret! 🤫</p>
            </div>

            <!-- Error State -->
            <div id="error-state" class="reveal-state" style="display: none;">
                <div class="reveal-icon">❌</div>
                <h2 class="reveal-title">Oops!</h2>
                <p class="reveal-subtitle" id="error-message">Invalid or expired link</p>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get token from URL
            const urlParams = new URLSearchParams(window.location.search);
            const token = urlParams.get('token');

            if (!token) {
                showError('No token provided. Please use the link sent to you.');
                return;
            }

            // Set up reveal button
            document.getElementById('reveal-btn').addEventListener('click', async function() {
                await revealPartner(token);
            });
        });

        async function revealPartner(token) {
            // Show loading state
            showState('loading-state');

            try {
                const response = await fetch('api.php?action=reveal_partner', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ token })
                });

                const data = await response.json();

                if (data.success) {
                    // Populate revealed state
                    document.getElementById('giver-name').textContent = data.giver_name;
                    document.getElementById('receiver-name').textContent = data.receiver_name;

                    // Show gift rules if available
                    if (data.gift_rules && data.gift_rules.trim() !== '') {
                        document.getElementById('gift-rules-text').textContent = data.gift_rules;
                        document.getElementById('gift-rules-container').style.display = 'block';
                    }

                    // Add animation delay then show revealed state
                    setTimeout(() => {
                        showState('revealed-state');
                        animateReveal();
                    }, 800);
                } else {
                    showError(data.message || 'Failed to reveal partner');
                }
            } catch (error) {
                console.error('Error:', error);
                showError('An error occurred. Please try again.');
            }
        }

        function showState(stateId) {
            const states = ['initial-state', 'loading-state', 'revealed-state', 'error-state'];
            states.forEach(id => {
                document.getElementById(id).style.display = 'none';
            });
            document.getElementById(stateId).style.display = 'flex';
        }

        function showError(message) {
            document.getElementById('error-message').textContent = message;
            showState('error-state');
        }

        function animateReveal() {
            const revealedState = document.getElementById('revealed-state');
            revealedState.classList.add('animate-in');
        }
    </script>
</body>
</html>
