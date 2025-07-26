import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import installElementPlus from './plugins/element'
import ElementPlus from 'element-plus';

const app = createApp(App)
// installElementPlus(app)
app.use(router)
app.mount('#app')
console.log('Environment:', import.meta.env.MODE);

app.use(ElementPlus, { 
  loading: { 
    spinner: 'el-icon-loading', 
    background: 'rgba(0,0,0,0.5)' 
  } 
});

app.config.errorHandler = (err, vm, info) => {
    console.error('[Global Error]', err, info);
  };