import axios from 'axios';

/**
 * Универсальный stopListen для любых "инстансов" (трек, сниппет и т.д.)
 * @param {'back'|'ended'} reason - причина остановки
 * @param {number} listenTime - сколько секунд пользователь слушал
 * @param {number} duration - полная длительность (сек)
 * @param {string} instance - тип инстанса, например 'track', 'snippet'
 * @param {number} instanceId - id инстанса
 * @param {string} [urlRoute] - если маршрут отличается от стандартного `/instance/:id/stop`
 */
export function stopListen(
        reason, listen_time, duration, instance,
        instanceId, urlRoute=null

    ) {
    if (!['back', 'ended'].includes(reason) || instanceId == null) return;
    const listenTime = Number(listen_time);
    const dur = Number(duration);
    if (!Number.isFinite(listenTime) || !Number.isFinite(dur)) return;

    const url = urlRoute ?? `/${instance}/${instanceId}/stop`;

    console.log(`stopListen: ${instance}#${instanceId}`, reason, listenTime, dur);

    axios.post(url, {
        reason,
        listen_time: listenTime,
        duration: dur,
    }).catch(() => {});
}
