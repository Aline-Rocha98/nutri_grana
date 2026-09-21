import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                ng: {
                    page: 'var(--ng-page)',
                    'page-accent': 'var(--ng-page-accent)',
                    card: 'var(--ng-card)',
                    'card-muted': 'var(--ng-card-muted)',
                    raised: 'var(--ng-raised)',
                    sidebar: 'var(--ng-sidebar)',
                    ink: 'var(--ng-ink)',
                    'ink-secondary': 'var(--ng-ink-secondary)',
                    'ink-muted': 'var(--ng-ink-muted)',
                    'ink-subtle': 'var(--ng-ink-subtle)',
                    line: 'var(--ng-line)',
                    'line-strong': 'var(--ng-line-strong)',
                    input: 'var(--ng-input)',
                    'input-border': 'var(--ng-input-border)',
                    brand: 'var(--ng-brand)',
                    'brand-hover': 'var(--ng-brand-hover)',
                    'brand-soft': 'var(--ng-brand-soft)',
                    header: 'var(--ng-header)',
                },
            },
            borderRadius: {
                '2.5xl': '1.25rem',
                '3xl': '1.5rem',
                '4xl': '2rem',
            },
            boxShadow: {
                soft: '0 1px 2px rgba(0,0,0,0.04), 0 8px 24px rgba(0,0,0,0.04)',
                panel: '0 0 0 1px var(--ng-line)',
            },
            transitionTimingFunction: {
                smooth: 'cubic-bezier(0.22, 1, 0.36, 1)',
            },
        },
    },

    plugins: [forms],

    safelist: [
        'text-red-500',
        'bg-red-50',
        'dark:bg-red-500/10',

        'text-blue-500',
        'bg-blue-50',
        'dark:bg-blue-500/10',

        'text-amber-500',
        'bg-amber-50',
        'dark:bg-amber-500/10',

        'text-[#1fa67e]',
        'bg-green-50',
        'dark:bg-emerald-500/10',
    ],
};
