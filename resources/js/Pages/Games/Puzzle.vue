<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';

const GRID_SIZE = 3;

// Правильное изображение для каждой позиции [row][col]
const correctImages = [
    [
        '/img/nodes/gigachat.png',
        '/img/nodes/gigachat.png',
        '/img/nodes/gigachat.png',
    ],
    [
        '/img/nodes/gigachat.png',
        '/img/nodes/gigachat.png',
        '/img/nodes/gigachat.png',
    ],
    [
        '/img/nodes/gigachat.png',
        '/img/nodes/gigachat.png',
        '/img/nodes/gigachat.png',
    ],
];

// Правильный поворот для каждой позиции [row][col] (0, 1, 2, 3) = (0°, 90°, 180°, 270°)
const correctRotation = [
    [0, 2, 1],
    [3, 0, 2],
    [1, 3, 0],
];

// Текущее состояние игрового поля: каждый элемент содержит { image, rotation }
const board = ref(
    correctImages.map((row, rowIndex) =>
        row.map((image, colIndex) => ({
            image,
            rotation: (correctRotation[rowIndex][colIndex] + 1) % 4, // Начинаем с неправильного поворота
        }))
    )
);

// Выбранная ячейка для перемещения
const selectedCell = ref(null);

const isWin = computed(() => {
    for (let row = 0; row < GRID_SIZE; row++) {
        for (let col = 0; col < GRID_SIZE; col++) {
            const cell = board.value[row][col];
            const correctImage = correctImages[row][col];
            const correctRot = correctRotation[row][col];

            // Проверяем позицию (изображение)
            if (cell.image !== correctImage) {
                return false;
            }

            // Проверяем поворот (должен совпадать с correctRotation)
            if (cell.rotation !== correctRot) {
                return false;
            }
        }
    }
    return true;
});

function selectCell(row, col) {
    if (isWin.value) return;

    if (selectedCell.value === null) {
        // Выбираем ячейку
        selectedCell.value = { row, col };
    } else {
        // Меняем местами ячейки
        const { row: prevRow, col: prevCol } = selectedCell.value;
        if (prevRow !== row || prevCol !== col) {
            const temp = board.value[prevRow][prevCol];
            board.value[prevRow][prevCol] = board.value[row][col];
            board.value[row][col] = temp;
        }
        selectedCell.value = null;
    }
}

function rotateCell(row, col, event) {
    if (isWin.value) return;
    event.stopPropagation(); // Чтобы не срабатывал selectCell
    board.value[row][col].rotation = (board.value[row][col].rotation + 1) % 4;
}

function resetGame() {
    board.value = correctImages.map((row, rowIndex) =>
        row.map((image, colIndex) => ({
            image,
            rotation: (correctRotation[rowIndex][colIndex] + 1) % 4,
        }))
    );
    selectedCell.value = null;
}
</script>

<template>
    <section class="flex flex-col items-center justify-center h-screen">
        <h2 class="text-2xl font-bold mb-4">
            {{ isWin ? 'Победа! Пазл собран!' : 'Расставьте кусочки по местам и поверните их' }}
        </h2>
        <p class="mb-4 text-gray-600">
            Клик по кубику — выбор, клик по другому — поменять местами. Клик по кнопке ↻ — повернуть на 90°.
        </p>
        <div class="bg-content flex flex-col p-4 rounded-lg shadow-lg">
            <div
                v-for="(row, rowIndex) in board"
                :key="rowIndex"
                class="flex flex-row"
            >
                <div
                    v-for="(cell, colIndex) in row"
                    :key="colIndex"
                    class="relative cursor-pointer transition-all duration-300 m-1"
                    :class="{
                        'ring-4 ring-blue-500': selectedCell && selectedCell.row === rowIndex && selectedCell.col === colIndex,
                        'ring-2 ring-green-500': cell.image === correctImages[rowIndex][colIndex] && cell.rotation === correctRotation[rowIndex][colIndex],
                        'ring-2 ring-red-500': cell.image !== correctImages[rowIndex][colIndex] || cell.rotation !== correctRotation[rowIndex][colIndex],
                    }"
                    @click="selectCell(rowIndex, colIndex)"
                >
                    <img
                        :src="cell.image"
                        class="object-contain w-32 h-32"
                        :style="{ transform: `rotate(${cell.rotation * 90}deg)` }"
                        alt=""
                    />
                    <button
                        class="absolute top-0 right-0 bg-gray-800 text-white w-6 h-6 rounded-bl-lg opacity-75 hover:opacity-100 flex items-center justify-center text-sm"
                        @click="rotateCell(rowIndex, colIndex, $event)"
                        title="Повернуть"
                    >
                        ↻
                    </button>
                </div>
            </div>
        </div>
        <button
            v-if="isWin"
            @click="resetGame"
            class="mt-6 px-6 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition"
        >
            Играть снова
        </button>
    </section>
</template>