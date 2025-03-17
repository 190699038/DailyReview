import ElementPlus from 'element-plus'
import { ElMessage } from 'element-plus'

export default (app) => {
  app.use(ElementPlus)
  app.config.globalProperties.$message = ElMessage
}
import 'element-plus/dist/index.css'