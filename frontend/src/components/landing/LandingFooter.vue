<script setup>
// "EDUCORE" in the ANSI Shadow figlet font. Rows are equal length, so the
// monospace <pre> keeps every glyph aligned. Decorative: the text is in sr-only.
const banner = [
  "███████╗██████╗ ██╗   ██╗ ██████╗ ██████╗ ██████╗ ███████╗",
  "██╔════╝██╔══██╗██║   ██║██╔════╝██╔═══██╗██╔══██╗██╔════╝",
  "█████╗  ██║  ██║██║   ██║██║     ██║   ██║██████╔╝█████╗  ",
  "██╔══╝  ██║  ██║██║   ██║██║     ██║   ██║██╔══██╗██╔══╝  ",
  "███████╗██████╔╝╚██████╔╝╚██████╗╚██████╔╝██║  ██║███████╗",
  "╚══════╝╚═════╝  ╚═════╝  ╚═════╝ ╚═════╝ ╚═╝  ╚═╝╚══════╝",
].join('\n')
</script>

<template>
  <footer class="overflow-hidden border-t border-dark-border bg-primary-dark px-4 py-12 sm:py-16">
    <pre class="landing-banner mx-auto w-fit text-primary" aria-hidden="true">{{ banner }}</pre>
    <p class="sr-only">EduCore — One Platform. Smarter Education.</p>
  </footer>
</template>
