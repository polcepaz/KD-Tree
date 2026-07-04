/**
 * ThemeManager — Alternancia tema oscuro/claro con persistencia en localStorage
 */
(function () {
    const KEY = 'kdtree-theme';

    function getTheme() {
        return document.documentElement.getAttribute('data-theme') || 'dark';
    }

    function setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem(KEY, theme);
        document.dispatchEvent(new CustomEvent('themechange', { detail: { theme } }));
        updateToggleIcon(theme);
    }

    function toggleTheme() {
        setTheme(getTheme() === 'dark' ? 'light' : 'dark');
    }

    function updateToggleIcon(theme) {
        document.querySelectorAll('.theme-toggle').forEach(btn => {
            btn.textContent = theme === 'dark' ? '☀ Claro' : '☾ Oscuro';
            btn.title = theme === 'dark' ? 'Modo claro' : 'Modo oscuro';
        });
    }

    // Aplicar tema guardado al cargar la pagina
    const saved = localStorage.getItem(KEY);
    if (saved === 'light' || saved === 'dark') {
        document.documentElement.setAttribute('data-theme', saved);
    } else {
        // Detectar preferencia del sistema
        const prefers = window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', prefers);
    }

    // Inicializar botones cuando el DOM este listo
    function init() {
        document.querySelectorAll('.theme-toggle').forEach(btn => {
            if (!btn.dataset.bound) {
                btn.dataset.bound = '1';
                btn.addEventListener('click', toggleTheme);
            }
        });
        updateToggleIcon(getTheme());
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.ThemeManager = { getTheme, setTheme, toggleTheme };
})();
