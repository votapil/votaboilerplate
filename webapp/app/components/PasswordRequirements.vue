<script setup lang="ts">
/**
 * Live checklist for a new password. The ticks come from utils/passwordPolicy, which mirrors
 * the server rule character for character — so nothing here can go green while the API refuses.
 *
 * One wrapped row of short items, not a four-line list: on a phone the form is already tall,
 * and the point is a glance, not reading.
 */
import { checkPassword } from '~/utils/passwordPolicy'

const props = defineProps<{ modelValue: string }>()

const { t } = useI18n()

const items = computed(() => {
  const checks = checkPassword(props.modelValue)

  return [
    { key: 'length', ok: checks.length, label: t('password_req.length') },
    { key: 'letter', ok: checks.letter, label: t('password_req.letter') },
    { key: 'digit', ok: checks.digit, label: t('password_req.digit') },
    { key: 'symbol', ok: checks.symbol, label: t('password_req.symbol') },
  ]
})
</script>

<template>
  <div class="d-flex flex-wrap align-center ga-2 mb-3">
    <span
      v-for="item in items"
      :key="item.key"
      class="d-inline-flex align-center text-caption"
      :class="item.ok ? 'text-success' : 'text-medium-emphasis'"
    >
      <v-icon size="14" class="mr-1">{{ item.ok ? 'mdi-check-circle' : 'mdi-circle-outline' }}</v-icon>
      {{ item.label }}
    </span>
  </div>
</template>
