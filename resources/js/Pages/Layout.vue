<script setup>
import { Head } from "@inertiajs/vue3";
</script>

<template>
  <Head title="Image Categorize" />
  <v-app>
    <v-main class="bg-grey-lighten-3">
      <slot />
    </v-main>
  </v-app>
</template>
