<template>
  <div class="logo-wrapper" :class="`variant-${variant} size-${size}`">
    <img
      v-if="variant === 'navbar' || variant === 'sidebar'"
      :src="`/logo.svg`"
      :alt="altText"
      :class="logoClass"
    />
    <img
      v-else-if="variant === 'auth'"
      :src="`/icon-main.svg`"
      :alt="altText"
      :class="logoClass"
    />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Props {
  variant?: 'navbar' | 'auth' | 'sidebar';
  size?: 'small' | 'medium' | 'large';
  altText?: string;
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'navbar',
  size: 'medium',
  altText: 'Tu Mejor Versión',
});

const logoClass = computed(() => {
  // navbar/sidebar usan logo.svg (wordmark horizontal 826×78) → dimensionar por ALTURA.
  // auth usa icon-main.svg (icono vertical 64×106) → dimensionar por ANCHO.
  const isWideLogo = props.variant === 'navbar' || props.variant === 'sidebar';

  if (isWideLogo) {
    const heightBySize = {
      small: 'h-7 w-auto',
      medium: props.variant === 'sidebar' ? 'h-8 w-auto' : 'h-9 w-auto',
      large: 'h-11 w-auto',
    };
    return `${heightBySize[props.size]} object-contain max-w-full`;
  }

  // variant === 'auth' (icono vertical)
  const widthBySize = {
    small: 'w-12 h-auto',
    medium: 'w-20 h-auto',
    large: 'w-24 h-auto',
  };
  return `${widthBySize[props.size]} object-contain`;
});
</script>

<style scoped>
.logo-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
}

.logo-wrapper img {
  max-width: 100%;
  display: block;
}

.variant-navbar {
  padding: 0;
}

.variant-sidebar {
  padding: 0;
}

.variant-auth {
  padding: 0.5rem;
}
</style>
