/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
    "./app/**/*.php",
    "./vendor/livewire/flux/stubs/resources/views/flux/**/*.blade.php",
  ],
  safelist: [
    { pattern: /bg-(blue|amber|indigo|emerald|green|zinc|primary)-500/ },
    { pattern: /ring-(blue|amber|indigo|emerald|green|zinc|primary)-500\/20/ },
    { pattern: /ring-(blue|amber|indigo|emerald|green|zinc|primary)-500\/30/ },
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
