<script>
import LandingLayout from '../layouts/LandingLayout.vue'

export default {
  layout: LandingLayout,
}
</script>

<script setup>
import { Head } from '@inertiajs/vue3'
import { defineAsyncComponent } from 'vue'
import AcademicEcosystem from '../components/landing/AcademicEcosystem.vue'
import CommunicationSection from '../components/landing/CommunicationSection.vue'
import DocumentVerification from '../components/landing/DocumentVerification.vue'
import FinalCTA from '../components/landing/FinalCTA.vue'
import HeroSection from '../components/landing/HeroSection.vue'
import LandingFooter from '../components/landing/LandingFooter.vue'
import LandingNavbar from '../components/landing/LandingNavbar.vue'
import ModuleExplorer from '../components/landing/ModuleExplorer.vue'
import PlatformIntro from '../components/landing/PlatformIntro.vue'
import ProblemSolution from '../components/landing/ProblemSolution.vue'
import ProductShowcase from '../components/landing/ProductShowcase.vue'
import ProjectStory from '../components/landing/ProjectStory.vue'
import SecuritySection from '../components/landing/SecuritySection.vue'
import StudentJourney from '../components/landing/StudentJourney.vue'
import TechnologyStack from '../components/landing/TechnologyStack.vue'

// Live, aggregate-only figures from LandingStatsService (routes/web.php `/`).
defineProps({
  stats: { type: Object, default: null },
})

// Chart.js is only needed by the analytics preview; keep it out of the first load.
const AnalyticsSection = defineAsyncComponent(() => import('../components/landing/AnalyticsSection.vue'))

// The same tags are rendered by app.blade.php (keyed with data-inertia) for
// crawlers that do not run JavaScript; head-key makes these replace them.
const title = 'EduCore — One Platform. Smarter Education.'
const description =
  'EduCore is a university digital administration platform connecting academic management, student services, administration, communication, and institutional workflows.'
const ogImage = `${window.location.origin}/assets/logo/educore-primary.png`
</script>

<template>
  <Head>
    <title>{{ title }}</title>
    <meta head-key="description" name="description" :content="description" />
    <meta head-key="og:type" property="og:type" content="website" />
    <meta head-key="og:site_name" property="og:site_name" content="EduCore" />
    <meta head-key="og:title" property="og:title" :content="title" />
    <meta head-key="og:description" property="og:description" :content="description" />
    <meta head-key="og:image" property="og:image" :content="ogImage" />
    <meta head-key="twitter:card" name="twitter:card" content="summary_large_image" />
    <meta head-key="twitter:title" name="twitter:title" :content="title" />
    <meta head-key="twitter:description" name="twitter:description" :content="description" />
    <meta head-key="twitter:image" name="twitter:image" :content="ogImage" />
  </Head>

  <a
    href="#main-content"
    class="sr-only z-[70] rounded-md bg-surface px-4 py-2 text-small font-semibold text-primary shadow-md focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus-visible:outline-2 focus-visible:outline-primary dark:bg-dark-surface dark:text-dark-primary"
  >
    Skip to content
  </a>

  <LandingNavbar />

  <!-- Story order: what it is → why → how the university is modelled → what it
       covers → what it looks like → key workflows → trust → who built it. -->
  <main id="main-content" tabindex="-1" class="focus:outline-none">
    <HeroSection />
    <PlatformIntro />
    <ProblemSolution />
    <AcademicEcosystem />
    <StudentJourney />
    <ModuleExplorer />
    <ProductShowcase :stats="stats" />
    <DocumentVerification />
    <CommunicationSection />
    <AnalyticsSection :stats="stats" />
    <SecuritySection />
    <TechnologyStack />
    <ProjectStory />
    <FinalCTA />
  </main>

  <LandingFooter />
</template>
