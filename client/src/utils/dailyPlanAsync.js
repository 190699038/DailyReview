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

// 获取单人的日计划
const  getDailyPlan =  async (uid, token,startTime,endTime) =>{
    // const now = new Date();
    // const yesterday = new Date(now.getTime() - 24 * 60 * 60 * 1000);
    
    // const startTime = new Date(yesterday);
    // startTime.setHours(0, 0, 0, 0);
    
    // const endTime = new Date(yesterday);
    // endTime.setHours(23, 59, 59, 999);

    // const dailyData = {
    //     ...dailyDataTemplate,
    //     startDate: formatDate(startTime, "YYYY-MM-DD HH:mm:ss"),
    //     endDate: formatDate(endTime, "YYYY-MM-DD HH:mm:ss"),
    //     userid: uid
    // };

    // try {
    //     const response = await axios.post(
    //         "https://oa.aizyun.com/admin/dailyplan/list",
    //         dailyData,
    //         { headers: { ...headers, Authorization: token } }
    //     );
    //     return response.data.data.sort((a, b) => a.sort - b.sort);
    // } catch (error) {
    //     console.error(`获取用户${uid}日报失败:`, error.response?.data);
    //     return null;
    // }
}


/**
 * 获取当前组别用户对应OA数据的用户ID
 * 如果没有，则刷新一次
 */
export const megerOAUserIDS = async ( department_id ) => {
    department_id = 0;
    const cache = localStorage.getItem('departments_user_cache');
    let users = cache ? JSON.parse(cache) : [];
    if (users.length === 0) {
       return ;
    }

    let singleOAID =  users[0].oa_userid; 
    if(singleOAID) {
       return ;
    }

    loginOA();

    const response = await fetch(groupURL + department_id, {
        method: 'GET',
        headers: headers 
    })

    const data = await response.json();

    if (data.code === 200) {
        let oaGroupUsers = data.data;
       for (let i = 0; i < oaGroupUsers.length; i++) {
          for (let j = 0; j < users.length; j++) {
             if (oaGroupUsers[i].label === users[j].partner_name) {
                users[j].oa_userid = oaGroupUsers[i].value;
             }
          }
       }

       localStorage.setItem('departments_user_cache', JSON.stringify(users));

    }else{
        localStorage.removeItem('token');
    }
}


