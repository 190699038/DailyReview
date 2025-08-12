import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import installElementPlus from './plugins/element'
import ElementPlus from 'element-plus';

// 配置被动事件监听器以提高性能
if (typeof window !== 'undefined') {
  // 重写 addEventListener 以默认使用 passive: true
  const originalAddEventListener = EventTarget.prototype.addEventListener;
  EventTarget.prototype.addEventListener = function(type, listener, options) {
    if (typeof options === 'boolean') {
      options = { capture: options, passive: true };
    } else if (typeof options === 'object' && options !== null) {
      if (options.passive === undefined) {
        options.passive = true;
      }
    } else {
      options = { passive: true };
    }
    
    // 对于某些需要阻止默认行为的事件，不使用 passive
    const nonPassiveEvents = ['touchstart', 'touchmove', 'wheel', 'mousewheel'];
    if (nonPassiveEvents.includes(type) && listener && listener.toString().includes('preventDefault')) {
      options.passive = false;
    }
    
    return originalAddEventListener.call(this, type, listener, options);
  };
}

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