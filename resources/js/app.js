import "./bootstrap";

import { createApp, h } from "vue";
import { createInertiaApp } from "@inertiajs/vue3";
import Layout from "./Pages/Layout.vue";

import "vuetify/styles";
import { createVuetify } from "vuetify";
import "@mdi/font/css/materialdesignicons.css";
const vuetify = createVuetify({
  icons: {
    defaultSet: "mdi",
  },
});

createInertiaApp({
  resolve: (name) => {
    const pages = import.meta.glob("./Pages/**/*.vue", { eager: true });
    let page = pages[`./Pages/${name}.vue`];
    page.default.layout = page.default.layout || Layout;
    return page;
  },
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(vuetify)
      .mount(el);
  },
});
