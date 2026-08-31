<script setup lang="ts">
/**
 * The worked example of a permission-gated page — copy this pattern, do not reinvent it.
 *
 * `definePageMeta({ permission })` is read by middleware/auth.global.ts, which redirects a user
 * without the permission back to the home page. That matters for direct navigation: hiding the
 * nav item (layouts/default.vue does that too) is cosmetics, the URL is still typeable.
 *
 * The name comes from utils/permissions.ts, the frontend mirror of the backend enum.
 */
// Explicit import, unlike the rest of the app: `definePageMeta` is a compiler macro whose
// argument is hoisted into a separate module at build time, where auto-imported bindings do
// not exist. Anything referenced inside it must be statically imported here.
import { PERMISSIONS } from '~/utils/permissions'

definePageMeta({ permission: PERMISSIONS.adminAccess })

const { t } = useI18n()

useHead({ title: t('admin_area.title') })
</script>

<template>
  <v-container class="py-6" max-width="720">
    <h1 class="text-h5 font-weight-bold mb-6">{{ $t('admin_area.title') }}</h1>

    <v-card>
      <v-card-item>
        <v-card-title>{{ $t('admin_area.gated_title') }}</v-card-title>
        <v-card-subtitle>{{ PERMISSIONS.adminAccess }}</v-card-subtitle>
      </v-card-item>
      <v-card-text>{{ $t('admin_area.gated_body') }}</v-card-text>
      <v-card-actions>
        <v-btn color="primary" variant="flat" href="/admin" target="_blank" prepend-icon="mdi-open-in-new">
          {{ $t('admin_area.open_panel') }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-container>
</template>
