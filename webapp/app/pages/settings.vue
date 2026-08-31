<script setup lang="ts">
/**
 * Appearance and language.
 *
 * Theme: the preference is applied through useAppTheme() — the same function the boot plugin
 * uses — so a change here takes effect immediately AND survives a reload. Never call
 * `theme.change()` directly from a page; that is how the two paths drift apart.
 *
 * Language: `setLocale()` persists the choice, and useApi() sends it as `Accept-Language`, so
 * server-side messages switch language too.
 */
import { useTheme } from 'vuetify'
import type { ThemePreference } from '~/composables/useAppTheme'

const auth = useAuthStore()
const api = useApi()
const notification = useNotification()
const { t, locale, locales, setLocale } = useI18n()

useHead({ title: t('settings.title') })

const { applyTheme, getStoredPreference } = useAppTheme(useTheme())
const theme = ref<ThemePreference>(getStoredPreference())

const themeOptions: { value: ThemePreference, label: string }[] = [
  { value: 'system', label: t('settings.theme_system') },
  { value: 'light', label: t('settings.theme_light') },
  { value: 'dark', label: t('settings.theme_dark') },
]

const saving = ref(false)

/**
 * Persist to the profile so the choice follows the user to another device. Local state is
 * applied first: the UI must not wait for the network to change colour.
 */
const save = singleFlight(async () => {
  saving.value = true
  try {
    await api.apiPut('/api/v1/settings', { locale: locale.value, theme: theme.value })
    await auth.fetchMe()
    notification.success(t('settings.saved'))
  } catch {
    // useApi already reported the failure.
  } finally {
    saving.value = false
  }
})

watch(theme, value => applyTheme(value))
</script>

<template>
  <v-container class="py-6" max-width="720">
    <h1 class="text-h5 font-weight-bold mb-6">{{ $t('settings.title') }}</h1>

    <v-card class="mb-4">
      <v-card-item>
        <v-card-title>{{ $t('settings.appearance') }}</v-card-title>
      </v-card-item>
      <v-card-text>
        <v-select
          v-model="theme"
          :items="themeOptions"
          item-title="label"
          item-value="value"
          :label="$t('settings.theme')"
        />
        <v-select
          :model-value="locale"
          :items="locales"
          item-title="name"
          item-value="code"
          :label="$t('common.language')"
          @update:model-value="setLocale($event)"
        />
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn color="primary" variant="flat" :loading="saving" @click="save">
          {{ $t('common.save') }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-container>
</template>
