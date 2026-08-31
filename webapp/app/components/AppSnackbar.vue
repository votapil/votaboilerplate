<script setup lang="ts">
/**
 * The app's only snackbar. Mounted once in app.vue; everything else raises messages through
 * useNotification(). Do not add a second one — two snackbars fight over the same corner.
 */
const notification = useNotification()
</script>

<template>
  <v-snackbar
    v-model="notification.state.show"
    :color="notification.state.color"
    :timeout="notification.state.timeout"
    location="bottom center"
    rounded="pill"
  >
    <div class="d-flex align-center">
      <v-icon size="20" class="mr-2">{{ notification.state.icon }}</v-icon>
      <span class="text-body-2">{{ notification.state.text }}</span>
    </div>
    <template #actions>
      <v-btn
        variant="text"
        size="small"
        icon="mdi-close"
        :aria-label="$t('common.close')"
        @click="notification.state.show = false"
      />
    </template>
  </v-snackbar>
</template>
