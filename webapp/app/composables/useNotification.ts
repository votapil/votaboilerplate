/**
 * App-wide snackbar.
 *
 * The state lives at MODULE scope on purpose: any composable, store or plugin can raise a
 * message without a provide/inject chain, and exactly one <AppSnackbar /> in app.vue renders
 * it. `useApi()` depends on this — the two are a pair, do not move one without the other.
 */
interface NotificationState {
    show: boolean
    text: string
    color: 'success' | 'error' | 'warning' | 'info'
    icon: string
    timeout: number
}

const ICONS: Record<NotificationState['color'], string> = {
    success: 'mdi-check-circle',
    error: 'mdi-alert-circle',
    warning: 'mdi-alert',
    info: 'mdi-information',
}

const state = reactive<NotificationState>({
    show: false,
    text: '',
    color: 'success',
    icon: ICONS.success,
    timeout: 3000,
})

export function useNotification() {
    const notify = (
        text: string,
        color: NotificationState['color'] = 'success',
        timeout = 3000,
    ) => {
        state.text = text
        state.color = color
        state.icon = ICONS[color]
        state.timeout = timeout
        state.show = true
    }

    return {
        state,
        notify,
        success: (text: string) => notify(text, 'success'),
        error: (text: string) => notify(text, 'error', 5000),
        warning: (text: string) => notify(text, 'warning', 4000),
        info: (text: string) => notify(text, 'info'),
    }
}
