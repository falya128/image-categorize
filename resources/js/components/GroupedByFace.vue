<script setup>
import { ref, computed } from "vue";

const props = defineProps({
  imagesOfAll: Array,
  imagesByFace: Object,
});
const emit = defineEmits(["showImage"]);

const selectedFaceId = ref();
const imagesOfSelectedFaceId = computed(() => {
  if (selectedFaceId.value) {
    const selectedImage = Object.values(props.imagesByFace).find(
      (image) => image.face_id == selectedFaceId.value
    );
    return selectedImage.files;
  } else {
    return props.imagesOfAll;
  }
});

const createFaceImageUrl = (image) => {
  let url = image.url;
  url += `?width=${image.bounding_box.width}`;
  url += `&height=${image.bounding_box.height}`;
  url += `&top=${image.bounding_box.top}`;
  url += `&left=${image.bounding_box.left}`;
  return url;
};
</script>

<template>
  <v-row>
    <v-col class="mt-2 py-0" cols="12">
      <v-slide-group v-model="selectedFaceId" class="pa-4" show-arrows>
        <v-slide-group-item
          v-for="image in imagesByFace"
          :key="image.face_id"
          :value="image.face_id"
          v-slot="{ toggle, isSelected }"
        >
          <v-avatar
            tag="button"
            size="70"
            @click="toggle"
            class="ma-3"
            :color="isSelected ? 'green' : 'grey-lighten-3'"
            variant="outlined"
          >
            <v-img :src="createFaceImageUrl(image)" cover>
              <template v-slot:placeholder>
                <v-sheet height="100%" color="grey-lighten-2">
                  <div class="d-flex align-center justify-center fill-height">
                    <v-progress-circular color="grey-lighten-4" indeterminate />
                  </div>
                </v-sheet>
              </template>
            </v-img>
          </v-avatar>
        </v-slide-group-item>
      </v-slide-group>
    </v-col>
  </v-row>
  <v-row>
    <v-col
      v-for="image in imagesOfSelectedFaceId"
      :key="image.name"
      cols="6"
      md="2"
    >
      <v-img
        :src="image.url"
        height="150"
        @click="emit('showImage', image.url)"
      >
        <template v-slot:placeholder>
          <v-sheet height="150" color="grey-lighten-2">
            <div class="d-flex align-center justify-center fill-height">
              <v-progress-circular color="grey-lighten-4" indeterminate />
            </div>
          </v-sheet>
        </template>
      </v-img>
    </v-col>
  </v-row>
</template>

<style scoped>
.v-avatar {
  border-width: 4px;
}
</style>
