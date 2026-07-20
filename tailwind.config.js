import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],

    safelist: [
        'text-red-500',
        'bg-red-50',

        'text-blue-500',
        'bg-blue-50',

        'text-amber-500',
        'bg-amber-50',

        'text-[#1fa67e]',
        'bg-green-50',
    ],
};
