<script setup lang="ts">
/**
 * Sign in. Public — listed in utils/routes.ts, which the global auth guard consults.
 *
 * Two things worth copying into every form:
 *  - the submit handler is wrapped in singleFlight(), so a double tap cannot fire it twice;
 *  - 422s are read back into per-field messages with fieldErrors(), while useApi() has already
 *    shown the backend's localised summary in a toast.
 */
definePageMeta({ layout: false })

const auth = useAuthStore()
const { t } = useI18n()

useHead({ title: t('auth.sign_in') })

const email = ref('')
const password = ref('')
const showPassword = ref(false)
const submitting = ref(false)
const errors = ref<Record<string, string>>({})

const submit = singleFlight(async () => {
  submitting.value = true
  errors.value = {}
  try {
    await auth.login(email.value, password.value)
    await navigateTo('/')
  } catch (e) {
    errors.value = fieldErrors(e)
  } finally {
    submitting.value = false
  }
})
</script>

<template>
  <v-container class="fill-height d-flex align-center justify-center">
    <v-card max-width="420" width="100%" class="pa-2">
      <v-card-item>
        <v-card-title class="text-h6">{{ $t('auth.sign_in') }}</v-card-title>
        <v-card-subtitle>{{ $t('auth.sign_in_subtitle') }}</v-card-subtitle>
      </v-card-item>

      <v-card-text>
        <v-form @submit.prevent="submit">
          <v-text-field
            v-model="email"
            :label="$t('common.email')"
            :error-messages="errors.email"
            type="email"
            autocomplete="email"
            prepend-inner-icon="mdi-email-outline"
          />
          <v-text-field
            v-model="password"
            :label="$t('common.password')"
            :error-messages="errors.password"
            :type="showPassword ? 'text' : 'password'"
            :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
            autocomplete="current-password"
            prepend-inner-icon="mdi-lock-outline"
            @click:append-inner="showPassword = !showPassword"
          />
          <v-btn type="submit" color="primary" variant="flat" block :loading="submitting" class="mt-2">
            {{ $t('auth.sign_in') }}
          </v-btn>
        </v-form>
      </v-card-text>

      <v-card-actions class="justify-center">
        <NuxtLink to="/register" class="text-body-2 text-primary text-decoration-none">
          {{ $t('auth.no_account') }}
        </NuxtLink>
      </v-card-actions>
    </v-card>
  </v-container>
</template>
