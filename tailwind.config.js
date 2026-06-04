import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    darkMode: 'class',

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
                mono: ['"JetBrains Mono"', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                primary: {
                    50: '#eef2ff',
                    100: '#e0e7ff',
                    200: '#c7d2fe',
                    300: '#a5b4fc',
                    400: '#818cf8',
                    500: '#6366f1',
                    600: '#4f46e5',
                    700: '#4338ca',
                    800: '#3730a3',
                    900: '#312e81',
                    950: '#1e1b4b',
                },
            },
            borderRadius: {
                '2xl': '1rem',
                '3xl': '1.25rem',
                '4xl': '1.5rem',
            },
            boxShadow: {
                'glass': '0 8px 32px rgba(0, 0, 0, 0.08)',
                'glass-lg': '0 16px 48px rgba(0, 0, 0, 0.12)',
                'card': '0 1px 3px rgba(0, 0, 0, 0.06), 0 1px 2px rgba(0, 0, 0, 0.04)',
                'card-hover': '0 10px 40px rgba(0, 0, 0, 0.1)',
            },
            animation: {
                'fade-in': 'fadeIn 0.5s ease-out',
                'slide-up': 'slideUp 0.3s ease-out',
                'slide-down': 'slideDown 0.3s ease-out',
                'scale-in': 'scaleIn 0.2s ease-out',
            },
            keyframes: {
                fadeIn: {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                slideUp: {
                    '0%': { opacity: '0', transform: 'translateY(10px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                slideDown: {
                    '0%': { opacity: '0', transform: 'translateY(-10px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                scaleIn: {
                    '0%': { opacity: '0', transform: 'scale(0.95)' },
                    '100%': { opacity: '1', transform: 'scale(1)' },
                },
            },
        },
    },

    safelist: [
        // StatCard dynamic color classes
        'bg-indigo-50', 'dark:bg-indigo-950', 'text-indigo-600', 'dark:text-indigo-400',
        'bg-emerald-50', 'dark:bg-emerald-950', 'text-emerald-600', 'dark:text-emerald-400',
        'bg-purple-50', 'dark:bg-purple-950', 'text-purple-600', 'dark:text-purple-400',
        'bg-amber-50', 'dark:bg-amber-950', 'text-amber-600', 'dark:text-amber-400',
        'bg-blue-50', 'dark:bg-blue-950', 'text-blue-600', 'dark:text-blue-400',
        'bg-red-50', 'dark:bg-red-950', 'text-red-600', 'dark:text-red-400',
        'bg-gray-50', 'dark:bg-gray-950', 'text-gray-600', 'dark:text-gray-400',
        'bg-orange-50', 'dark:bg-orange-950', 'text-orange-600', 'dark:text-orange-400',
        // StatusBadge dynamic color classes
        'bg-emerald-400', 'bg-emerald-500', 'text-emerald-700', 'dark:text-emerald-400',
        'bg-amber-400', 'bg-amber-500', 'text-amber-700', 'dark:text-amber-400',
        'bg-orange-400', 'bg-orange-500', 'text-orange-700', 'dark:text-orange-400',
        'bg-red-400', 'bg-red-500', 'text-red-700', 'dark:text-red-400',
        'bg-blue-400', 'bg-blue-500', 'text-blue-700', 'dark:text-blue-400',
        'bg-gray-400', 'bg-gray-500', 'text-gray-700', 'dark:text-gray-400',
        // card-accent dynamic color classes
        'card-accent-indigo', 'card-accent-emerald', 'card-accent-purple',
        'card-accent-amber', 'card-accent-blue', 'card-accent-red', 'card-accent-orange',
    ],

    plugins: [forms],
};
