import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    // Badge tones are chosen in PHP (for example from a status enum), so the
    // class names never appear in full in the views.
    safelist: ['badge-neutral', 'badge-info', 'badge-success', 'badge-warning', 'badge-danger'],

    corePlugins: {
        backgroundImage: false,
        gradientColorStops: false,
    },

    theme: {
        borderRadius: {
            none: '0',
            sm: '0',
            DEFAULT: '0',
            md: '0',
            lg: '0',
            xl: '0',
            '2xl': '0',
            '3xl': '0',
            full: '0',
        },
        extend: {
            fontFamily: {
                sans: ['"IBM Plex Sans"', ...defaultTheme.fontFamily.sans],
                mono: ['"IBM Plex Mono"', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                brand: {
                    50: '#EEF2FA',
                    100: '#DCE5F5',
                    200: '#B9CBEA',
                    300: '#8AA6D8',
                    400: '#5A7FC2',
                    500: '#2F5AA8',
                    600: '#1F4590',
                    700: '#173675',
                    800: '#112A5C',
                    900: '#0C1F47',
                    950: '#081530',
                },
                accent: {
                    100: '#F6E3EA',
                    500: '#D02A5E',
                    600: '#7A1540',
                    700: '#5E0F31',
                },
                paper: '#F4F6FB',
                line: '#D6DCEA',
                ink: '#141A2E',
                muted: '#56607A',
                gold: {
                    100: '#F6EBD2',
                    600: '#A6761C',
                    700: '#8A6117',
                },
            },
            boxShadow: {
                card: '0 1px 0 rgba(12, 31, 71, 0.06)',
            },
        },
    },

    plugins: [forms],
};