<template>
  <v-container class="fill-height d-flex align-center justify-center">
    <v-card class="text-center pa-6" elevation="4" rounded="lg" max-width="500">
      <v-avatar color="primary" size="64" class="mb-4">
        <v-icon size="32" color="white">mdi-rocket-launch</v-icon>
      </v-avatar>
      <v-card-title class="text-h5 font-weight-bold">
        VotaBoilerplate is Ready!
      </v-card-title>
      <v-card-text>
        <p class="mb-4">Your Laravel 12 + Nuxt 3 AI-First environment is up and running.</p>
        <p class="text-caption text-medium-emphasis">Use the Agentic IDE to start building features.</p>
      </v-card-text>
      <v-card-actions class="justify-center">
        <v-btn color="primary" variant="flat" href="/api/v1" target="_blank">
          Check API
        </v-btn>
        <v-btn color="secondary" variant="outlined" href="/docs/api" target="_blank">
          View Swagger
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-container>
</template>
