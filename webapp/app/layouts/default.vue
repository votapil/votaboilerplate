<script setup lang="ts">
/**
 * Navigation shell for signed-in pages.
 *
 * The layout IS the one-handed-mobile rule, not the sentence about it: on a phone the primary
 * sections sit in a bottom bar under the thumb and there is no top navigation to reach for; on
 * a desktop the same items become a rail. Add sections here rather than inventing per-page
 * navigation, and keep the bottom bar at four items plus "more" — a fifth is a tap nobody hits.
 *
 * `mobile` comes from the GLOBAL Vuetify breakpoint (nuxt.config.ts -> display.mobileBreakpoint).
 * Do not pass a local override here: two thresholds in one app means the bar and the drawer
 * disagree about which one is showing.
 */
import { useDisplay } from 'vuetify'

const { mobile } = useDisplay()
const auth = useAuthStore()
const drawer = ref(false)

interface NavItem {
  to: string
  icon: string
  labelKey: string
  /** When set, the item is hidden unless the user holds this permission. */
  permission?: string
}

const items: NavItem[] = [
  { to: '/', icon: 'mdi-home-outline', labelKey: 'nav.home' },
  { to: '/settings', icon: 'mdi-cog-outline', labelKey: 'nav.settings' },
  { to: '/admin-area', icon: 'mdi-shield-account-outline', labelKey: 'nav.admin', permission: PERMISSIONS.adminAccess },
]

// Hiding the link is courtesy; the page itself is gated by definePageMeta({ permission }).
const visibleItems = computed(() => items.filter(item => !item.permission || auth.can(item.permission)))
</script>

<template>
  <v-navigation-drawer v-model="drawer" :temporary="mobile" :permanent="!mobile" location="left">
    <v-list nav density="comfortable">
      <v-list-item
        v-for="item in visibleItems"
        :key="item.to"
        :to="item.to"
        :prepend-icon="item.icon"
        :title="$t(item.labelKey)"
      />
    </v-list>

    <template #append>
      <v-list nav density="comfortable">
        <v-list-item
          prepend-icon="mdi-logout"
          :title="$t('common.logout')"
          @click="auth.logout()"
        />
      </v-list>
    </template>
  </v-navigation-drawer>

  <v-app-bar v-if="!mobile" flat border="b">
    <v-app-bar-title>{{ $t('app.name') }}</v-app-bar-title>
    <template #append>
      <span class="text-body-2 text-medium-emphasis mr-2">{{ auth.user?.name }}</span>
    </template>
  </v-app-bar>

  <v-main>
    <slot />
  </v-main>

  <v-bottom-navigation v-if="mobile" grow color="primary" border="t">
    <v-btn v-for="item in visibleItems" :key="item.to" :to="item.to" :value="item.to">
      <v-icon>{{ item.icon }}</v-icon>
      <span>{{ $t(item.labelKey) }}</span>
    </v-btn>
    <v-btn value="more" @click="drawer = !drawer">
      <v-icon>mdi-menu</v-icon>
      <span>{{ $t('nav.more') }}</span>
    </v-btn>
  </v-bottom-navigation>
</template>
