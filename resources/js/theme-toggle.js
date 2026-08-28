// Theme toggle button behavior. The initial dark/light class on <html> is
// applied synchronously in a blocking <head> script (see app.blade.php) to
// avoid a flash of the wrong theme before this module loads.
function updateToggleIcon(toggleBtn, isDark) {
    const sunIcon = toggleBtn.querySelector('.icon-sun');
    const moonIcon = toggleBtn.querySelector('.icon-moon');
    if (sunIcon) sunIcon.classList.toggle('hidden', isDark);
    if (moonIcon) moonIcon.classList.toggle('hidden', !isDark);
}

document.addEventListener('DOMContentLoaded', function () {
    const toggleBtn = document.getElementById('theme-toggle');
    if (!toggleBtn) return;

    updateToggleIcon(toggleBtn, document.documentElement.classList.contains('dark'));

    toggleBtn.addEventListener('click', function () {
        const isDark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        updateToggleIcon(toggleBtn, isDark);
    });
});
