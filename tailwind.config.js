import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.tsx',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Remapped from emerald -> brand orange-yellow (#fdb913).
                emerald: {
                    '50': '#fff7e3',
                    '100': '#feeec4',
                    '200': '#fee095',
                    '300': '#fed266',
                    '400': '#fdc63d',
                    '500': '#fdb913',
                    '600': '#dfa311',
                    '700': '#b1820d',
                    '800': '#84600a',
                    '900': '#563f06',
                    '950': '#2e2103',
                },
            },
        },
    },

    plugins: [forms],
};
