let player;
let currentVideoIndex = 0;
let playlist = [];

// Initialize YouTube API
function onYouTubeIframeAPIReady() {
    loadPlaylist();
    initializePlayer();
}

// Load sample playlist (replace with your actual playlist loading logic)
function loadPlaylist() {
    playlist = [
        {
            videoId: 'VIDEO_ID_1',
            title: 'Introduction to the Course',
            description: 'Welcome to the course! In this video, we will cover the basics...',
            duration: '10:00'
        },
        {
            videoId: 'VIDEO_ID_2',
            title: 'Chapter 1: Getting Started',
            description: 'In this chapter, we will learn the fundamentals...',
            duration: '15:30'
        }
        // Add more videos as needed
    ];
    renderPlaylist();
}

// Initialize the YouTube player
function initializePlayer() {
    player = new YT.Player('videoPlayer', {
        height: '100%',
        width: '100%',
        videoId: playlist[currentVideoIndex].videoId,
        playerVars: {
            'playsinline': 1,
            'rel': 0,
            'modestbranding': 1
        },
        events: {
            'onReady': onPlayerReady,
            'onStateChange': onPlayerStateChange
        }
    });
}

function onPlayerReady(event) {
    updateVideoInfo();
    loadSavedNotes();
}

function onPlayerStateChange(event) {
    // Handle player state changes if needed
}

// Update video information
function updateVideoInfo() {
    const currentVideo = playlist[currentVideoIndex];
    document.querySelector('.video-info h2').textContent = currentVideo.title;
    document.querySelector('.video-info p').textContent = currentVideo.description;
}

// Render playlist items
function renderPlaylist() {
    const playlistContainer = document.getElementById('videoPlaylist');
    playlistContainer.innerHTML = '';

    playlist.forEach((video, index) => {
        const item = document.createElement('div');
        item.className = `playlist-item ${index === currentVideoIndex ? 'active' : ''}`;
        item.innerHTML = `
            <h3>${video.title}</h3>
            <p>${video.description}</p>
            <div class="duration">${video.duration}</div>
        `;
        item.addEventListener('click', () => playVideo(index));
        playlistContainer.appendChild(item);
    });
}

// Play selected video
function playVideo(index) {
    if (index >= 0 && index < playlist.length) {
        currentVideoIndex = index;
        player.loadVideoById(playlist[index].videoId);
        updateVideoInfo();
        renderPlaylist();
        saveNotes(); // Save notes before switching videos
        loadSavedNotes(); // Load notes for the new video
    }
}

// Navigate through playlist
function playPrevious() {
    if (currentVideoIndex > 0) {
        playVideo(currentVideoIndex - 1);
    }
}

function playNext() {
    if (currentVideoIndex < playlist.length - 1) {
        playVideo(currentVideoIndex + 1);
    }
}

// Handle notes
function saveNotes() {
    const notes = document.getElementById('videoNotes').value;
    const videoId = playlist[currentVideoIndex].videoId;
    localStorage.setItem(`notes_${videoId}`, notes);
    showNotification('Notes saved successfully!');
}

function loadSavedNotes() {
    const videoId = playlist[currentVideoIndex].videoId;
    const savedNotes = localStorage.getItem(`notes_${videoId}`) || '';
    document.getElementById('videoNotes').value = savedNotes;
}

// Show notification
function showNotification(message) {
    const notification = document.querySelector('.notification');
    notification.textContent = message;
    notification.classList.add('show');
    
    setTimeout(() => {
        notification.classList.remove('show');
    }, 3000);
}

// Event listeners
document.addEventListener('DOMContentLoaded', () => {
    // Load YouTube API
    const tag = document.createElement('script');
    tag.src = 'https://www.youtube.com/iframe_api';
    const firstScriptTag = document.getElementsByTagName('script')[0];
    firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

    // Add event listeners for controls
    document.getElementById('prevButton').addEventListener('click', playPrevious);
    document.getElementById('nextButton').addEventListener('click', playNext);
    document.getElementById('saveNotesButton').addEventListener('click', saveNotes);

    // Auto-save notes when typing stops
    let saveTimeout;
    document.getElementById('videoNotes').addEventListener('input', () => {
        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(saveNotes, 1000);
    });
});

class CoursePlayer {
    constructor() {
        this.currentVideoIndex = 0;
        this.playlist = [];
        this.player = null;
        this.notes = JSON.parse(localStorage.getItem('videoNotes')) || {};

        // DOM Elements
        this.videoPlayer = document.getElementById('videoPlayer');
        this.videoTitle = document.getElementById('videoTitle');
        this.videoDescription = document.getElementById('videoDescription');
        this.playlistContainer = document.getElementById('videoPlaylist');
        this.prevButton = document.getElementById('prevVideo');
        this.nextButton = document.getElementById('nextVideo');
        this.notesInput = document.getElementById('videoNotes');
        this.saveNotesBtn = document.getElementById('saveNotes');
        this.searchInput = document.getElementById('searchVideos');
        this.notification = document.querySelector('.notification');

        // Event Listeners
        this.prevButton.addEventListener('click', () => this.playPreviousVideo());
        this.nextButton.addEventListener('click', () => this.playNextVideo());
        this.saveNotesBtn.addEventListener('click', () => this.saveNotes());
        this.notesInput.addEventListener('input', () => this.autoSaveNotes());
        this.searchInput.addEventListener('input', (e) => this.filterPlaylist(e.target.value));

        // Initialize YouTube API
        this.loadYouTubeAPI();
    }

    loadYouTubeAPI() {
        // Load the YouTube IFrame API
        const tag = document.createElement('script');
        tag.src = 'https://www.youtube.com/iframe_api';
        const firstScriptTag = document.getElementsByTagName('script')[0];
        firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

        // Set up the callback
        window.onYouTubeIframeAPIReady = () => this.initializePlayer();
    }

    initializePlayer() {
        this.player = new YT.Player('videoPlayer', {
            height: '100%',
            width: '100%',
            videoId: '',
            playerVars: {
                playsinline: 1,
                modestbranding: 1,
                rel: 0
            },
            events: {
                'onStateChange': (event) => this.onPlayerStateChange(event)
            }
        });
    }

    setPlaylist(playlistData) {
        this.playlist = playlistData;
        this.renderPlaylist();
        if (this.playlist.length > 0) {
            this.loadVideo(0);
        }
    }

    renderPlaylist() {
        // Clear existing playlist items except header
        const header = this.playlistContainer.querySelector('.playlist-header');
        this.playlistContainer.innerHTML = '';
        this.playlistContainer.appendChild(header);

        // Add playlist items
        this.playlist.forEach((video, index) => {
            const item = document.createElement('div');
            item.className = `playlist-item ${index === this.currentVideoIndex ? 'active' : ''}`;
            item.innerHTML = `
                <img class="thumbnail" src="https://img.youtube.com/vi/${video.videoId}/mqdefault.jpg" alt="${video.title}">
                <div class="info">
                    <div class="title">${video.title}</div>
                    <div class="duration">${video.duration || 'Loading...'}</div>
                </div>
            `;
            item.addEventListener('click', () => this.loadVideo(index));
            this.playlistContainer.appendChild(item);
        });
    }

    loadVideo(index) {
        if (index >= 0 && index < this.playlist.length) {
            this.currentVideoIndex = index;
            const video = this.playlist[index];
            
            // Update video player
            if (this.player && this.player.loadVideoById) {
                this.player.loadVideoById(video.videoId);
            }

            // Update UI
            this.videoTitle.textContent = video.title;
            this.videoDescription.textContent = video.description;
            this.notesInput.value = this.notes[video.videoId] || '';

            // Update playlist UI
            const items = this.playlistContainer.querySelectorAll('.playlist-item');
            items.forEach((item, i) => {
                item.classList.toggle('active', i === index);
            });

            // Update navigation buttons
            this.prevButton.disabled = index === 0;
            this.nextButton.disabled = index === this.playlist.length - 1;

            // Save last watched video
            localStorage.setItem('lastWatchedVideo', index);
        }
    }

    playNextVideo() {
        if (this.currentVideoIndex < this.playlist.length - 1) {
            this.loadVideo(this.currentVideoIndex + 1);
        }
    }

    playPreviousVideo() {
        if (this.currentVideoIndex > 0) {
            this.loadVideo(this.currentVideoIndex - 1);
        }
    }

    saveNotes() {
        const videoId = this.playlist[this.currentVideoIndex].videoId;
        this.notes[videoId] = this.notesInput.value;
        localStorage.setItem('videoNotes', JSON.stringify(this.notes));
        this.showNotification('Notes saved successfully!');
    }

    autoSaveNotes() {
        clearTimeout(this.autoSaveTimeout);
        this.autoSaveTimeout = setTimeout(() => this.saveNotes(), 1000);
    }

    showNotification(message) {
        this.notification.textContent = message;
        this.notification.classList.add('show');
        setTimeout(() => {
            this.notification.classList.remove('show');
        }, 3000);
    }

    onPlayerStateChange(event) {
        // Save video progress
        if (event.data === YT.PlayerState.PAUSED || event.data === YT.PlayerState.ENDED) {
            const videoId = this.playlist[this.currentVideoIndex].videoId;
            const currentTime = this.player.getCurrentTime();
            const progress = {
                videoId,
                time: currentTime,
                timestamp: new Date().toISOString()
            };
            localStorage.setItem(`videoProgress_${videoId}`, JSON.stringify(progress));
        }

        // Auto-play next video when current video ends
        if (event.data === YT.PlayerState.ENDED) {
            this.playNextVideo();
        }
    }

    // Method to add a new video to the playlist
    addVideo(videoData) {
        this.playlist.push(videoData);
        this.renderPlaylist();
    }

    // Method to remove a video from the playlist
    removeVideo(index) {
        if (index >= 0 && index < this.playlist.length) {
            this.playlist.splice(index, 1);
            this.renderPlaylist();
            if (index === this.currentVideoIndex) {
                this.loadVideo(0);
            }
        }
    }

    filterPlaylist(query) {
        const items = this.playlistContainer.querySelectorAll('.playlist-item');
        items.forEach(item => {
            const title = item.querySelector('.info .title').textContent.toLowerCase();
            const matches = title.includes(query.toLowerCase());
            item.style.display = matches ? 'block' : 'none';
        });
    }
}

// Example usage:
const coursePlayer = new CoursePlayer();

// Example playlist data (you'll replace this with your actual YouTube playlist data)
const playlistData = [
    {
        videoId: 'yRpLlJmRo2w',
        title: 'Course Video 1',
        description: 'First video in the course series',
        duration: '00:00'
    },
    {
        videoId: 'LusTv0RlnSU',
        title: 'Course Video 2',
        description: 'Second video in the course series',
        duration: '00:00'
    },
    {
        videoId: 'I5srDu75h_M',
        title: 'Course Video 3',
        description: 'Third video in the course series',
        duration: '00:00'
    },
    {
        videoId: '0r1SfRoLuzU',
        title: 'Course Video 4',
        description: 'Fourth video in the course series',
        duration: '00:00'
    }
];

// Initialize the player with the playlist
coursePlayer.setPlaylist(playlistData);

// Function to add a YouTube playlist
function addYouTubePlaylist(playlistId) {
    // You'll need to implement the YouTube Data API to fetch playlist data
    // This is a placeholder for the API call
    fetch(`https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&maxResults=50&playlistId=${playlistId}&key=YOUR_API_KEY`)
        .then(response => response.json())
        .then(data => {
            const videos = data.items.map(item => ({
                videoId: item.snippet.resourceId.videoId,
                title: item.snippet.title,
                description: item.snippet.description,
                thumbnail: item.snippet.thumbnails.medium.url
            }));
            coursePlayer.setPlaylist(videos);
        })
        .catch(error => console.error('Error fetching playlist:', error));
}

// Example function to add a single video
function addSingleVideo(videoUrl) {
    // Extract video ID from URL
    const videoId = videoUrl.match(/(?:youtu\.be\/|youtube\.com(?:\/embed\/|\/v\/|\/watch\?v=|\/watch\?.+&v=))([\w-]{11})/)[1];
    
    // Fetch video details using YouTube Data API
    fetch(`https://www.googleapis.com/youtube/v3/videos?part=snippet,contentDetails&id=${videoId}&key=YOUR_API_KEY`)
        .then(response => response.json())
        .then(data => {
            const videoData = {
                videoId: videoId,
                title: data.items[0].snippet.title,
                description: data.items[0].snippet.description,
                duration: data.items[0].contentDetails.duration
            };
            coursePlayer.addVideo(videoData);
        })
        .catch(error => console.error('Error adding video:', error));
}

document.addEventListener('DOMContentLoaded', function() {
    // Course data (same as in courses.html)
    const courses = [
        {
            id: 1,
            title: "Course Video 1",
            category: "programming",
            level: "beginner",
            duration: "Video Course",
            lessons: 1,
            progress: 0,
            rating: 4.8,
            videoId: "yRpLlJmRo2w",
            image: "https://img.youtube.com/vi/yRpLlJmRo2w/maxresdefault.jpg",
            enrolled: false
        },
        {
            id: 2,
            title: "Course Video 2",
            category: "programming",
            level: "intermediate",
            duration: "Video Course",
            lessons: 1,
            progress: 0,
            rating: 4.7,
            videoId: "LusTv0RlnSU",
            image: "https://img.youtube.com/vi/LusTv0RlnSU/maxresdefault.jpg",
            enrolled: false
        },
        {
            id: 3,
            title: "Course Video 3",
            category: "programming",
            level: "intermediate",
            duration: "Video Course",
            lessons: 1,
            progress: 0,
            rating: 4.9,
            videoId: "I5srDu75h_M",
            image: "https://img.youtube.com/vi/I5srDu75h_M/maxresdefault.jpg",
            enrolled: false
        },
        {
            id: 4,
            title: "Course Video 4",
            category: "programming",
            level: "advanced",
            duration: "Video Course",
            lessons: 1,
            progress: 0,
            rating: 4.8,
            videoId: "0r1SfRoLuzU",
            image: "https://img.youtube.com/vi/0r1SfRoLuzU/maxresdefault.jpg",
            enrolled: false
        }
    ];

    // Get current video ID from URL
    const urlParams = new URLSearchParams(window.location.search);
    const currentVideoId = urlParams.get('video');
    
    // Find current video index
    let currentIndex = courses.findIndex(course => course.videoId === currentVideoId);
    if (currentIndex === -1) currentIndex = 0;

    // Update video information
    function updateVideoInfo(course) {
        document.getElementById('videoTitle').textContent = course.title;
        document.getElementById('videoDescription').textContent = `${course.level} level ${course.category} course`;
    }

    // Load video by ID
    function loadVideo(videoId) {
        const iframe = document.getElementById('videoPlayer');
        iframe.src = `https://www.youtube.com/embed/${videoId}?rel=0&modestbranding=1`;
        
        // Update video info
        const course = courses.find(c => c.videoId === videoId);
        if (course) {
            updateVideoInfo(course);
            currentIndex = courses.findIndex(c => c.videoId === videoId);
            updateNavigationButtons();
        }
    }

    // Update navigation buttons
    function updateNavigationButtons() {
        const prevButton = document.getElementById('prevButton');
        const nextButton = document.getElementById('nextButton');
        
        prevButton.disabled = currentIndex <= 0;
        nextButton.disabled = currentIndex >= courses.length - 1;
    }

    // Navigation event listeners
    document.getElementById('prevButton').addEventListener('click', () => {
        if (currentIndex > 0) {
            currentIndex--;
            loadVideo(courses[currentIndex].videoId);
        }
    });

    document.getElementById('nextButton').addEventListener('click', () => {
        if (currentIndex < courses.length - 1) {
            currentIndex++;
            loadVideo(courses[currentIndex].videoId);
        }
    });

    // Render playlist
    function renderPlaylist() {
        const playlistContainer = document.getElementById('videoPlaylist');
        playlistContainer.innerHTML = '';

        courses.forEach((course, index) => {
            const item = document.createElement('div');
            item.className = `playlist-item ${index === currentIndex ? 'active' : ''}`;
            item.innerHTML = `
                <div class="playlist-item-content">
                    <img src="${course.image}" alt="${course.title}">
                    <div class="playlist-item-info">
                        <h4>${course.title}</h4>
                        <span>${course.level} • ${course.duration}</span>
                    </div>
                </div>
            `;
            item.addEventListener('click', () => loadVideo(course.videoId));
            playlistContainer.appendChild(item);
        });
    }

    // Search functionality
    const searchInput = document.getElementById('playlistSearch');
    searchInput.addEventListener('input', (e) => {
        const searchTerm = e.target.value.toLowerCase();
        const playlistItems = document.querySelectorAll('.playlist-item');
        
        playlistItems.forEach((item, index) => {
            const title = courses[index].title.toLowerCase();
            const matches = title.includes(searchTerm);
            item.style.display = matches ? 'block' : 'none';
        });
    });

    // Notes functionality
    const notesTextarea = document.getElementById('videoNotes');
    const saveNotesButton = document.getElementById('saveNotesButton');

    // Load saved notes
    function loadNotes() {
        const savedNotes = localStorage.getItem(`notes_${currentVideoId}`);
        notesTextarea.value = savedNotes || '';
    }

    // Save notes
    function saveNotes() {
        localStorage.setItem(`notes_${currentVideoId}`, notesTextarea.value);
        showNotification('Notes saved successfully!');
    }

    saveNotesButton.addEventListener('click', saveNotes);

    // Show notification
    function showNotification(message) {
        const notification = document.querySelector('.notification');
        notification.textContent = message;
        notification.classList.add('show');
        
        setTimeout(() => {
            notification.classList.remove('show');
        }, 3000);
    }

    // Initialize
    if (currentVideoId) {
        loadVideo(currentVideoId);
    } else if (courses.length > 0) {
        loadVideo(courses[0].videoId);
    }
    
    renderPlaylist();
    loadNotes();
    updateNavigationButtons();
}); 