import { router } from "@inertiajs/vue3";

/**
 * @param {number} snippetId
 * @param {number[]} otherSnippetIds
 */
export function useFetchSnippets(snippetId, otherSnippetIds = []) {
    router.get(route('reels.index'), {
        snippetId,
        otherSnippetIds,
    }, {
        preserveScroll: true,
        preserveState: true,
    });
}
