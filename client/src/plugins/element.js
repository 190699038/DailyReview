import ElementPlus from 'element-plus'
import { ElMessage } from 'element-plus'

export default (app) => {
  app.use(ElementPlus, {
    components: true,
    autoImportComponents: true,
    icons: {
      autoImport: true
    },
    sanitizer: {
      allowedAttributes: {
        span: ['style'],
        // 添加其他需要允许的标签属性
      }
    }
  })
  app.config.globalProperties.$message = ElMessage
}
import 'element-plus/dist/index.css'
