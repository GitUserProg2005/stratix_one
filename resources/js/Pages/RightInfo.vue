<template>
  <aside class="h-full flex flex-col bg-body p-4">
    <!-- Верхняя часть — пустое пространство -->
    <div class="flex-1 min-h-0" />

    <!-- Блок с картинкой, градиентом и CTA -->
    <div class="relative mt-auto flex flex-col items-center">
      <!-- Область картинки: градиент сверху (прозрачно → чёрный) накрывает изображение -->
      <div class="relative w-full flex justify-center overflow-hidden">
        <div
          class="absolute inset-0 z-10 pointer-events-none"
          style="background: linear-gradient(to bottom, transparent 0%, #0f0f0f 80%, #0f0f0f 70%);"
        />
        <img
          src="/img/phone.png"
          alt=""
          class="relative z-0 w-48 -mb-4 object-contain object-bottom"
        />
      </div>

      <!-- Текст и кнопка под «растворившейся» картинкой -->
      <div class="px-2 pb-2 pt-2 w-full space-y-4">
        <h3 class="title-2">Также можете попробовать приложение</h3>
        <p class="context">
          Слушай любимую музыку в приложении и наслаждайся высоким качеством звука
        </p>
        <button
          type="button"
          class="primary-btn mt-3 inline-flex items-center gap-2"
        >
          Установить приложение
          <i class="fa-solid fa-arrow-right text-xs" />
        </button>
      </div>
    </div>
  </aside>
</template>
