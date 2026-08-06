/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],
    theme: {
        extend: {
            colors: {
                navy: {
                    DEFAULT: '#0f2a4a',
                    50: '#eff6ff',
                    100: '#dbeafe',
                    500: '#2e7dd1',
                    600: '#1a3a5c',
                    700: '#0f2a4a',
                    800: '#0a1d33',
                    900: '#060e1a',
                    dark: '#0f2a4a',
                    light: '#2e7dd1',
                },
                blue: {
                    50: '#e6f1fb',
                    100: '#d7e9f9',
                    200: '#b5d4f4',
                    500: '#2e7dd1',
                    600: '#2563a8',
                    700: '#1d4f87',
                },
                gold: {
                    DEFAULT: '#f5b800',
                    50: '#faeeda',
                    100: '#f7e2b8',
                    200: '#f0d295',
                    400: '#f5b800',
                    500: '#f5b800',
                    600: '#e0a800',
                    700: '#c08f00',
                    800: '#854f0b',
                    dark: '#e0a800',
                    light: '#faeeda',
                },
            },
            fontFamily: {
                sans: ['Inter', 'Plus Jakarta Sans', 'ui-sans-serif', 'system-ui', '-apple-system', 'Segoe UI', 'Roboto', 'sans-serif'],
            },
            boxShadow: {
                lift: '0 6px 15px rgba(15, 42, 74, 0.35)',
                goldlift: '0 6px 15px rgba(245, 184, 0, 0.35)',
                soft: '0 10px 30px rgba(15, 23, 42, 0.08)',
            },
            borderRadius: {
                DEFAULT: '10px',
                '2xl': '16px',
                '3xl': '20px',
            },
        },
    },
    corePlugins: {
        preflight: false,
    },
    plugins: [],
};
