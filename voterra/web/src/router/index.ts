import { createRouter, createWebHistory } from 'vue-router'
import VoterView from '../views/voter/VoterView.vue'
import AdminConfigView from '../views/admin/AdminConfigView.vue'
import AdminResultsView from '../views/admin/AdminResultsView.vue'

const routes = [
  { path: '/', component: VoterView },
  { path: '/admin-config', component: AdminConfigView },
  { path: '/admin-results', component: AdminResultsView }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router
