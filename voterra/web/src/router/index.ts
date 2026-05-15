import { createRouter, createWebHistory } from 'vue-router'
import VoterView from '../views/voter/VoterView.vue'
import AdminConfigView from '../views/admin/AdminConfigView.vue'
import AdminResultsView from '../views/admin/AdminResultsView.vue'
import AdminLoginView from '../views/admin/AdminLoginView.vue'
import { useAuthStore } from '../stores/auth'

const routes = [
  { path: '/', component: VoterView },
  { path: '/admin-login', component: AdminLoginView },
  { path: '/admin-config', component: AdminConfigView, meta: { requiresAuth: true } },
  { path: '/admin-results', component: AdminResultsView, meta: { requiresAuth: true } }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((to, _from, next) => {
  const auth = useAuthStore()
  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    next('/admin-login')
  } else if (to.path === '/admin-login' && auth.isAuthenticated) {
    next('/admin-config')
  } else {
    next()
  }
})

export default router
