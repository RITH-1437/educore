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
import ProblemSolution from '../components/landing/ProblemSolution.vue'
import ProductShowcase from '../components/landing/ProductShowcase.vue'
import ProjectStory from '../components/landing/ProjectStory.vue'
import SecuritySection from '../components/landing/SecuritySection.vue'
import StudentJourney from '../components/landing/StudentJourney.vue'
import TechnologyStack from '../components/landing/TechnologyStack.vue'
import WhatIsEduCore from '../components/landing/WhatIsEduCore.vue'

// Live, aggregate-only figures from LandingStatsService (routes/web.php `/`).
defineProps({
  stats: { type: Object, default: null },
})

// Chart.js is only needed by the analytics preview; keep it out of the first load.
const AnalyticsSection = defineAsyncComponent(() => import('../components/landing/AnalyticsSection.vue'))

const ogImage = '/assets/logo/educore-primary.png'
const description =
  'EduCore is a university digital administration platform connecting academic management, student services, administration, communication and documents in one secure system.'
</script>

<template>
  <Head>
    <title>EduCore — One Platform. Smarter Education.</title>
    <meta name="description" :content="description" />
    <meta property="og:type" content="website" />
    <meta property="og:site_name" content="EduCore" />
    <meta property="og:title" content="EduCore — One Platform. Smarter Education." />
    <meta property="og:description" :content="description" />
    <meta property="og:image" :content="ogImage" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="EduCore — One Platform. Smarter Education." />
    <meta name="twitter:description" :content="description" />
    <meta name="twitter:image" :content="ogImage" />
  </Head>

  <a
    href="#main-content"
    class="sr-only z-[70] rounded-md bg-surface px-4 py-2 text-small font-semibold text-primary shadow-md focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus-visible:outline-2 focus-visible:outline-primary dark:bg-dark-surface dark:text-dark-primary"
  >
    Skip to content
  </a>

  <LandingNavbar />

  <main id="main-content" tabindex="-1" class="focus:outline-none">
    <HeroSection />
    <WhatIsEduCore />
    <ProblemSolution />
    <ModuleExplorer />
    <AcademicEcosystem />
    <StudentJourney />
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
