<script setup>
import '../../css/custom.css';
</script>

<template>
    <div class="bg-body min-h-screen">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <header class="py-8 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-gray-50 border border-gray-200" />
                    <div class="flex flex-col leading-tight">
                        <span class="title-font">STRATIX</span>
                        <span class="t-mini text-gray-500">UI kit preview</span>
                    </div>
                </div>

                <nav class="hidden md:flex items-center gap-6">
                    <a class="t-small text-gray-700 hover:text-black transition-colors" href="#">Главная</a>
                    <a class="t-small text-gray-700 hover:text-black transition-colors" href="#">Компоненты</a>
                    <a class="t-small text-gray-700 hover:text-black transition-colors" href="#">Оплата</a>
                    <a class="t-small text-gray-700 hover:text-black transition-colors" href="#">Counter</a>
                </nav>

                <div class="flex items-center gap-3">
                    <button class="tag">Demo</button>
                    <button class="primary-btn">Войти</button>
                </div>
            </header>

            <main class="pb-16 space-y-8">
                <!-- HERO -->
                <section class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="content flex flex-col justify-center">
                        <h1 class="title-font">Минималистичный UI-kit</h1>
                        <p class="context mt-2 max-w-prose">
                            Витрина базовых классов: типографика, кнопки, блоки, инпуты и теги.
                            Фокусируйся на чистой сетке, контрастных уровнях текста и аккуратных состояниях.
                        </p>

                        <div class="mt-6 flex flex-wrap items-center gap-3">
                            <button class="primary-btn">Primary action</button>
                            <button class="tag">Secondary / chip</button>
                            <a class="t-small text-gray-700 underline underline-offset-4 hover:text-black" href="#">
                                Текстовая ссылка
                            </a>
                        </div>

                        <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="t-mini text-gray-600">Email</label>
                                <input class="input mt-2 w-full" placeholder="name@domain.com" />
                            </div>
                            <div>
                                <label class="t-mini text-gray-600">Поиск</label>
                                <input class="search-input mt-2 w-full" placeholder="Найти..." />
                            </div>
                        </div>
                    </div>

                    <div class="content">
                        <div class="flex items-center justify-between">
                            <h2 class="title">Контент-блок</h2>
                            <span class="tag">new</span>
                        </div>

                        <p class="context mt-2">
                            Фон: <span class="font-medium text-gray-900">bg-gray-50</span>, отступы: <span class="font-medium text-gray-900">p-6</span>,
                            скругление: <span class="font-medium text-gray-900">rounded-xl</span>.
                        </p>

                        <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="p-4 rounded-xl bg-white border border-gray-200">
                                <div class="title-2">Card</div>
                                <div class="t-small text-gray-600 mt-1">
                                    Белая карточка на фоне content-блока.
                                </div>
                            </div>
                            <div class="p-4 rounded-xl bg-white border border-gray-200">
                                <div class="title-2">Card</div>
                                <div class="t-small text-gray-600 mt-1">
                                    Подходит для списков / настроек.
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- TYPOGRAPHY -->
                <section class="content">
                    <div class="flex items-end justify-between gap-6 flex-wrap">
                        <div>
                            <h2 class="title">Типографика</h2>
                            <p class="context mt-1">Основной текст — Roboto. Тайтл — Roboto или customFont.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="tag">t-body</span>
                            <span class="tag">t-small</span>
                            <span class="tag">t-mini</span>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="space-y-3">
                            <div class="title-font">Title (customFont)</div>
                            <div class="title">Title (Roboto)</div>
                            <div class="title-2">Subtitle (Roboto)</div>
                            <div class="t-body text-gray-900">Body text — базовый размер.</div>
                            <div class="t-small text-gray-700">Small text — для подписей, метаданных.</div>
                            <div class="t-mini text-gray-600">Mini text — для label/secondary info.</div>
                        </div>
                        <div class="space-y-3">
                            <p class="context">
                                Пример “context”: нейтральный, не слишком бледный.
                            </p>
                            <p class="t-small text-gray-700">
                                Смысловая иерархия строится за счёт размера, насыщенности и цвета.
                            </p>
                            <p class="t-mini text-gray-600">
                                Минимум декоративности, максимум читаемости.
                            </p>
                        </div>
                    </div>
                </section>

                <!-- INTERACTIONS -->
                <section class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="content">
                        <h2 class="title">Теги / интерактив</h2>
                        <p class="context mt-1">Hover: фон #276d4c, текст белый, рамка исчезает.</p>

                        <div class="mt-5 flex flex-wrap gap-2">
                            <button class="tag">Tag</button>
                            <button class="tag">Filter</button>
                            <button class="tag">Action</button>
                            <button class="tag">Chip</button>
                            <button class="tag">Hover me</button>
                        </div>
                    </div>

                    <div class="content-dark">
                        <h2 class="title">Тёмный блок</h2>
                        <p class="t-small text-white/80 mt-1">
                            Цвет фона: #1a1a1a. Сетка/отступы такие же как у светлого content-блока.
                        </p>

                        <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                                <div class="t-small text-white">Item</div>
                                <div class="t-mini text-white/70 mt-1">Secondary text</div>
                            </div>
                            <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                                <div class="t-small text-white">Item</div>
                                <div class="t-mini text-white/70 mt-1">Secondary text</div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- FORMS -->
                <section class="content">
                    <h2 class="title">Инпуты</h2>
                    <p class="context mt-1">Focus ring: #276d4c. Попробуй кликнуть/Tab.</p>

                    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="t-mini text-gray-600">Имя</label>
                            <input class="input mt-2 w-full" placeholder="Иван" />
                        </div>
                        <div>
                            <label class="t-mini text-gray-600">Пароль</label>
                            <input class="input mt-2 w-full" type="password" placeholder="••••••••" />
                        </div>
                        <div class="md:col-span-2">
                            <label class="t-mini text-gray-600">Комментарий</label>
                            <textarea class="input mt-2 w-full min-h-28 resize-y" placeholder="Короткий текст..." />
                        </div>
                    </div>

                    <div class="mt-6 flex items-center gap-3">
                        <button class="primary-btn">Сохранить</button>
                        <button class="primary-btn" disabled>Disabled</button>
                    </div>
                </section>
            </main>

            <footer class="py-10 border-t border-gray-200">
                <div class="flex flex-col sm:flex-row gap-2 sm:items-center sm:justify-between">
                    <div class="t-small text-gray-600">
                        Boilerplate UI kit — light theme
                    </div>
                    <div class="t-small text-gray-600">
                        Colors: <span class="font-medium text-gray-900">#276d4c</span>, <span class="font-medium text-gray-900">#1a1a1a</span>
                    </div>
                </div>
            </footer>
        </div>
    </div>
</template>
