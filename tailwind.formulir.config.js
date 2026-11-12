import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** Tailwind khusus pulau Formulir (Inertia/Vue) — tidak dipakai halaman Materialize. */
/** Batch Desain: nilai token warna ada di resources/css/formulir.css; bayangan dibuat berlapis agar kartu terpisah jelas dari kanvas. */
/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './resources/views/formulir/**/*.blade.php',
    './resources/js/formulir/**/*.vue'
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        canvas: 'rgb(var(--color-canvas) / <alpha-value>)',
        surface: 'rgb(var(--color-surface) / <alpha-value>)',
        ink: 'rgb(var(--color-ink) / <alpha-value>)',
        muted: 'rgb(var(--color-muted) / <alpha-value>)',
        border: 'rgb(var(--color-border) / <alpha-value>)',
        brand: {
          DEFAULT: 'rgb(var(--color-brand) / <alpha-value>)',
          light: '#818CF8',
          dark: '#4338CA'
        },
        success: 'rgb(var(--color-success) / <alpha-value>)',
        warning: 'rgb(var(--color-warning) / <alpha-value>)',
        danger: 'rgb(var(--color-danger) / <alpha-value>)',
        info: 'rgb(var(--color-info) / <alpha-value>)'
      },
      fontFamily: {
        sans: ['Plus Jakarta Sans', ...defaultTheme.fontFamily.sans],
        display: ['Plus Jakarta Sans', ...defaultTheme.fontFamily.sans]
      },
      fontSize: {
        xs: ['0.75rem', { lineHeight: '1.1rem' }],
        sm: ['0.8125rem', { lineHeight: '1.25rem' }],
        base: ['0.9375rem', { lineHeight: '1.5rem' }],
        lg: ['1.0625rem', { lineHeight: '1.6rem' }],
        xl: ['1.25rem', { lineHeight: '1.75rem', letterSpacing: '-0.01em' }],
        '2xl': ['1.5rem', { lineHeight: '2rem', letterSpacing: '-0.015em' }],
        '3xl': ['1.875rem', { lineHeight: '2.25rem', letterSpacing: '-0.02em' }]
      },
      boxShadow: {
        premium:
          '0 1px 2px rgba(15, 23, 42, 0.07), 0 12px 32px -12px rgba(15, 23, 42, 0.22)',
        'premium-dark':
          '0 1px 2px rgba(0, 0, 0, 0.4), 0 12px 32px -12px rgba(0, 0, 0, 0.55)',
        glow: '0 8px 20px -6px rgba(79, 70, 229, 0.45)',
        soft: '0 1px 2px rgba(15, 23, 42, 0.06), 0 4px 16px -6px rgba(15, 23, 42, 0.14)',
        hover:
          '0 2px 4px rgba(15, 23, 42, 0.07), 0 20px 40px -12px rgba(15, 23, 42, 0.25)',
        card: '0 1px 2px rgba(15, 23, 42, 0.06), 0 8px 24px -12px rgba(15, 23, 42, 0.18)',
        lift: '0 4px 8px rgba(15, 23, 42, 0.06), 0 16px 32px -12px rgba(15, 23, 42, 0.22)',
        bar: '0 -8px 30px rgba(15, 23, 42, 0.10)'
      },
      borderRadius: { '4xl': '2rem' },
      backgroundImage: {
        'brand-gradient':
          'linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%)',
        'mesh-emerald':
          'radial-gradient(at 20% 20%, rgba(31,75,63,0.15) 0px, transparent 50%), radial-gradient(at 80% 0%, rgba(184,86,47,0.10) 0px, transparent 50%), radial-gradient(at 50% 100%, rgba(31,75,63,0.08) 0px, transparent 50%)'
      }
    }
  },
  plugins: [forms]
};
