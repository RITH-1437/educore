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
import AdministrationSection from '../components/landing/AdministrationSection.vue'
import AboutSection from '../components/landing/AboutSection.vue'
import CommunicationSection from '../components/landing/CommunicationSection.vue'
import DocumentVerification from '../components/landing/DocumentVerification.vue'
import FinalCTA from '../components/landing/FinalCTA.vue'
import HeroSection from '../components/landing/HeroSection.vue'
import LandingFooter from '../components/landing/LandingFooter.vue'
import LandingNavbar from '../components/landing/LandingNavbar.vue'
import ModuleExplorer from '../components/landing/ModuleExplorer.vue'
import PlatformOverview from '../components/landing/PlatformOverview.vue'
import ProjectStory from '../components/landing/ProjectStory.vue'
import SecuritySection from '../components/landing/SecuritySection.vue'
import StudentJourney from '../components/landing/StudentJourney.vue'
import TechnologyStack from '../components/landing/TechnologyStack.vue'
import TrustSection from '../components/landing/TrustSection.vue'
import WhyEduCore from '../components/landing/WhyEduCore.vue'

const AnalyticsSection = defineAsyncComponent(
  () => import('../components/landing/AnalyticsSection.vue')
)

const ogImage = '/assets/logo/educore-primary.png'
</script>

<template>
  <Head>
    <title>EduCore — One Platform. Smarter Education.</title>
    <meta
      name="description"
      content="EduCore is a modern university digital administration platform connecting academic management, student services, administration, communication, documents, and institutional workflows."
    />
    <meta property="og:type" content="website" />
    <meta property="og:site_name" content="EduCore" />
    <meta property="og:title" content="EduCore — One Platform. Smarter Education." />
    <meta
      property="og:description"
      content="EduCore is a modern university digital administration platform connecting academic management, student services, administration, communication, documents, and institutional workflows."
    />
    <meta property="og:image" :content="ogImage" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="EduCore — One Platform. Smarter Education." />
    <meta
      name="twitter:description"
      content="EduCore is a modern university digital administration platform connecting academic management, student services, administration, communication, documents, and institutional workflows."
    />
    <meta name="twitter:image" :content="ogImage" />
  </Head>

  <a
    href="#main-content"
    class="sr-only z-[70] rounded-md bg-white px-4 py-2 text-sm font-semibold text-blue-700 shadow-md focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:ring-2 focus:ring-blue-600"
  >
    Skip to content
  </a>

  <LandingNavbar />

  <main id="main-content" tabindex="-1" class="focus:outline-none">
    <HeroSection />
    <TrustSection />
    <AboutSection />
    <PlatformOverview />
    <ModuleExplorer />
    <AcademicEcosystem />
    <StudentJourney />
    <AdministrationSection />
    <DocumentVerification />
    <CommunicationSection />
    <AnalyticsSection />
    <SecuritySection />
    <TechnologyStack />
    <WhyEduCore />
    <ProjectStory />
    <FinalCTA />
  </main>

  <LandingFooter />
</template>