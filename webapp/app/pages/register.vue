<script setup lang="ts">
/**
 * Sign up. Public — see utils/routes.ts.
 *
 * The password checklist is driven by utils/passwordPolicy, which mirrors the server's rule.
 * Never re-state the rule in a `:rules` array here: two definitions drift, and the version the
 * user sees is the one that lies.
 */
definePageMeta({ layout: false })

const auth = useAuthStore()
const { t } = useI18n()

useHead({ title: t('auth.sign_up') })

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})
const submitting = ref(false)
const errors = ref<Record<string, string>>({})

const canSubmit = computed(() =>
  form.name !== ''
  && form.email !== ''
  && passwordMeetsPolicy(form.password)
  && form.password === form.password_confirmation)

const submit = singleFlight(async () => {
  submitting.value = true
  errors.value = {}
  try {
    await auth.register({ ...form })
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
        <v-card-title class="text-h6">{{ $t('auth.sign_up') }}</v-card-title>
      </v-card-item>

      <v-card-text>
        <v-form @submit.prevent="submit">
          <v-text-field
            v-model="form.name"
            :label="$t('common.name')"
            :error-messages="errors.name"
            autocomplete="name"
          />
          <v-text-field
            v-model="form.email"
            :label="$t('common.email')"
            :error-messages="errors.email"
            type="email"
            autocomplete="email"
          />
          <v-text-field
            v-model="form.password"
            :label="$t('common.password')"
            :error-messages="errors.password"
            type="password"
            autocomplete="new-password"
          />
          <PasswordRequirements :model-value="form.password" />
          <v-text-field
            v-model="form.password_confirmation"
            :label="$t('common.password_confirm')"
            type="password"
            autocomplete="new-password"
          />
          <v-btn
            type="submit"
            color="primary"
            variant="flat"
            block
            :disabled="!canSubmit"
            :loading="submitting"
            class="mt-2"
          >
            {{ $t('auth.sign_up') }}
          </v-btn>
        </v-form>
      </v-card-text>

      <v-card-actions class="justify-center">
        <NuxtLink to="/login" class="text-body-2 text-primary text-decoration-none">
          {{ $t('auth.have_account') }}
        </NuxtLink>
      </v-card-actions>
    </v-card>
  </v-container>
</template>
