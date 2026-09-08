/**
 * BookShelf — Main JavaScript
 */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    // ── Theme Toggle (Light / Dark) ─────────────────────────────
    const themeToggle = document.getElementById('theme-toggle');

    function getStoredTheme() {
        return localStorage.getItem('bookshelf-theme');
    }

    function getPreferredTheme() {
        const stored = getStoredTheme();
        if (stored) return stored;
        return window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
    }

    function applyTheme(theme) {
        if (theme === 'light') {
            document.documentElement.setAttribute('data-theme', 'light');
        } else {
            document.documentElement.removeAttribute('data-theme');
        }
        localStorage.setItem('bookshelf-theme', theme);
    }

    // Apply on load (before paint if possible)
    applyTheme(getPreferredTheme());

    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const current = getPreferredTheme();
            const next = current === 'dark' ? 'light' : 'dark';
            applyTheme(next);
        });
    }

    // ── Print ───────────────────────────────────────────────────
    const printBtn = document.getElementById('btn-print');
    if (printBtn) {
        printBtn.addEventListener('click', () => window.print());
    }

    // ── Lucide Icons ────────────────────────────────────────────
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
