import type { Config } from 'tailwindcss'
import forms from '@tailwindcss/forms'

export default {
  content: [
    './resources/js/**/*.{vue,ts,tsx}',
    './resources/views/**/*.blade.php',
  ],
  theme: {
    extend: {
      colors: {
        black:     '#000000',
        'bg-soft': '#0D0D0D',
        surface:   '#161616',
        surface2:  '#1C1C1C',
        surface3:  '#242424',
        green: {
          DEFAULT: '#1DF412',
          dark:    '#15C40F',
          subtle:  'rgba(29, 244, 18, 0.08)',
        },
        border: {
          DEFAULT: 'rgba(255, 255, 255, 0.06)',
          strong:  'rgba(255, 255, 255, 0.10)',
        },
        muted:   '#9CA3AF',
        dim:     '#6B7280',
        danger:  '#EF4444',
        warning: '#F59E0B',
      },
      fontFamily: {
        sans:    ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
        display: ['Barlow Condensed', 'Barlow', 'system-ui', 'sans-serif'],
      },
      borderRadius: {
        card: '18px',
        btn:  '12px',
        input:'14px',
      },
      screens: {
        xs: '390px',
      },
    },
  },
  plugins: [forms],
} satisfies Config
