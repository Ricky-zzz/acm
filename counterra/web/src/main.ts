import { createApp } from 'vue'
import { createPinia } from 'pinia'
import axios from 'axios'
import App from './App.vue'
import router from './router'
import './style.css'
import { useToastStore } from './stores/toast'

const app = createApp(App)

const pinia = createPinia()
app.use(pinia)

axios.defaults.baseURL = 'http://localhost/acm/counterra/api'

axios.interceptors.response.use(
	response => response,
	error => {
		const toast = useToastStore(pinia)

		const status = error?.response?.status
		const messageFromServer = error?.response?.data?.message

		if (status === 422) {
			toast.push({ type: 'error', message: messageFromServer || 'Validation failed' })
		} else if (status === 401) {
			toast.push({ type: 'error', message: messageFromServer || 'Unauthorized' })
		} else if (status === 404) {
			toast.push({ type: 'error', message: messageFromServer || 'Not found' })
		} else if (status) {
			toast.push({ type: 'error', message: messageFromServer || `Request failed (${status})` })
		} else {
			toast.push({ type: 'error', message: 'Network error. Please try again.' })
		}

		return Promise.reject(error)
	}
)

app.use(router)
app.mount('#app')