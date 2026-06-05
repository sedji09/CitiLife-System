/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: 'class',
  content: [
    "./views/**/*.php",
    "./app/**/*.php",
    "./public/assets/js/**/*.js",
    "./*.php"
  ],
  safelist: [
    // Priority & Status badge colors (dynamic PHP class strings)
    'border-red-400',    'bg-red-50',    'text-red-700',
    'border-orange-400', 'bg-orange-50', 'text-orange-700',
    'border-yellow-400', 'bg-yellow-50', 'text-yellow-700',
    'border-green-400',  'bg-green-50',  'text-green-700',
    'border-blue-400',   'bg-blue-50',   'text-blue-700',
    'border-indigo-400', 'bg-indigo-50', 'text-indigo-700',
    'border-purple-400', 'bg-purple-50', 'text-purple-700',
    'border-gray-400',   'bg-gray-50',   'text-gray-700',
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}