class ProctoringSystem {
    constructor() {
        this.isProctored = false;
        this.warningCount = 0;
        this.maxWarnings = 3;
        
        this.initializeElements();
        this.setupEventListeners();
    }

    initializeElements() {
        this.proctorButton = document.getElementById('proctorButton');
        this.proctorStatus = document.getElementById('proctorStatus');
        this.warningModal = document.getElementById('warningModal');
        this.warningMessage = document.getElementById('warningMessage');
        this.acknowledgeButton = document.getElementById('acknowledgeWarning');
    }

    setupEventListeners() {
        // Proctor button click handler
        this.proctorButton.addEventListener('click', () => this.toggleProctoredMode());

        // Tab visibility change detection
        document.addEventListener('visibilitychange', () => {
            if (this.isProctored && document.hidden) {
                this.handleWarning('Tab switching detected! Please return to the exam tab.');
            }
        });

        // Fullscreen change detection
        document.addEventListener('fullscreenchange', () => {
            if (this.isProctored && !document.fullscreenElement) {
                this.handleWarning('Fullscreen mode exited! Please return to fullscreen mode.');
                this.requestFullscreen();
            }
        });

        // Warning acknowledgment
        this.acknowledgeButton.addEventListener('click', () => {
            this.warningModal.style.display = 'none';
        });

        // Prevent keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (this.isProctored) {
                // Prevent alt+tab, windows key, alt+f4, etc.
                if (e.altKey || e.metaKey || e.ctrlKey) {
                    e.preventDefault();
                    this.handleWarning('Keyboard shortcuts are disabled in proctored mode!');
                }
            }
        });
    }

    async toggleProctoredMode() {
        if (!this.isProctored) {
            try {
                await this.enableProctoredMode();
            } catch (error) {
                this.showWarning('Failed to enable proctored mode: ' + error.message);
            }
        } else {
            this.disableProctoredMode();
        }
    }

    async enableProctoredMode() {
        await this.requestFullscreen();
        this.isProctored = true;
        this.updateStatus();
        this.proctorButton.classList.add('active');
        this.showNotification('Proctored mode enabled');
    }

    disableProctoredMode() {
        if (document.fullscreenElement) {
            document.exitFullscreen();
        }
        this.isProctored = false;
        this.updateStatus();
        this.proctorButton.classList.remove('active');
        this.showNotification('Proctored mode disabled');
    }

    async requestFullscreen() {
        try {
            await document.documentElement.requestFullscreen();
        } catch (error) {
            throw new Error('Fullscreen request failed');
        }
    }

    handleWarning(message) {
        this.warningCount++;
        this.showWarning(message);
        
        if (this.warningCount >= this.maxWarnings) {
            this.disableProctoredMode();
            this.showWarning('Maximum warnings reached. Proctored mode disabled.');
            this.warningCount = 0;
        }
    }

    showWarning(message) {
        this.warningMessage.textContent = message;
        this.warningModal.style.display = 'block';
    }

    showNotification(message) {
        const notification = document.createElement('div');
        notification.className = 'fullscreen-indicator';
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    updateStatus() {
        this.proctorStatus.textContent = this.isProctored ? 'Proctored' : 'Not Proctored';
        this.proctorStatus.className = 'proctor-status ' + (this.isProctored ? 'active' : 'inactive');
    }
}

// Initialize proctoring system when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    const proctoring = new ProctoringSystem();
});
