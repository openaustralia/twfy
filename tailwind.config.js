/** @type {import('tailwindcss').Config} */
// Tailwind CSS is MIT licensed, compatible with this repo's BSD-3-Clause license (see LICENSE.txt).
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
