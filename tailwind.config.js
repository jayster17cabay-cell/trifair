/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './public/js/**/*.js',
    ],
    safelist: [
        'border-l-red-500',
        'border-l-amber-400',
        'border-l-emerald-500',
        'border-l-slate-300',
    ],
    theme: {
        extend: {
            colors: {
                navy: {
                    DEFAULT: '#0A2A5E',
                    50: '#eef3fb',
                    100: '#dbe7fa',
                    500: '#2e7dd1',
                    600: '#11366f',
                    700: '#0A2A5E',
                    800: '#071e42',
                    900: '#051327',
                    dark: '#0A2A5E',
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
                    DEFAULT: '#F5B301',
                    50: '#fff3d6',
                    100: '#ffe7ad',
                    200: '#fbd97f',
                    400: '#F5B301',
                    500: '#F5B301',
                    600: '#E0A800',
                    700: '#b8860b',
                    800: '#8a5f06',
                    dark: '#E0A800',
                    light: '#fff3d6',
                },
                'nv-green': {
                    DEFAULT: '#1B7A3D',
                    light: '#E6F4EA',
                },
                'nv-red': {
                    DEFAULT: '#C8102E',
                    light: '#FDECEC',
                },
            },
            fontFamily: {
                sans: ['Inter', 'Plus Jakarta Sans', 'ui-sans-serif', 'system-ui', '-apple-system', 'Segoe UI', 'Roboto', 'sans-serif'],
            },
            boxShadow: {
                lift: '0 6px 15px rgba(10, 42, 94, 0.35)',
                goldlift: '0 6px 15px rgba(245, 179, 1, 0.35)',
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
