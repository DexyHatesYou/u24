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

    // ── File Upload Feedback ────────────────────────────────────
    const fileInputs = document.querySelectorAll('.file-upload-wrapper input[type="file"]');
    fileInputs.forEach(input => {
        const wrapper = input.closest('.file-upload-wrapper');
        const textEl = wrapper.querySelector('.file-upload-text');

        // Handle file selection
        input.addEventListener('change', function() {
            if (this.files && this.files.length > 0) {
                textEl.textContent = this.files[0].name;
                wrapper.classList.add('has-file');
            } else {
                textEl.textContent = 'Click to upload or drag and drop';
                wrapper.classList.remove('has-file');
            }
        });

        // Handle drag events for visual feedback
        ['dragenter', 'dragover'].forEach(eventName => {
            input.addEventListener(eventName, (e) => {
                wrapper.classList.add('is-dragover');
            });
        });

        ['dragleave', 'drop'].forEach(eventName => {
            input.addEventListener(eventName, (e) => {
                wrapper.classList.remove('is-dragover');
            });
        });
    });

    // ── Lucide Icons ────────────────────────────────────────────
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
