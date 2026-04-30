import axios from 'axios';

const COPY_COOLDOWN_MS = 10_000;
const copyCooldownTimers = new Map();

function createWebhookCopyHandler({ nodeId, onWebhookLog }) {
    return {
        async execute() {
            if (!nodeId) {
                return;
            }

            if (copyCooldownTimers.has(nodeId)) {
                onWebhookLog?.({
                    nodeId,
                    body: 'Повторное копирование будет доступно через 10 секунд.',
                });
                return;
            }

            copyCooldownTimers.set(
                nodeId,
                setTimeout(() => {
                    copyCooldownTimers.delete(nodeId);
                }, COPY_COOLDOWN_MS)
            );

            try {
                const response = await axios.get(route('webhook.token', nodeId));
                const token = response?.data?.token;

                if (!token) {
                    throw new Error('Webhook token is missing');
                }

                const webhookUrl = `${window.location.origin}/webhooks/${token}`;

                await navigator.clipboard.writeText(webhookUrl);

                onWebhookLog?.({
                    nodeId,
                    body: `Webhook URL скопирован:\n${webhookUrl}`,
                });
            } catch (error) {
                console.error('Ошибка копирования webhook URL:', error);
                onWebhookLog?.({
                    nodeId,
                    body: 'Не удалось скопировать webhook URL.',
                });
            }
        },
    };
}

export const buttonHandlers = {
    copy_webhook_token: createWebhookCopyHandler,
};

export function createButtonHandler(handlerKey, workflowId, nodeId, onWebhookLog, loadingState) {
    void workflowId;
    void loadingState;

    const handlerFactory = buttonHandlers[handlerKey];
    if (!handlerFactory) {
        console.warn(`Button handler "${handlerKey}" is not configured`);
        return null;
    }

    return handlerFactory({
        nodeId,
        onWebhookLog,
    });
}


