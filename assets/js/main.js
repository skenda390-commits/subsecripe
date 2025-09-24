// assets/js/main.js

document.addEventListener('DOMContentLoaded', () => {
    const themeToggle = document.getElementById('theme-toggle');
    const langSwitcher = document.getElementById('lang-switcher');

    // --- Theme Switcher ---
    // Function to apply theme
    const applyTheme = (theme) => {
        if (theme === 'light') {
            document.body.classList.add('light-mode');
            document.body.classList.remove('dark-mode');
            themeToggle.checked = true;
        } else {
            document.body.classList.add('dark-mode');
            document.body.classList.remove('light-mode');
            themeToggle.checked = false;
        }
    };

    // Check for saved theme in localStorage
    const savedTheme = localStorage.getItem('theme') || 'dark'; // Default to dark
    applyTheme(savedTheme);

    // Event listener for the theme toggle
    themeToggle.addEventListener('change', () => {
        const newTheme = themeToggle.checked ? 'light' : 'dark';
        localStorage.setItem('theme', newTheme);
        applyTheme(newTheme);
    });

    // --- Language Switcher ---
    let translations = {};

    const applyLanguage = (lang) => {
        document.documentElement.lang = lang;
        document.documentElement.dir = lang === 'ar' ? 'rtl' : 'ltr';

        document.querySelectorAll('[data-translate]').forEach(element => {
            const key = element.getAttribute('data-translate');
            if (translations[lang] && translations[lang][key]) {
                element.innerHTML = translations[lang][key];
            }
        });
    };

    const fetchTranslations = async () => {
        try {
            const response = await fetch('../translations.json');
            translations = await response.json();
            const savedLang = localStorage.getItem('language') || 'en';
            langSwitcher.value = savedLang;
            applyLanguage(savedLang);
        } catch (error) {
            console.error('Could not load translations:', error);
        }
    };

    langSwitcher.addEventListener('change', (e) => {
        const newLang = e.target.value;
        localStorage.setItem('language', newLang);
        applyLanguage(newLang);
    });

    // --- User Status Check ---
    const checkUserStatus = async () => {
        try {
            const response = await fetch('../api/user_status.php');
            const data = await response.json();

            if (data.loggedIn) {
                // User is logged in, update UI
                const userBtn = document.getElementById('user-btn');
                userBtn.textContent = data.user.email;
                userBtn.onclick = () => { window.location.href = 'dashboard.html'; }; // Redirect to dashboard

                // Hide ads if user has a paid plan
                if (!data.subscription.hasAds) {
                    const adsContainer = document.getElementById('ads-container');
                    if(adsContainer) adsContainer.style.display = 'none';
                } else {
                     const adsContainer = document.getElementById('ads-container');
                    if(adsContainer) adsContainer.style.display = 'block';
                }

                // Store user data globally for other scripts to use
                window.userData = data;

            } else {
                // User is not logged in
                window.userData = null;
            }
        } catch (error) {
            console.error('Error checking user status:', error);
            window.userData = null;
        }
    };


    // Initial load
    await fetchTranslations();
    await checkUserStatus();
});