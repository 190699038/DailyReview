import { id } from 'element-plus/es/locales.mjs';
import { read, utils } from 'xlsx';

export const parseExcelFile = (file,type) => {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    
    reader.onload = (e) => {
      const data = new Uint8Array(e.target.result);
      const workbook = read(data, { type: 'array' });
      const worksheet = workbook.Sheets[workbook.SheetNames[0]];
      const jsonData = utils.sheet_to_json(worksheet, { header: 1 });

    //   console.log(jsonData);
      // 跳过标题行和第二行
      const isDaily = type === 'daily';
      
      const rows = jsonData.slice(1)
  .filter(row => row.some(cell => cell))
  .map(row => ({ 
        id: row[0],
        ...(isDaily ? {
          executor: row[1],
          progress: row[3],
          time_spent: row[4],
          date: formatDate(row[5]),
          day_goal: row[6],
          task_content: row[7],
          executor_id: getExecutor_id(row[1])
        } : {
          priority: convertPriority(row[1]),
          content: row[2],
          executor: row[3]
        })
      }));

      resolve(rows);
    };

    reader.onerror = error => reject(error);
    reader.readAsArrayBuffer(file);
  });
};

const priorityMap = {
  'A+': 10,
  'A': 9,
  'A-': 8,
  'B+': 7,
  'B': 6,
  'B-': 5,
  'C+': 4,
  'C': 3,
  'C-': 2
};

const convertPriority = (value) => {
  if(typeof(value) == 'number' ){
    return value;
  }
  return priorityMap[value.trim()] || 5;
};

const statusMap = {
  '未开始': 0,
  '进行中': 1,
  '测试中': 2,
  '已完成': 3,
  '已暂停': 4
};

const convertStatus = (value) => {
  return statusMap[value.trim()] || 0;
};


const getExecutor_id = (value) => {
  const cache = localStorage.getItem('departments_user_cache')
  let users = cache ? JSON.parse(cache) : []

  for (let i = 0; i < users.length; i++) {
    if (users[i].partner_name === value) {
      return users[i].id
    }
  }
  return 0;
};

const formatDate = (date) => {
  // if (!date) return '';
  // const d = new Date(date);
  // return d.toISOString().slice(0,10).replace(/-/g, '');
  return date;
};