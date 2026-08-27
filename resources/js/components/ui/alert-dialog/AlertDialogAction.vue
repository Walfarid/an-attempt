<script setup lang="ts">
import type { AlertDialogActionProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { computed } from "vue"
import { AlertDialogAction } from "reka-ui"
import { cn } from "@/lib/utils"
import { buttonVariants } from "@/components/ui/button"

const props = defineProps<
  AlertDialogActionProps & {
    class?: HTMLAttributes["class"]
    variant?: NonNullable<Parameters<typeof buttonVariants>[0]>["variant"]
  }
>()

const variant = computed(
  () => props.variant ?? "default",
)
</script>

<template>
  <AlertDialogAction
    data-slot="alert-dialog-action"
    :class="cn(buttonVariants({ variant }), props.class)"
  >
    <slot />
  </AlertDialogAction>
</template>