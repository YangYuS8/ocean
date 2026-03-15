// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  modules: [['@nuxt/ui', { fonts: false }], '@nuxt/icon', '@vueuse/nuxt'],
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },
  css: ['~/assets/css/main.css'],
  icon: {
    localApiEndpoint: '/_nuxt_icon',
    serverBundle: {
      collections: ['lucide']
    }
  },
  runtimeConfig: {
    public: {
      apiBase: '',
    },
  },
})
