// Script para generar todos los íconos PWA a partir del ícono base
// Usa jimp con API CommonJS (compatible con Node.js v22)
const { Jimp } = require('jimp')
const { mkdirSync } = require('fs')
const path = require('path')

// Directorio de salida en public/icons
const outputDir = path.join(__dirname, 'public', 'icons')
mkdirSync(outputDir, { recursive: true })

// Ruta al ícono base de los assets de plantillas
const sourceIcon = path.join(
  __dirname,
  '..',
  'plantillas',
  'Tu mejor version',
  'assets',
  'icon-green.png'
)

// Tamaños requeridos por el manifest PWA
const sizes = [72, 96, 128, 144, 152, 192, 384, 512]

console.log('Generando íconos PWA desde:', sourceIcon)

async function generateIcons() {
  try {
    const image = await Jimp.read(sourceIcon)

    for (const size of sizes) {
      const outputPath = path.join(outputDir, `icon-${size}.png`)
      const resized = image.clone().resize({ w: size, h: size })
      await resized.write(outputPath)
      console.log(`✓ Creado: public/icons/icon-${size}.png`)
    }

    console.log('\n✅ Todos los íconos PWA generados correctamente.')
  } catch (err) {
    console.error('Error al generar íconos:', err.message)
    process.exit(1)
  }
}

generateIcons()
