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
                    DEFAULT: '#1e3a5f',
                    50: '#eff6ff',
                    100: '#dbeafe',
                    500: '#2a4a7a',
                    600: '#1e3a5f',
                    700: '#0f2b4a',
                    800: '#0f2847',
                    900: '#060e1a',
                    dark: '#0f2b4a',
                    light: '#2a4a7a',
                },
                gold: {
                    DEFAULT: '#f5a623',
                    50: '#fffbeb',
                    100: '#fef3c7',
                    200: '#fde68a',
                    400: '#f5a623',
                    600: '#d97706',
                    700: '#b87a0a',
                    800: '#92400e',
                    dark: '#d48b0a',
                    light: '#fef3c7',
                },
            },
            fontFamily: {
                sans: ['Inter', 'Plus Jakarta Sans', 'ui-sans-serif', 'system-ui', '-apple-system', 'Segoe UI', 'Roboto', 'sans-serif'],
            },
            boxShadow: {
                lift: '0 6px 15px rgba(30, 58, 95, 0.35)',
                goldlift: '0 6px 15px rgba(245, 166, 35, 0.35)',
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
