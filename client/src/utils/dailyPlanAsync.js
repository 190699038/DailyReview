import axios from 'axios'

/**
 * 1. 从localStorage 登录的token  , key 为token  
 * 2. 如果token 不存在 
 * 
 * 
 */
//登录地址
const loginURL = "https://oa.aizyun.com/admin/login"
const dailyURL = "https://oa.aizyun.com/admin/dailyplan/list"
const groupURL = "https://oa.aizyun.com/admin/sys/user/deptlist/" //如:https://oa.aizyun.com/admin/sys/user/deptlist/12  12-财务组ID

const headers = {
    'Content-Type': 'application/json;charset=UTF-8',
    'authorization': ''
}

const dailyData = {
    'startDate': '',
    'endDate': '',
    'userid': 35
}

const loginData = {
    'username': 'yejijian',
    "password": "qyy369852"
}


/**
 *  登录 获取token
 * 1. 从localStorage 登录的token  , key 为token  
 * 2. 如果token 不存在 , loginURL +  loginData 通过post 方式获取token 
 * 3. 
 * @param {*} url
 * @param {*} data
 */
export const  loginOA = async () => {
  try {
    let token = localStorage.getItem('token');
    
    if (!token) {
      const response = await fetch(loginURL, {

        method: 'POST',
        headers: {
          'Content-Type': 'application/json;charset=UTF-8'
        },
        body: JSON.stringify(loginData)
      });

      if (!response.ok) throw new Error('登录失败');
      
      const data = await response.json();
      console.log(data);
      if (data.code !== 200) throw new Error('登录失败');
      token = data.data.token;
      localStorage.setItem('token', token);
    }

    headers.authorization = token;
    return token;
  } catch (error) {
    console.error('登录流程出错:', error);
    throw new Error('无法完成登录：' + error.message);
  }
}

const formatDate = (date) => {
  const year = date.getFullYear();
  const month = (date.getMonth() + 1).toString().padStart(2, '0');
  const day = date.getDate().toString().padStart(2, '0');
  const hours = date.getHours().toString().padStart(2, '0');
  const minutes = date.getMinutes().toString().padStart(2, '0');
  const seconds = date.getSeconds().toString().padStart(2, '0');
  return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
}

export const  getDailyPlanWithExecutorId = async (isToday,executor_id) => {
  
  let oa_userid = 0;
  const cache = localStorage.getItem('departments_user_cache');
  let users = cache? JSON.parse(cache) : [];
  if (users.length === 0) {
     return [];
  }

  for (let i = 0; i < users.length; i++) {
    if (users[i].id === executor_id) {
      oa_userid = users[i].oa_userid;
    }
  }

  let value =  await getDailyPlan(isToday,oa_userid);
  return value;
}

export const  getDailyPlan = async (isToday,uid) => {
    // let token = await loginOA();
    // const todayStart = new Date();
    // const todayEnd = new Date();
    // todayStart.setHours(0, 0, 0, 0);
    // todayEnd.setHours(23, 59, 59, 0);

    // let startTime = '';
    // let endTime = '';
    // if(isToday){
    //   // 获取今天的时间范围
     
    //   startTime = formatDate(todayStart);
    //   endTime = formatDate(todayEnd);
    // }else{
    //   // 获取昨天的时间范围
    //   const yesterdayStart = new Date(todayStart);
    //   yesterdayStart.setDate(yesterdayStart.getDate() - 1);

    //   const yesterdayEnd = new Date(todayEnd);
    //   yesterdayEnd.setDate(yesterdayEnd.getDate() - 1);
    //   startTime = formatDate(yesterdayStart);
    //   endTime = formatDate(yesterdayEnd);
    // }

    // let resData = getSingleUserDailyPlan(uid,token,startTime,endTime);
    // return resData;
    return [];
}

// 获取单人的日计划
const  getSingleUserDailyPlan =  async (uid, token,startTime,endTime) =>{
    const dailyData = {
        startDate: startTime,
        endDate: endTime,
        userid: uid
    };
    try {
        const response = await axios.post(dailyURL,
            dailyData,
            { headers: { ...headers, Authorization: token } }
        );
        return response.data.data.sort((a, b) => a.sort - b.sort);
    } catch (error) {
        console.error(`获取用户${uid}日报失败:`, error.response?.data);
        return null;
    }
}


/**
 * 获取当前组别用户对应OA数据的用户ID
 * 如果没有，则刷新一次
 */
export const megerOAUserIDS = async ( department_id ) => {
    // department_id = 0;
    // const cache = localStorage.getItem('departments_user_cache');
    // let users = cache ? JSON.parse(cache) : [];
    // if (users.length === 0) {
    //    return ;
    // }

    // let singleOAID =  users[0].oa_userid; 
    // if(singleOAID) {
    //    return ;
    // }

    // loginOA();

    // const response = await fetch(groupURL + department_id, {
    //     method: 'GET',
    //     headers: headers 
    // })

    // const data = await response.json();

    // if (data.code === 200) {
    //     let oaGroupUsers = data.data;
    //    for (let i = 0; i < oaGroupUsers.length; i++) {
    //       for (let j = 0; j < users.length; j++) {
    //          if (oaGroupUsers[i].label === users[j].partner_name) {
    //             users[j].oa_userid = oaGroupUsers[i].value;
    //          }
    //       }
    //    }

    //    localStorage.setItem('departments_user_cache', JSON.stringify(users));

    // }else{
    //     localStorage.removeItem('token');
    //     loginOA();
    // }
}


