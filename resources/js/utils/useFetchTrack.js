import { router } from '@inertiajs/vue3';

/**
 * Переход на страницу трека (Player).
 * @param {number} trackId - id трека
 * @param {Object} [options] - rightNow: играть сразу; back: URL или path для кнопки «Назад»
 */
export function fetchTrack(trackId, options = {}) {
    const { rightNow = null, back = null } = typeof options === 'object' ? options : { rightNow: options };
    const params = { rightNow };
    // Ziggy возвращает полный URL; сервер принимает только путь (защита от open redirect)
    if (back) {
        params.back = back.startsWith('/') ? back : new URL(back, window.location.origin).pathname;
    }
    router.get(route('tracks.show', trackId), params);
}
