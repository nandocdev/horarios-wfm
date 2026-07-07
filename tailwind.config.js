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
    { pattern: /bg-slate-(50|100|200|300|400|500|600|700|800|900|950)/ },
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
