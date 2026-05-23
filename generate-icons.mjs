// Script para generar todos los íconos PWA a partir del ícono base
// Usa jimp (puro JavaScript, sin dependencias nativas de compilación)
import Jimp from 'jimp'
import { mkdirSync } from 'fs'
import { join, dirname } from 'path'
import { fileURLToPath } from 'url'

const __dirname = dirname(fileURLToPath(import.meta.url))

// Directorio de salida en public/icons
const outputDir = join(__dirname, 'public', 'icons')
mkdirSync(outputDir, { recursive: true })

// Ruta al ícono base de los assets de plantillas
const sourceIcon = join(
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

try {
  const image = await Jimp.read(sourceIcon)

  for (const size of sizes) {
    const outputPath = join(outputDir, `icon-${size}.png`)
    await image
      .clone()
      .resize(size, size)
      .writeAsync(outputPath)
    console.log(`✓ Creado: public/icons/icon-${size}.png`)
  }

  console.log('\n✅ Todos los íconos PWA generados correctamente.')
} catch (err) {
  console.error('Error al generar íconos:', err.message)
  process.exit(1)
}
