<script setup lang="ts">
/**
 * App shell. <AppSnackbar /> is mounted here exactly once — see composables/useNotification.ts.
 */
const { t } = useI18n()

useHead({
  titleTemplate: (title?: string) => (title ? `${title} — ${t('app.name')}` : t('app.name')),
})
</script>

<template>
  <v-app>
    <NuxtLayout>
      <NuxtPage />
    </NuxtLayout>
    <AppSnackbar />
  </v-app>
</template>
