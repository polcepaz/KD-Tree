/**
 * Gestor del Log de Eventos del KD-Tree.
 * Muestra en tiempo real todas las operaciones que realiza la estructura.
 */
const EventLog = {
    panel: null,
    autoScroll: true,
    eventCount: 0,

    typeConfig: {
        BUILD:        { label: 'BUILD',       cls: 'badge bg-info',      icon: '🏗' },
        SPLIT:        { label: 'SPLIT',       cls: 'badge bg-primary',   icon: '🔪' },
        CREATE_NODE:  { label: 'NODE',        cls: 'badge bg-success',   icon: '●' },
        INSERT:       { label: 'INSERT',      cls: 'badge bg-success',   icon: '➕' },
        INSERT_LEFT:  { label: 'INSERT-L',    cls: 'badge bg-success',   icon: '⬅' },
        INSERT_RIGHT: { label: 'INSERT-R',    cls: 'badge bg-success',   icon: '➡' },
        DELETE:       { label: 'DELETE',      cls: 'badge bg-danger',    icon: '🗑' },
        NN_SEARCH:    { label: 'NN-SEARCH',   cls: 'badge bg-warning text-dark', icon: '🔍' },
        NN_RESULT:    { label: 'NN-RESULT',   cls: 'badge bg-warning text-dark', icon: '🎯' },
        RANGE_SEARCH: { label: 'RANGE',       cls: 'badge bg-secondary', icon: '🎯' },
        RANGE_RESULT: { label: 'RANGE-RES',   cls: 'badge bg-secondary', icon: '✅' },
        VISIT:        { label: 'VISIT',       cls: 'badge bg-info',      icon: '👁' },
        COMPARE:      { label: 'COMPARE',     cls: 'badge bg-warning text-dark', icon: '⚖' },
        UPDATE_BEST:  { label: 'BEST',        cls: 'badge bg-success',   icon: '⭐' },
        PRUNE:        { label: 'PRUNE',       cls: 'badge bg-danger',    icon: '✂' },
        EXPLORE_OTHER:{ label: 'EXPLORE',     cls: 'badge bg-purple',    icon: '🔄' },
        FOUND:        { label: 'FOUND',       cls: 'badge bg-success',   icon: '✅' },
        TRAVERSE:     { label: 'TRAVERSE',    cls: 'badge bg-info',      icon: '➡' },
        REBALANCE:    { label: 'REBALANCE',   cls: 'badge bg-danger',    icon: '🔄' },
        DEFAULT:      { label: 'EVENT',       cls: 'badge bg-secondary', icon: '•' },
    },

    init: function (panelId = 'eventLogPanel') {
        this.panel = document.getElementById(panelId);
        if (!this.panel) return;

        document.getElementById('btnClearEventLog')?.addEventListener('click', () => this.clear());
        document.getElementById('btnEventLogAutoScroll')?.addEventListener('click', () => {
            this.autoScroll = !this.autoScroll;
            const btn = document.getElementById('btnEventLogAutoScroll');
            if (btn) btn.style.opacity = this.autoScroll ? '1' : '0.4';
        });

        // Listen for custom events from App
        document.addEventListener('kdtree:event', (e) => {
            if (e.detail && e.detail.events) {
                this.addEvents(e.detail.events);
            }
        });
    },

    addEvents: function (events) {
        if (!this.panel || !events || events.length === 0) return;

        // Si es el primer evento, limpiar placeholder
        if (this.eventCount === 0) {
            this.panel.innerHTML = '';
        }

        events.forEach(event => {
            this.addEvent(event);
        });

        if (this.autoScroll) {
            this.panel.scrollTop = this.panel.scrollHeight;
        }
    },

    addEvent: function (event) {
        if (!this.panel) return;
        this.eventCount++;

        const type = event.type || 'DEFAULT';
        const cfg = this.typeConfig[type] || this.typeConfig.DEFAULT;
        const time = event.timestamp
            ? new Date(event.timestamp * 1000).toLocaleTimeString('es-ES', { hour12: false })
            : new Date().toLocaleTimeString('es-ES', { hour12: false });

        const entry = document.createElement('div');
        entry.className = 'event-log-entry';

        entry.innerHTML = `
            <span class="event-time">${time}</span>
            <span class="${cfg.cls} event-type-badge">${cfg.icon} ${cfg.label}</span>
            <span class="event-desc">${this.escapeHtml(event.description || '')}</span>
        `;

        this.panel.appendChild(entry);
    },

    clear: function () {
        if (!this.panel) return;
        this.panel.innerHTML = '<div class="text-secondary text-center py-4 small">Log limpiado. Espere nuevas operaciones...</div>';
        this.eventCount = 0;
    },

    escapeHtml: function (text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },
};

document.addEventListener('DOMContentLoaded', () => EventLog.init('eventLogPanel'));
