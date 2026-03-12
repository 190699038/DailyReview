import axios from 'axios'
import { ElMessage } from 'element-plus'

const service = axios.create({
  baseURL: import.meta.env.DEV ? '/server/' : 'https://daily.gameyzy.com/server/',
  timeout: 5000
})

service.interceptors.response.use(
  response => {
    const data = response.data
    if (data && data.error) {
      ElMessage.error(data.error)
      return Promise.reject(new Error(data.error))
    }
    return data
  },
  error => {
    if (error.code === 'ECONNABORTED') {
      ElMessage.error('请求超时，请检查网络连接')
    } else if (!error.response) {
      ElMessage.error('网络错误，请检查网络连接')
    } else if (error.response.status === 401) {
      ElMessage.error('未授权，请重新登录')
      window.location.href = '/login'
    } else if (error.response.status === 500) {
      ElMessage.error('服务器错误，请稍后重试')
    } else {
      ElMessage.error(error.response.data?.message || error.response.data?.error || '请求失败')
    }
    return Promise.reject(error)
  }
)

export default service