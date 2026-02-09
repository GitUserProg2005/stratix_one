<script setup>
defineProps({
  src: {
    type: String,
    required: true,
  },
  isPlaying: {
    type: Boolean,
    default: false,
  },
  size: {
    type: String,
    default: 'w-48 h-48',
  },
});
</script>

<template>
    <div
      class="z-10 relative flex items-center justify-center"
      :class="size"
    >
      <!-- ВРАЩАЮЩИЙСЯ ДИСК -->
      <div
        class="absolute inset-0 rounded-full vinyl"
        :class="{ paused: !isPlaying }"
      >
        <!-- блики -->
        <div class="vinyl-gloss"></div>
      </div>
  
      <!-- обложка -->
      <img
        :src="src"
        alt=""
        class="relative z-10 w-2/3 h-2/3 rounded-full object-cover
               vinyl vinyl-cover"
        :class="{ paused: !isPlaying }"
      />
  
      <!-- отверстие (НЕ крутится) -->
      <div class="absolute z-20 w-3 h-3 rounded-full bg-black"></div>
    </div>
  </template>
  
  <style scoped>
  /* вращение */
@keyframes vinyl-spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.vinyl {
  background:
    /* дорожки */
    repeating-radial-gradient(
      circle,
      #0b0b0b 0px,
      #0b0b0b 2px,
      #0e0e0e 3px
    ),
    /* основной цвет */
    radial-gradient(
      circle at center,
      #111 0%,
      #050505 70%
    );

  animation: vinyl-spin 6s linear infinite;
}

.vinyl.paused {
  animation-play-state: paused;
}

/* мягкий блик */
.vinyl-gloss {
  position: absolute;
  inset: 0;
  border-radius: 9999px;

  background:
    linear-gradient(
      120deg,
      transparent 35%,
      rgba(242, 240, 240, 0.227) 45%,
      transparent 55%
    );

  opacity: 0.6;
  mix-blend-mode: screen;
  pointer-events: none;
}

/* обложка */
.vinyl-cover {
  box-shadow:
    0 0 0 5px #0a0a0a,
    0 12px 35px rgba(0,0,0,.7);
}

  </style>
  