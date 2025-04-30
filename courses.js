document.addEventListener('DOMContentLoaded', function() {
    let courseData = JSON.parse(localStorage.getItem('courseData')) || {
        courses: {
            'Mathematics': {
                topics: ['Algebra', 'Calculus', 'Geometry', 'Statistics'],
                progress: 0,
                totalTime: 0,
                lastStudied: null
            },
            'Physics': {
                topics: ['Mechanics', 'Thermodynamics', 'Electromagnetism', 'Quantum Physics'],
                progress: 0,
                totalTime: 0,
                lastStudied: null
            },
            'Chemistry': {
                topics: ['Organic', 'Inorganic', 'Physical Chemistry', 'Biochemistry'],
                progress: 0,
                totalTime: 0,
                lastStudied: null
            },
            'Biology': {
                topics: ['Cell Biology', 'Genetics', 'Ecology', 'Evolution'],
                progress: 0,
                totalTime: 0,
                lastStudied: null
            }
        },
        totalStudyTime: 0,
        studyStreak: 0,
        lastStudyDate: null
    };

    let activeTimer = null;
    let startTime = null;
    let currentCourse = null;

    function initializeCourses() {
        const courseContainer = document.getElementById('course-container');
        courseContainer.innerHTML = '';

        Object.entries(courseData.courses).forEach(([course, data]) => {
            const courseCard = document.createElement('div');
            courseCard.className = 'course-card';
            courseCard.innerHTML = `
                <div class="course-header">
                    <h3>${course}</h3>
                    <span class="time-spent">⏱️ ${formatTime(data.totalTime)}</span>
                </div>
                <div class="progress-bar">
                    <div class="progress" style="width: ${data.progress}%"></div>
                </div>
                <div class="topics">
                    ${data.topics.map(topic => `
                        <div class="topic">
                            <span class="topic-name">📚 ${topic}</span>
                        </div>
                    `).join('')}
                </div>
                <div class="course-footer">
                    <button class="start-study" data-course="${course}">
                        ${currentCourse === course ? 'Stop Studying' : 'Start Studying'}
                    </button>
                    <span class="last-studied">
                        ${data.lastStudied ? `Last studied: ${formatDate(data.lastStudied)}` : 'Not started yet'}
                    </span>
                </div>
            `;
            courseContainer.appendChild(courseCard);

            // Add click handler for study button
            const studyButton = courseCard.querySelector('.start-study');
            studyButton.addEventListener('click', () => toggleStudy(course));
        });

        updateStudyStats();
    }

    function toggleStudy(course) {
        if (currentCourse === course) {
            stopStudying();
        } else {
            if (currentCourse) {
                stopStudying();
            }
            startStudying(course);
        }
    }

    function startStudying(course) {
        currentCourse = course;
        startTime = Date.now();
        
        // Update button text
        document.querySelectorAll('.start-study').forEach(btn => {
            if (btn.dataset.course === course) {
                btn.textContent = 'Stop Studying';
                btn.classList.add('active');
            }
        });

        // Start timer
        const timerDisplay = document.createElement('div');
        timerDisplay.id = 'active-timer';
        timerDisplay.className = 'study-timer';
        document.body.appendChild(timerDisplay);

        activeTimer = setInterval(() => {
            const elapsed = Math.floor((Date.now() - startTime) / 1000);
            timerDisplay.textContent = `Studying ${course}: ${formatTime(elapsed)}`;
        }, 1000);

        // Request notification permission if needed
        if ("Notification" in window) {
            Notification.requestPermission();
        }

        showNotification(`Started studying ${course}! 📚`);
    }

    function stopStudying() {
        if (!currentCourse || !startTime) return;

        clearInterval(activeTimer);
        const studyTime = Math.floor((Date.now() - startTime) / 1000);
        
        // Update course data
        courseData.courses[currentCourse].totalTime += studyTime;
        courseData.courses[currentCourse].lastStudied = new Date().toISOString();
        courseData.courses[currentCourse].progress = Math.min(100,
            Math.round((courseData.courses[currentCourse].totalTime / 3600) * 10));
        
        // Update total study time
        courseData.totalStudyTime += studyTime;
        
        // Update streak
        const today = new Date().toISOString().split('T')[0];
        if (!courseData.lastStudyDate || courseData.lastStudyDate !== today) {
            courseData.studyStreak++;
            courseData.lastStudyDate = today;
        }

        // Save to localStorage
        localStorage.setItem('courseData', JSON.stringify(courseData));
        
        // Update progress data
        updateProgressData(currentCourse, studyTime);

        // Reset state
        const timerDisplay = document.getElementById('active-timer');
        if (timerDisplay) {
            timerDisplay.remove();
        }

        // Update button text
        document.querySelectorAll('.start-study').forEach(btn => {
            btn.textContent = 'Start Studying';
            btn.classList.remove('active');
        });

        showNotification(`Great job! You studied ${currentCourse} for ${formatTime(studyTime)}! 🎉`);
        
        currentCourse = null;
        startTime = null;
        activeTimer = null;

        // Refresh display
        initializeCourses();
    }

    function updateProgressData(course, studyTime) {
        let progressData = JSON.parse(localStorage.getItem('progressData')) || {
            studyHours: {},
            subjects: {},
            streak: 0,
            weeklyGoal: 30
        };

        const today = new Date().toISOString().split('T')[0];
        if (!progressData.studyHours[today]) {
            progressData.studyHours[today] = {};
        }

        if (!progressData.subjects[course]) {
            progressData.subjects[course] = {
                totalHours: 0,
                lastStudy: today,
                progress: 0
            };
        }

        const hours = studyTime / 3600; // Convert seconds to hours
        progressData.subjects[course].totalHours += hours;
        progressData.subjects[course].lastStudy = today;
        progressData.subjects[course].progress = Math.min(100,
            Math.round((progressData.subjects[course].totalHours / 40) * 100));

        localStorage.setItem('progressData', JSON.stringify(progressData));
    }

    function updateStudyStats() {
        const statsContainer = document.getElementById('study-stats');
        if (!statsContainer) return;

        const totalHours = Math.floor(courseData.totalStudyTime / 3600);
        const averageDaily = courseData.totalStudyTime > 0 ? 
            (totalHours / (Object.values(courseData.courses)
                .filter(c => c.lastStudied)
                .length || 1)).toFixed(1) : 0;

        statsContainer.innerHTML = `
            <div class="stat-card">
                <h4>Total Study Time</h4>
                <p>${formatTime(courseData.totalStudyTime)}</p>
            </div>
            <div class="stat-card">
                <h4>Study Streak</h4>
                <p>🔥 ${courseData.studyStreak} days</p>
            </div>
            <div class="stat-card">
                <h4>Daily Average</h4>
                <p>⌛ ${averageDaily} hours</p>
            </div>
        `;
    }

    function formatTime(seconds) {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;

        if (hours > 0) {
            return `${hours}h ${minutes}m`;
        } else if (minutes > 0) {
            return `${minutes}m ${secs}s`;
        } else {
            return `${secs}s`;
        }
    }

    function formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('show');
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, 2000);
        }, 100);

        // Show browser notification if studying
        if (currentCourse && "Notification" in window && 
            Notification.permission === "granted") {
            new Notification("Study Session", {
                body: message,
                icon: '/path/to/icon.png'
            });
        }
    }

    // Initialize
    initializeCourses();

    // Auto-save study progress every minute
    setInterval(() => {
        if (currentCourse && startTime) {
            localStorage.setItem('courseData', JSON.stringify(courseData));
        }
    }, 60000);

    // Handle page unload
    window.addEventListener('beforeunload', () => {
        if (currentCourse) {
            stopStudying();
        }
    });
});
