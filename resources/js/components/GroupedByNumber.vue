<script setup>
import { ref, computed } from "vue";

const props = defineProps({
  imagesOfAll: Array,
  imagesByNumberOfPeople: Object,
});
const emit = defineEmits(["showImage"]);

const selectedNumber = ref();
const imagesOfSelectedNumber = computed(() => {
  if (selectedNumber.value) {
    return props.imagesByNumberOfPeople[selectedNumber.value];
  } else {
    return props.imagesOfAll;
  }
});
const selectableNumberList = computed(() =>
  Object.keys(props.imagesByNumberOfPeople)
);
</script>

<template>
  <v-row>
    <v-col cols="12">
      <v-item-group selected-class="bg-green" v-model="selectedNumber">
        <v-item
          v-for="selectableNumber in selectableNumberList"
          :key="selectableNumber"
          :value="selectableNumber"
          v-slot="{ selectedClass, toggle }"
        >
          <v-chip
            tag="button"
            class="mt-6 mr-4"
            :class="selectedClass"
            @click="toggle"
          >
            {{ selectableNumber }} 人
          </v-chip>
        </v-item>
      </v-item-group>
    </v-col>
  </v-row>
  <v-row>
    <v-col
      v-for="image in imagesOfSelectedNumber"
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
