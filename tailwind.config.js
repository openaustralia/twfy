/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ['./www/**/*.php'],
  corePlugins: {
    // The legacy stylesheets (www/docs/style/**) already provide a reset;
    // Tailwind's preflight would fight with them.
    preflight: false,
  },
  theme: {
    extend: {},
  },
  plugins: [],
};
