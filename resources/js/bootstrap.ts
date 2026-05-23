import axios from 'axios'

declare global {
  interface Window {
    axios: typeof axios
    Pose: unknown
    Camera: unknown
  }
}

window.axios = axios
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
