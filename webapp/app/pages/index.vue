<script setup lang="ts">
/**
 * Signed-in home. Replace the body; keep the shape — a page reads state from a store, renders,
 * and leaves the API talking to useApi()/the store.
 */
const auth = useAuthStore()
const { t, locale } = useI18n()

useHead({ title: t('home.title') })
</script>

<template>
  <v-container class="py-6">
    <h1 class="text-h5 font-weight-bold mb-1">{{ $t('home.greeting', { name: auth.user?.name ?? '' }) }}</h1>
    <p class="text-body-2 text-medium-emphasis mb-6">{{ $t('home.subtitle') }}</p>

    <v-row>
      <v-col cols="12" md="6">
        <v-card>
          <v-card-item>
            <v-card-title>{{ $t('home.session_title') }}</v-card-title>
          </v-card-item>
          <v-card-text>
            <v-list density="compact" class="bg-transparent">
              <v-list-item :title="$t('common.email')" :subtitle="auth.user?.email" />
              <v-list-item
                :title="$t('home.roles')"
                :subtitle="auth.user?.roles?.join(', ') || $t('home.none')"
              />
              <v-list-item
                :title="$t('home.permissions')"
                :subtitle="String(auth.user?.permissions?.length ?? 0)"
              />
              <v-list-item :title="$t('home.today')" :subtitle="formatDate(new Date(), locale)" />
            </v-list>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>
