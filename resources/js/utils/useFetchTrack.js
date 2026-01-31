import { router } from '@inertiajs/vue3';

export function fetchTrack(trackId, rightNow=null) {
    router.get(route('tracks.show', trackId), { rightNow }, 
    {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}
