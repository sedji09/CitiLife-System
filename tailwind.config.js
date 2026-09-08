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
    // Priority & Status badge colors & Action buttons
    'border-red-400',    'border-red-500',    'bg-red-50',    'bg-red-100',    'text-red-600',    'text-red-700',    'hover:bg-red-600',    'hover:border-red-600',
    'border-orange-400', 'bg-orange-50', 'text-orange-700',
    'border-yellow-400', 'bg-yellow-50', 'text-yellow-700',
    'border-green-400',  'border-green-500',  'bg-green-50',  'bg-green-100',  'text-green-600',  'text-green-700',  'hover:bg-green-600',  'hover:border-green-600',
    'border-blue-400',   'border-blue-500',   'bg-blue-50',   'bg-blue-100',   'text-blue-600',   'text-blue-700',   'hover:bg-blue-600',   'hover:border-blue-600',
    'border-indigo-400', 'border-indigo-500', 'bg-indigo-50', 'bg-indigo-100', 'text-indigo-600', 'text-indigo-700', 'hover:bg-indigo-600', 'hover:border-indigo-600',
    'border-purple-400', 'border-purple-500', 'bg-purple-50', 'bg-purple-100', 'text-purple-600', 'text-purple-700', 'hover:bg-purple-600', 'hover:border-purple-600',
    'border-gray-400',   'border-gray-500',   'bg-gray-50',   'bg-gray-100',   'text-gray-600',   'text-gray-700',   'hover:bg-gray-600',   'hover:border-gray-600',
    'border-amber-400',  'border-amber-500',  'bg-amber-50',  'bg-amber-100',  'text-amber-600',  'text-amber-700',  'hover:bg-amber-600',  'hover:border-amber-600',
    'hover:text-white',
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}