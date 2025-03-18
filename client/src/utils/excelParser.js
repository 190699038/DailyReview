import { id } from 'element-plus/es/locales.mjs';
import { read, utils } from 'xlsx';

export const parseExcelFile = (file) => {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    
    reader.onload = (e) => {
      const data = new Uint8Array(e.target.result);
      const workbook = read(data, { type: 'array' });
      const worksheet = workbook.Sheets[workbook.SheetNames[0]];
      const jsonData = utils.sheet_to_json(worksheet, { header: 1 });

    //   console.log(jsonData);
      // 跳过标题行和第二行
      const rows = jsonData.slice(1).map(row => ({
        id: row[0],
        priority: convertPriority(row[1]),
        content: row[2],
        executor: row[3]
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

  return priorityMap[value.trim()] || 5; // 默认返回B-
};