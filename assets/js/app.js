document.addEventListener('DOMContentLoaded', function () {
    const themeToggle = document.getElementById('themeToggle');
    const savedTheme = localStorage.getItem('smoketechTheme') || 'light';

    function setTheme(mode) {
        document.body.classList.toggle('dark-mode', mode === 'dark');
        if (themeToggle) {
            themeToggle.innerHTML = mode === 'dark'
                ? '<i class="fas fa-sun"></i> Light'
                : '<i class="fas fa-moon"></i> Dark';
            themeToggle.setAttribute('aria-label', mode === 'dark' ? 'Switch to light mode' : 'Switch to dark mode');
        }
        localStorage.setItem('smoketechTheme', mode);
    }

    setTheme(savedTheme);

    if (themeToggle) {
        themeToggle.addEventListener('click', function (event) {
            event.preventDefault();
            setTheme(document.body.classList.contains('dark-mode') ? 'light' : 'dark');
        });
    }
});
