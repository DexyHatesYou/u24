/**
 * Book Management App — Main JavaScript
 */

'use strict';

document.addEventListener('DOMContentLoaded', () => {
    // ── Print functionality ─────────────────────────────────────
    const printBtn = document.getElementById('btn-print');
    if (printBtn) {
        printBtn.addEventListener('click', () => window.print());
    }
});
