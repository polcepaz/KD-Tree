/**
 * Control de tamano de fuente con persistencia en localStorage
 */
(function () {
    var KEY = 'kdtree-fontsize';
    var sizes = [80, 85, 90, 95, 100, 105, 110, 115, 120, 130, 140];
    var current = parseInt(localStorage.getItem(KEY), 10) || 100;

    function apply(size) {
        document.documentElement.style.fontSize = size + '%';
        localStorage.setItem(KEY, size);
        current = size;
        updateButtons();
    }

    function increase() {
        var idx = sizes.indexOf(current);
        if (idx < sizes.length - 1) apply(sizes[idx + 1]);
    }

    function decrease() {
        var idx = sizes.indexOf(current);
        if (idx > 0) apply(sizes[idx - 1]);
    }

    function reset() {
        apply(100);
    }

    function updateButtons() {
        var disp = document.getElementById('fontsizeValue');
        if (disp) disp.textContent = current + '%';
    }

    // Aplicar valor guardado
    apply(current);

    // Init botones
    function init() {
        document.querySelectorAll('.fontsize-up').forEach(function (btn) {
            btn.addEventListener('click', increase);
        });
        document.querySelectorAll('.fontsize-down').forEach(function (btn) {
            btn.addEventListener('click', decrease);
        });
        document.querySelectorAll('.fontsize-reset').forEach(function (btn) {
            btn.addEventListener('click', reset);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.FontSize = { increase: increase, decrease: decrease, reset: reset, current: function () { return current; } };
})();
