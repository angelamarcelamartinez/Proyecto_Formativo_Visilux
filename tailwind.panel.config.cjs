// Configuración para regenerar public/assets/css/panel.css (ver resources/css/panel.css).
module.exports = {
  content: ['./resources/views/**/*.blade.php'],
  theme: {
    extend: {
      colors: {
        cream: '#FAF7F2',
        creamdark: '#F1EBDD',
        olive: { 50: '#F7F1E1', 100: '#EFE2C4', 300: '#C8AD6C', 500: '#93762E', 600: '#7E6427', 700: '#6B5522', 800: '#4A3A18' },
        ink: '#2E2A22',
        muted: '#8A8477',
      },
      fontFamily: {
        serif: ['Fraunces', 'Georgia', 'serif'],
        sans: ['Inter', 'ui-sans-serif', 'system-ui'],
      },
      // Las vistas usan w-4.5 / h-4.5 en algunos íconos; Tailwind no trae ese tamaño por defecto.
      spacing: { '4.5': '1.125rem' },
      boxShadow: {
        card: '0 1px 2px rgba(46,42,34,0.06), 0 8px 24px -12px rgba(46,42,34,0.12)',
      },
    },
  },
};
