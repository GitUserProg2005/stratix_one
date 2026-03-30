/**
 * Кнопки действий на нодах (webhook и т.д.) — в этом проекте не используются.
 */
export const buttonHandlers = {};

export function createButtonHandler(handlerKey, workflowId, onWebhookLog, loadingState) {
    console.warn(`Button handler "${handlerKey}" is not configured`);

    return null;
}
