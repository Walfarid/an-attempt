---
paths:
  - resources/js/components/ui/**
---

# UI Primitives

## reka-ui, not Radix
All primitive components come from `reka-ui` (the Radix Vue successor). Import from `reka-ui`, never from `@radix-ui/*` or any other source. The primitives are `DialogRoot`, `SelectRoot`, `AlertDialogRoot`, `Primitive`, etc. — note the `Root` suffix on container components.

## useForwardPropsEmits is mandatory for wrapper components
Every component that wraps a reka-ui primitive must use `useForwardPropsEmits(props, emits)` to forward props and events. This preserves reactivity and event handling. The pattern is:
```ts
const props = defineProps<SomeRootProps>()
const emits = defineEmits<SomeRootEmits>()
const forwarded = useForwardPropsEmits(props, emits)
```
Then `v-bind="forwarded"` on the primitive in the template.

## reactiveOmit strips custom props before forwarding
Components that add custom props (like `class`, `showCloseButton`, `side`) must use `reactiveOmit` from `@vueuse/core` to exclude them before calling `useForwardPropsEmits`. Example:
```ts
const delegatedProps = reactiveOmit(props, "class", "side")
const forwarded = useForwardPropsEmits(delegatedProps, emits)
```
Passing custom props to reka-ui primitives causes type errors and runtime warnings.

## inheritAttrs: false when spreading $attrs
Components that manually spread `$attrs` (like `DialogContent`, `SheetContent`) must set `defineOptions({ inheritAttrs: false })` to prevent Vue's automatic attribute inheritance. Then use `v-bind="{ ...$attrs, ...forwarded }"` to merge them explicitly.

## data-slot attribute on every root element
Every UI component's root element must have a `data-slot="component-name"` attribute (e.g., `data-slot="button"`, `data-slot="dialog-content"`). This enables CSS targeting and testing without relying on class names.

## cn() for class merging
All class composition uses `cn()` from `@/lib/utils` (which combines `clsx` and `tailwind-merge`). Components accept an optional `class` prop of type `HTMLAttributes["class"]` and merge it last: `cn(baseClasses, props.class)`. Never concatenate classes with string interpolation or template literals.

## class-variance-authority for variants
Components with multiple visual variants (currently only `Button`) use `cva` from `class-variance-authority` to define variant/size combinations. The pattern is:
```ts
export const buttonVariants = cva(baseClasses, {
  variants: { variant: {...}, size: {...} },
  defaultVariants: { variant: "default", size: "default" }
})
```
Then the component accepts `variant` and `size` props typed as `VariantProps<typeof buttonVariants>`.

## Icons from @lucide/vue
All icons come from `@lucide/vue` (e.g., `import { X } from "@lucide/vue"`). Do not import from `lucide-vue-next` or other variants.

## Directory structure: one component per directory
Each UI primitive lives in its own directory under `components/ui/` (e.g., `ui/button/`, `ui/dialog/`). Each directory contains:
- Individual `.vue` files for each sub-component (e.g., `Button.vue`, `DialogContent.vue`)
- An `index.ts` that re-exports all components with named exports: `export { default as Button } from "./Button.vue"`
- For variant-based components, `index.ts` also exports the `cva` definition and types

Do not add new files outside this structure or import components directly from `.vue` files — always import from the directory's `index.ts`.

## @vueuse/core for reactive utilities
`useVModel` is used for two-way binding in `Input.vue`; `reactiveOmit` strips props before forwarding. Do not reimplement these patterns with custom composables.
