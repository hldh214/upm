/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.jsx',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            colors: {
                'uq-red': '#FF0000',
                'gu-blue': '#0066CC',
            },
        },
    },
    plugins: [],
};
