<script setup>
import { useForm, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import GroupedByNumber from "../components/GroupedByNumber.vue";
import GroupedByFace from "../components/GroupedByFace.vue";

const props = defineProps({
  imagesByNumberOfPeople: Object,
  imagesByFace: Object,
});

const loading = ref(false);
const tabName = ref("number");

const form = useForm({ files: [] });
const submit = () => {
  form.post("/upload", {
    onBefore: () => (loading.value = true),
    onSuccess: () => form.reset(),
    onFinish: () => (loading.value = false),
  });
};

const reset = () => {
  router.post("/reset", null, {
    onBefore: () => (loading.value = true),
    onFinish: () => (loading.value = false),
  });
};

const imagesOfAll = computed(() => {
  let files = [];
  Object.keys(props.imagesByNumberOfPeople).forEach((key) => {
    files = [...files, ...props.imagesByNumberOfPeople[key]];
  });
  return files;
});

const isShowImage = ref(false);
const imageUrl = ref("");
const showImage = (url) => {
  isShowImage.value = true;
  imageUrl.value = url;
};
</script>

<template>
  <v-overlay v-model="loading" class="align-center justify-center" persistent>
    <v-progress-circular color="grey-lighten-4" size="50" indeterminate />
  </v-overlay>
  <v-dialog
    v-model="isShowImage"
    max-width="80%"
    height="100%"
    class="align-center justify-center"
    close-on-content-click
  >
    <v-img :src="imageUrl" />
  </v-dialog>
  <v-container>
    <v-row class="my-5" v-if="imagesOfAll.length > 0">
      <v-col class="d-flex justify-start" cols="12">
        <v-btn
          type="button"
          prepend-icon="mdi-reload"
          variant="elevated"
          color="grey-darken-2"
          @click="reset"
          >登録済の写真を削除
        </v-btn>
      </v-col>
    </v-row>
    <v-form @submit.prevent="submit">
      <v-row class="my-5" no-gutters>
        <v-col cols="12" sm="9" md="10">
          <v-file-input
            v-model="form.files"
            show-size
            accept="image/png, image/jpeg"
            prepend-icon="mdi-camera"
            bg-color="grey-lighten-5"
            density="comfortable"
            label="アップロードする写真を選択する"
            variant="solo"
            multiple
            counter
            clearable
            :error-messages="form.errors.files"
            class="mr-sm-3"
          />
        </v-col>
        <v-col cols="12" sm="3" md="2">
          <v-btn
            type="submit"
            class="w-100 mt-1 mt-sm-0"
            prepend-icon="mdi-upload"
            variant="elevated"
            color="green-lighten-1"
            >アップロード
          </v-btn>
        </v-col>
      </v-row>
    </v-form>
    <template v-if="imagesOfAll.length <= 0">
      <v-chip class="pa-5 w-100" label color="error">
        画像が登録されていません。</v-chip
      >
    </template>
    <template v-else>
      <v-tabs v-model="tabName" color="grey-darken-4" align-tabs="center">
        <v-tab value="number">人数別</v-tab>
        <v-tab value="face">人物別</v-tab>
      </v-tabs>
      <v-window v-model="tabName">
        <v-window-item value="number">
          <GroupedByNumber
            :images-of-all="imagesOfAll"
            :images-by-number-of-people="imagesByNumberOfPeople"
            @showImage="showImage"
          />
        </v-window-item>
        <v-window-item value="face">
          <GroupedByFace
            :images-of-all="imagesOfAll"
            :images-by-face="imagesByFace"
            @showImage="showImage"
          />
        </v-window-item>
      </v-window>
    </template>
  </v-container>
</template>
