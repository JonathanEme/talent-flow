/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.ts",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      colors: {
        brand: {
          50: '#eef2ff',
          100: '#e0e7ff',
          500: '#6366f1',
          600: '#4f46e5',
          700: '#4338ca',
          900: '#312e81',
        },
        surface: {
          base: '#f8fafc',    // slate-50
          card: '#ffffff',    // white
          muted: '#f1f5f9',   // slate-100
          border: '#e2e8f0',  // slate-200
        }
      }
    },
  },
  plugins: [],
}