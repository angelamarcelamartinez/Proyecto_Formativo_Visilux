/**
 * Estilos del MÓDULO DEL OPTÓMETRA (public/assets/css/optometra.css).
 * Se carga DESPUÉS de panel.css, por eso no incluye el "preflight" (reset) de Tailwind.
 *
 * Si cambias clases en las vistas del módulo, regenera el CSS con:
 *   npx tailwindcss@3 -c tailwind.optometra.config.cjs -i resources/css/optometra.css -o public/assets/css/optometra.css --minify
 */
module.exports = {
    content: [
        './resources/views/layouts/optometra.blade.php',
        './resources/views/optometra/**/*.blade.php',
    ],
    corePlugins: { preflight: false },
    theme: {
        extend: {
            colors: {
                olive: {
                    50: '#F7F1E1', 100: '#EFE2C4', 200: '#E2CF9C', 300: '#C8AD6C', 400: '#B0924D',
                    500: '#93762E', 600: '#7E6427', 700: '#6B5522', 800: '#4A3A18', 900: '#33280F',
                },
                cream: '#FAF7F2',
                creamdark: '#F1EBDD',
                ink: '#2E2A22',
                muted: '#8A8477',
            },
            fontFamily: {
                serif: ['Fraunces', 'Georgia', 'serif'],
                sans: ['Inter', 'ui-sans-serif', 'system-ui'],
            },
            boxShadow: {
                card: '0 1px 2px rgba(46,42,34,.06), 0 8px 24px -12px rgba(46,42,34,.12)',
            },
            spacing: { 4.5: '1.125rem' },
        },
    },
};
