// assets/js/dashboard.js

document.addEventListener('DOMContentLoaded', () => {
    const loadingState = document.getElementById('loading-state');
    const dashboardContent = document.getElementById('dashboard-content');
    const logoutBtn = document.getElementById('logout-btn');

    const populateDashboard = (data) => {
        if (!data || !data.loggedIn) {
            // If not logged in, redirect to the main page
            window.location.href = 'index.html';
            return;
        }

        // Subscription info
        document.getElementById('plan-name').textContent = data.subscription.name;
        document.getElementById('plan-end-date').textContent = data.subscription.endDate ? new Date(data.subscription.endDate).toLocaleDateString() : 'N/A';

        // Usage stats
        const { imagesUsed, imageLimit, videosUsed, videoLimit } = data.usage;
        document.getElementById('images-used').textContent = imagesUsed;
        document.getElementById('images-limit').textContent = imageLimit;
        document.getElementById('videos-used').textContent = videosUsed;
        document.getElementById('videos-limit').textContent = videoLimit;

        // Progress bars
        const imageProgress = (imagesUsed / imageLimit) * 100;
        const videoProgress = (videosUsed / videoLimit) * 100;
        document.getElementById('images-progress').style.width = `${imageProgress}%`;
        document.getElementById('videos-progress').style.width = `${videoProgress}%`;

        // Show content
        loadingState.style.display = 'none';
        dashboardContent.style.display = 'grid';
    };

    const fetchDashboardData = async () => {
        try {
            const response = await fetch('../api/user_status.php');
            if (!response.ok) {
                 throw new Error('User not authenticated');
            }
            const data = await response.json();
            populateDashboard(data);
        } catch (error) {
            console.error('Failed to fetch user data:', error);
            window.location.href = 'index.html'; // Redirect on error/unauthenticated
        }
    };

    const handleLogout = async () => {
        try {
            await fetch('../api/logout.php');
            window.location.href = 'index.html';
        } catch (error) {
            console.error('Logout failed:', error);
        }
    };

    logoutBtn.addEventListener('click', handleLogout);

    // Also add other event listeners for theme, lang etc. from main.js
    // This is a simplified version for dashboard-specific logic
    const themeToggle = document.getElementById('theme-toggle');
    const applyTheme = (theme) => {
        if (theme === 'light') {
            document.body.classList.add('light-mode');
            document.body.classList.remove('dark-mode');
            if(themeToggle) themeToggle.checked = true;
        } else {
            document.body.classList.add('dark-mode');
            document.body.classList.remove('light-mode');
            if(themeToggle) themeToggle.checked = false;
        }
    };
    const savedTheme = localStorage.getItem('theme') || 'dark';
    applyTheme(savedTheme);
    if(themeToggle) {
        themeToggle.addEventListener('change', () => {
            const newTheme = themeToggle.checked ? 'light' : 'dark';
            localStorage.setItem('theme', newTheme);
            applyTheme(newTheme);
        });
    }

    fetchDashboardData();
});