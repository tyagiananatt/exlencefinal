document.addEventListener('DOMContentLoaded', function() {
    const startButton = document.getElementById('startFocus');
    const pauseButton = document.getElementById('takePause');
    const timerDisplay = document.getElementById('timer');
    const notification = document.getElementById('notification');
    let isFullscreen = false;
    let focusTimer;
    let startTime;
    let totalFocusTime = parseInt(localStorage.getItem('totalFocusTime')) || 0;
    let streakDays = parseInt(localStorage.getItem('streakDays')) || 0;
    let lastFocusDate = localStorage.getItem('lastFocusDate');

    // Initialize stats
    updateStats();

    startButton.addEventListener('click', toggleFocusMode);
    pauseButton.addEventListener('click', takePause);

    function toggleFocusMode() {
        if (!isFullscreen) {
            enterFocusMode();
        } else {
            exitFocusMode();
        }
    }

    function enterFocusMode() {
        const elem = document.documentElement;
        if (elem.requestFullscreen) {
            elem.requestFullscreen();
        }
        isFullscreen = true;
        startButton.textContent = 'Exit Focus Mode';
        startButton.classList.add('active');
        startTimer();
        showNotification('Focus Mode activated! Stay focused! ');
        
        // Update streak
        const today = new Date().toDateString();
        if (lastFocusDate !== today) {
            streakDays++;
            localStorage.setItem('streakDays', streakDays);
            localStorage.setItem('lastFocusDate', today);
        }
    }

    function exitFocusMode() {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        }
        isFullscreen = false;
        startButton.textContent = 'Start Focus Mode';
        startButton.classList.remove('active');
        stopTimer();
        showNotification('Great work! Focus session completed! ');
    }

    function startTimer() {
        startTime = new Date();
        focusTimer = setInterval(updateTimer, 1000);
    }

    function stopTimer() {
        clearInterval(focusTimer);
        const endTime = new Date();
        const focusDuration = Math.floor((endTime - startTime) / 1000 / 60); // Convert to minutes
        totalFocusTime += focusDuration;
        localStorage.setItem('totalFocusTime', totalFocusTime);
        updateStats();
    }

    function updateTimer() {
        const currentTime = new Date();
        const elapsedTime = Math.floor((currentTime - startTime) / 1000);
        const hours = Math.floor(elapsedTime / 3600);
        const minutes = Math.floor((elapsedTime % 3600) / 60);
        const seconds = elapsedTime % 60;
        timerDisplay.textContent = `${padNumber(hours)}:${padNumber(minutes)}:${padNumber(seconds)}`;
    }

    function padNumber(number) {
        return number.toString().padStart(2, '0');
    }

    function takePause() {
        if (isFullscreen) {
            showNotification('Taking a 5-minute break! ');
            setTimeout(() => {
                showNotification('Break time is over! Back to focus! ');
            }, 300000); // 5 minutes
        }
    }

    function updateStats() {
        document.getElementById('todayTime').textContent = `${totalFocusTime} minutes`;
        document.getElementById('streaks').textContent = `${streakDays} days`;
        const productivityScore = Math.min(100, Math.floor((totalFocusTime / 240) * 100)); // Based on 4 hours ideal focus time
        document.getElementById('score').textContent = `${productivityScore}%`;
    }

    function showNotification(message) {
        notification.textContent = message;
        notification.classList.add('show');
        setTimeout(() => {
            notification.classList.remove('show');
        }, 3000);
    }

    // Handle fullscreen change
    document.addEventListener('fullscreenchange', function() {
        if (!document.fullscreenElement && isFullscreen) {
            exitFocusMode();
        }
    });

    // Prevent tab switching
    document.addEventListener('visibilitychange', function() {
        if (document.hidden && isFullscreen) {
            showNotification('Stay focused! Keep this tab open! ');
        }
    });
});
