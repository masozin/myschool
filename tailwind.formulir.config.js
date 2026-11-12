import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** Tailwind khusus pulau Formulir (Inertia/Vue) — tidak dipakai halaman Materialize. */
/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './resources/views/formulir/**/*.blade.php',
    './resources/js/formulir/**/*.vue'
  ],
  darkMode: 'class',
  theme: {
    extend: {
      fontFamily: {
        sans: ['Plus Jakarta Sans', ...defaultTheme.fontFamily.sans]
      }
    }
  },
  plugins: [forms]
};
