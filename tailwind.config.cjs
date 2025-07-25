const colors = require('tailwindcss/colors');

module.exports = {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
    "./app/Filament/**/*.php",
    "./app/Livewire/**/*.php",
    "./vendor/filament/**/*.blade.php",
    "./vendor/bezhansalleh/filament-shield/**/*.blade.php",
    "./vendor/leandrocfe/filament-apex-charts/**/*.blade.php",
    "./vendor/saade/filament-fullcalendar/**/*.blade.php",
  ],
  theme: {
    extend: {
      colors: {
        danger: colors.rose,
        primary: colors.blue,
        success: colors.emerald,
        warning: colors.orange,
      },
    },
  },
  plugins: [
    require("@tailwindcss/forms"),
    require("@tailwindcss/typography"),
  ],
}