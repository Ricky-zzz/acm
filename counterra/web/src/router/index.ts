import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import DashboardView from '../views/dashboard/DashboardView.vue'
import LoginView from '../views/auth/LoginView.vue'
import CityListView from '../views/cities/CityListView.vue'
import PositionListView from '../views/positions/PositionListView.vue'
import PartyListView from '../views/parties/PartyListView.vue'
import CandidateListView from '../views/candidates/CandidateListView.vue'

const routes = [
  { path: '/login', component: LoginView },
  {
    path: '/',
    component: DashboardLayout, 
    redirect: '/dashboard',
    meta: { requiresAuth: true },
    children: [
      { path: 'dashboard', component: DashboardView },
      { path: 'cities', component: CityListView },
      { path: 'positions', component: PositionListView },
      { path: 'parties', component: PartyListView },
      { path: 'candidates', component: CandidateListView }
    ]
  }
]
const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((to, _from, next) => {
  const auth = useAuthStore()
  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    next('/login')
  } else if (to.path === '/login' && auth.isAuthenticated) {
    next('/dashboard') 
  } else {
    next()
  }
})

export default router