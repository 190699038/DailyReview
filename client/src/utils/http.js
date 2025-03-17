import axios from 'axios'

const service = axios.create({
  baseURL: 'http://127.0.0.1/DailyReview/server/',
  timeout: 5000
})

service.interceptors.response.use(
  response => {
    if (response.status === 401) {
      window.location.href = '/login'
    }
    return response.data
  },
  error => {
    if (error.code === 'ECONNABORTED') {
      ElMessage.error('请求超时，请检查网络连接')
    } else if (!error.response) {
      ElMessage.error('网络错误，请检查网络连接')
    } else {
      ElMessage.error(error.response.data.message || '请求失败')
    }
    return Promise.reject(error)
  }
)

export default service