/**
 * SoundFX — Sonidos con Web Audio API
 */
(function () {
    var ctx = null;

    function getCtx() {
        if (ctx && ctx.state !== 'closed') {
            if (ctx.state === 'suspended') { ctx.resume(); }
            return ctx;
        }
        try {
            ctx = new (window.AudioContext || window.webkitAudioContext)();
            return ctx;
        } catch (e) { return null; }
    }

    function beep(freq, dur, vol) {
        try {
            var c = getCtx();
            if (!c) return;
            var o = c.createOscillator();
            var g = c.createGain();
            o.type = 'sine';
            o.connect(g);
            g.connect(c.destination);
            o.frequency.setValueAtTime(freq, c.currentTime);
            g.gain.setValueAtTime(vol, c.currentTime);
            g.gain.exponentialRampToValueAtTime(0.001, c.currentTime + dur);
            o.start(c.currentTime);
            o.stop(c.currentTime + dur);
        } catch (e) {}
    }

    window.SoundFX = {
        tick: function () { beep(800, 0.05, 0.15); },
        found: function () { beep(660, 0.15, 0.2); setTimeout(function () { beep(880, 0.15, 0.2); }, 120); },
        select: function () { beep(500, 0.08, 0.1); },
        activate: function () { getCtx(); },
    };

    // Activar automaticamente lo antes posible
    function tryActivate() {
        getCtx();
    }
    // Al cargar la pagina
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', tryActivate);
    } else {
        tryActivate();
    }
    // Al primer click del usuario
    document.addEventListener('click', function () { getCtx(); });
    document.addEventListener('touchstart', function () { getCtx(); });
})();
