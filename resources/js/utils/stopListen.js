import axios from 'axios';

/**
 * Отправляет данные о завершении прослушивания трека.
 * @param {'back'|'ended'} reason - причина остановки
 * @param {number} listen_time - на каком времени (сек) пользователь перестал слушать
 * @param {number} duration - полная длительность трека (сек)
 * @param {number} trackId - id трека
 */
export function stopListen(reason, listen_time, duration, trackId) {
    if (!['back', 'ended'].includes(reason) || trackId == null) return;
    const listenTime = Number(listen_time);
    const dur = Number(duration);
    if (!Number.isFinite(listenTime) || !Number.isFinite(dur)) return;

    console.log('stopListen', reason, listen_time, duration, trackId);

    axios.post(`/track/${trackId}/stop`, {
        reason,
        listen_time: listenTime,
        duration: dur,
    }).catch(() => {});
}
