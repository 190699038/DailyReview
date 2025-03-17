import { createRouter, createWebHistory } from 'vue-router'
import MainLayout from '@/components/MainLayout.vue'
import WeekGoal from '@/components/WeekGoal.vue'
import DailyGoal from '@/components/DailyGoal.vue'
import SystemSetting from '@/components/SystemSetting.vue'

export default createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      component: MainLayout,
      children: [
        { path: 'daily-goal', component: DailyGoal },
        { path: 'week-goal', component: WeekGoal },
        { path: 'system-setting', component: SystemSetting },
        { path: '', redirect: 'daily-goal' }
      ]
    }
  ]
})