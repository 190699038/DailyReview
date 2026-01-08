import { createRouter, createWebHistory,createWebHashHistory  } from 'vue-router'
import MainLayout from '@/components/MainLayout.vue'
import UserTodayPlan from '@/components/UserTodayPlan.vue'
import WeekGoal from '@/components/WeekGoal.vue'
import DailyGoal from '@/components/DailyGoal.vue'
import SystemSetting from '@/components/SystemSetting.vue'
import DailyHistory from '@/components/DailyHistory.vue'
import TestTask from '@/components/TestTask.vue'

export default createRouter({
  history: createWebHashHistory(),
  routes: [
    {
      path: '/',
      component: MainLayout,
      children: [
    {
      path: '/user-today-plan',
      component: () => import('@/components/UserTodayPlan.vue')
    },
        { path: 'daily-goal', component: DailyGoal },
        { path: 'week-goal', component: WeekGoal },
        { path: 'system-setting', component: SystemSetting },
        { path: 'history-daily', component: DailyHistory },
        { path: 'test-task', component: TestTask },
        { path: '', redirect: 'week-goal' }
      ]
    }
  ]
})