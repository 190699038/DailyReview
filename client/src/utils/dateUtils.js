import { ref, computed, defineProps, defineEmits } from 'vue'

export const  getWeekDates = () => {
    const today = new Date(); // 当前日期（2025-03-20）
    const currentDay = today.getDay() === 0 ? 7 : today.getDay(); // 转换为ISO星期码（周四=4）
    const monday = new Date(today);
    monday.setDate(today.getDate() - (currentDay - 1)); // 计算本周一（2025-03-17）
  
    const dates = [];
    for (let i = 0; i < 7; i++) {
      const date = new Date(monday);
      date.setDate(monday.getDate() + i); // 从周一开始累加天数
      dates.push(formatDate(date));
    }
    return dates;
  }


  export const  getTodayDate = () => {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0'); // 补零
    const day = String(today.getDate()).padStart(2, '0');         // 补零
    return `${year}${month}${day}`;
  }

  export const formatDate = (date) => {
    if (!(date instanceof Date)) {
      date = new Date(date);
      if (isNaN(date)) {
        throw new Error('Invalid date passed to formatDate');
      }
    }
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}${month}${day}`;
  }

  export const getMondayDate = (dateStr) => {
    const year = dateStr.substr(0, 4)
    const month = dateStr.substr(4, 2) - 1
    const day = dateStr.substr(6, 2)
    const date = new Date(year, month, day)
    const dayOfWeek = date.getDay()
    const adjustDays = dayOfWeek === 0 ? -6 : 1 - dayOfWeek
    date.setDate(date.getDate() + adjustDays)
    return [
      date.getFullYear(),
      (date.getMonth() + 1).toString().padStart(2, '0'),
      date.getDate().toString().padStart(2, '0')
    ].join('')
  }