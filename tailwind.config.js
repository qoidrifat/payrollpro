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
                sans: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
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
                surface: {
                    DEFAULT: '#ffffff',
                    dark: '#0f172a',
                    50: '#f8fafc',
                    100: '#f1f5f9',
                    200: '#e2e8f0',
                    300: '#cbd5e1',
                },
            },
            borderRadius: {
                '2xl': '1rem',
                '3xl': '1.25rem',
                '4xl': '1.5rem',
            },
            boxShadow: {
                'glass': '0 8px 32px rgba(0, 0, 0, 0.06)',
                'glass-lg': '0 16px 48px rgba(0, 0, 0, 0.1)',
                'card': '0 1px 3px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.02)',
                'card-hover': '0 10px 40px rgba(0, 0, 0, 0.08)',
                'premium': '0 4px 24px rgba(99, 102, 241, 0.15), 0 1px 4px rgba(99, 102, 241, 0.06)',
                'premium-lg': '0 8px 40px rgba(99, 102, 241, 0.2), 0 2px 8px rgba(99, 102, 241, 0.08)',
                'inner-glow': 'inset 0 1px 0 rgba(255, 255, 255, 0.5)',
                'inner-glow-dark': 'inset 0 1px 0 rgba(255, 255, 255, 0.06)',
            },
            animation: {
                'fade-in': 'fadeIn 0.5s ease-out',
                'fade-in-up': 'fadeInUp 0.5s ease-out',
                'slide-up': 'slideUp 0.3s ease-out',
                'slide-down': 'slideDown 0.3s ease-out',
                'scale-in': 'scaleIn 0.2s ease-out',
                'shimmer': 'shimmer 2s ease-in-out infinite',
                'float': 'float 3s ease-in-out infinite',
                'pulse-subtle': 'pulseSubtle 2s ease-in-out infinite',
                'spin-slow': 'spin 3s linear infinite',
                'bounce-sm': 'bounceSm 1s ease-in-out infinite',
            },
            keyframes: {
                fadeIn: {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                fadeInUp: {
                    '0%': { opacity: '0', transform: 'translateY(12px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                slideUp: {
                    '0%': { opacity: '0', transform: 'translateY(8px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                slideDown: {
                    '0%': { opacity: '0', transform: 'translateY(-8px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                scaleIn: {
                    '0%': { opacity: '0', transform: 'scale(0.95)' },
                    '100%': { opacity: '1', transform: 'scale(1)' },
                },
                shimmer: {
                    '0%': { backgroundPosition: '-200% 0' },
                    '100%': { backgroundPosition: '200% 0' },
                },
                float: {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%': { transform: 'translateY(-6px)' },
                },
                pulseSubtle: {
                    '0%, 100%': { opacity: '1' },
                    '50%': { opacity: '0.7' },
                },
                bounceSm: {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%': { transform: 'translateY(-3px)' },
                },
            },
            spacing: {
                '4.5': '1.125rem',
                '5.5': '1.375rem',
            },
            ringWidth: {
                '3': '3px',
            },
            backgroundSize: {
                '200%': '200% 100%',
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
