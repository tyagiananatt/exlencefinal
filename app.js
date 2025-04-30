document.addEventListener('DOMContentLoaded', function() {
    // Mode control buttons
    const proctoredModeBtn = document.getElementById('proctoredMode');
    const darkModeBtn = document.getElementById('darkMode');
    const readModeBtn = document.getElementById('readMode');
    
    let isProctoredMode = false;
    let isFullscreen = false;
    let originalTitle = document.title;

    // Proctored Mode
    proctoredModeBtn.addEventListener('click', toggleProctoredMode);

    function toggleProctoredMode() {
        if (!isProctoredMode) {
            enterProctoredMode();
        } else {
            exitProctoredMode();
        }
    }

    function enterProctoredMode() {
        const elem = document.documentElement;
        if (elem.requestFullscreen) {
            elem.requestFullscreen();
            isFullscreen = true;
            isProctoredMode = true;
            proctoredModeBtn.classList.add('active');
            showNotification('Proctored Mode Activated', 'Stay focused on your work! 🎯');
            document.title = '⚠️ Focus Mode - ' + originalTitle;
        }
    }

    function exitProctoredMode() {
        if (document.exitFullscreen) {
            document.exitFullscreen();
            isFullscreen = false;
            isProctoredMode = false;
            proctoredModeBtn.classList.remove('active');
            showNotification('Proctored Mode Deactivated', 'Good work! 👏');
            document.title = originalTitle;
        }
    }

    // Dark Mode
    darkModeBtn.addEventListener('click', toggleDarkMode);

    function toggleDarkMode() {
        document.body.classList.toggle('dark-mode');
        darkModeBtn.classList.toggle('active');
        const isDark = document.body.classList.contains('dark-mode');
        localStorage.setItem('darkMode', isDark);
        showNotification(isDark ? 'Dark Mode Enabled' : 'Dark Mode Disabled', '🌙');
    }

    // Read Mode
    readModeBtn.addEventListener('click', toggleReadMode);

    function toggleReadMode() {
        document.body.classList.toggle('read-mode');
        readModeBtn.classList.toggle('active');
        const isRead = document.body.classList.contains('read-mode');
        localStorage.setItem('readMode', isRead);
        showNotification(isRead ? 'Read Mode Enabled' : 'Read Mode Disabled', '📖');
    }

    // Handle tab visibility
    document.addEventListener('visibilitychange', function() {
        if (isProctoredMode && document.hidden) {
            showWarning();
        }
    });

    // Handle fullscreen change
    document.addEventListener('fullscreenchange', function() {
        if (!document.fullscreenElement && isProctoredMode) {
            showWarning();
        }
    });

    function showWarning() {
        const warning = document.createElement('div');
        warning.className = 'proctored-warning';
        warning.innerHTML = `
            <div class="warning-content">
                <h2>⚠️ Warning: Focus Mode Active</h2>
                <p>Please return to fullscreen to continue your work.</p>
                <button onclick="document.documentElement.requestFullscreen()">Return to Fullscreen</button>
            </div>
        `;
        document.body.appendChild(warning);

        // Auto-remove warning when returning to fullscreen
        document.addEventListener('fullscreenchange', function removeWarning() {
            if (document.fullscreenElement) {
                warning.remove();
                document.removeEventListener('fullscreenchange', removeWarning);
            }
        });
    }

    function showNotification(message, emoji = '') {
        const notification = document.createElement('div');
        notification.className = 'app-notification';
        notification.innerHTML = `${emoji} ${message}`;
        document.body.appendChild(notification);

        // Animate in
        setTimeout(() => notification.classList.add('show'), 10);

        // Animate out and remove
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    // Load saved preferences
    if (localStorage.getItem('darkMode') === 'true') {
        document.body.classList.add('dark-mode');
        darkModeBtn.classList.add('active');
    }

    if (localStorage.getItem('readMode') === 'true') {
        document.body.classList.add('read-mode');
        readModeBtn.classList.add('active');
    }
});
