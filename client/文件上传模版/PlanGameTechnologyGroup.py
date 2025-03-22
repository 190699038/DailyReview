import json
import requests
from datetime import datetime, timedelta

# TOKEN = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1aWQiOjM2LCJwdiI6MSwiaWF0IjoxNzM2MjMyMzYwfQ.T3f7NT6l4OvlRG4fSXwZTt64o87kUvDRvfKVAhWgJZI"
TOKEN = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1aWQiOjM2LCJwdiI6MSwiaWF0IjoxNzM2MjMyMzYwfQ.T3f7NT6l4OvlRG4fSXwZTt64o87kUvDRvfKVAhWgJZk"

COUNT = 2

headers = {
    'Content-Type': 'application/json;charset=UTF-8',
    'authorization': ''
}

# {"startDate":"2025-01-07 00:00:00","endDate":"2025-01-07 23:59:59","project":10,"userid":18}
#
dailyURL = "https://oa.aizyun.com/admin/dailyplan/list"
# {"startDate":"2025-01-07 00:00:00","endDate":"2025-01-07 23:59:59","project":36,"userid":35}

dailyData = {
    'startDate': '',
    'endDate': '',
    'userid': 35
}

# 王子金、孔祥东、王子腾、周杭民、曹方毅、曹远浩、徐璠
userIDS = [ 34, 61, 96, 97,165,185, 200,202,209]
userNicks = ["曹方毅", "李欣","陈苏熙","梁嘉轩","曹远浩","谢国良","陶俊华","王子腾","帅维城"]

#
loginURL = "https://oa.aizyun.com/admin/login"

loginData = {
    'username': 'yejijian',
    "password": "qyy369852"
}

project_ids = {
    12: '财务组', 7: '选人组', 8: '用人组', 9: '总办组', 10: '技术组', 24: '游戏技术组', 14: "总参谋", 17: '大富组',
    '27': '穷奇客服组', 25: '投放组', 28: '订餐组', 34: '产品组', 36: '奇胜组1号', 37: '奇胜组2号', 26: '行业研究',
    39: '鲲鹏组', 41: '观星组', 33: '投放OA', 11: '麒麟组'

}


def login():
    response = requests.post(loginURL, headers=headers, json=loginData)

    html = response.content
    html = html.decode('utf-8')  # python3
    userinfo = json.loads(html)

    token = userinfo['data']['token']
    return token


def getDailyPlan(uid):
    global dailyData
    now = datetime.now()
    yesterday = now - timedelta(days=1)

    # 设置时间范围为昨天的00:00:00到23:59:59
    start_time = datetime.combine(yesterday.date(), datetime.min.time())  # 00:00:00
    end_time = datetime.combine(yesterday.date(), datetime.max.time())    # 23:59:59

    # 格式化为字符串
    start_date_str = start_time.strftime("%Y-%m-%d %H:%M:%S")
    end_date_str = end_time.strftime("%Y-%m-%d %H:%M:%S")
    # current_date = datetime.now()

    # start_date_str = current_date.strftime("%Y-%m-%d 00:00:00")
    # end_date_str = current_date.strftime("%Y-%m-%d 23:59:59")
    # print(start_date_str)
    # print(end_date_str)

    dailyData['startDate'] = start_date_str
    dailyData['endDate'] = end_date_str
    dailyData['userid'] = uid

    response = requests.post(dailyURL, headers=headers, json=dailyData)
    html = response.content
    html = html.decode('utf-8')  # python3
    planinfo = json.loads(html)

    return planinfo


def write_file(s, filename):
    # f = open(filename, 'w')
    # f.write(s)
    with open(filename, 'w', encoding='utf', errors='ignore') as f:
        f.write(s)


def read_file(filepath):
    content = ""
    with open(filepath, 'r') as f:
        content = f.read()
    return content


def downloadInfo(token):
    global COUNT
    headers['authorization'] = token
    print(headers)
    now = datetime.now()
    yesterday = now - timedelta(days=1)
    start_time = datetime.combine(yesterday.date(), datetime.min.time())  # 00:00:00
    start_date_str = start_time.strftime("%Y%m%d")

    idx = 0
    csvInfo = ''#'执行人@周目标@日目标@进度@日期'+ '\n'
 
    for id in userIDS:
        user = userNicks[idx]
        print(user)
        planinfo = getDailyPlan(id)
        if planinfo['code'] == 11001 or planinfo['code'] == 11002:
            return False
        planList = planinfo['data']
        sorted_data = sorted(planList, key=lambda x: x['sort'])
        current_date = datetime.now()

        # start_date_str = current_date.strftime("%Y/%m/%d")

        for plan in sorted_data:
            d_describe = plan['d_describe'].replace("\n", "")
            d_describe = d_describe.replace("@", "")

            p_describe = plan['p_describe'].replace("\n", "")
            p_describe = p_describe.replace("@", "")

            p_standard = plan['p_standard'].replace("\n", "")
            p_standard = p_standard.replace("@", "")

            print(plan)
            sort = 0
            try:
                sort = plan['sort']
                if sort == None:
                    sort = 0
            except Exception as e:
                raise
    

            print(str(sort + 1) + '@' + '岗位目标@')
            print(project_ids[plan['project']] + '@')
            print(plan['currentValue'] + '@')
            print(plan['targetValue'] + '@')
            print( p_standard + "@" + plan['p_time'] + "@" + plan['priority'] + "@" + str(
                plan['e_time']) + "@" + "@" + '0%@' + '@' + '@')
            

            compelete = plan['complete'] == None and '0' or str(plan['complete'])

            info = p_describe
            if d_describe.find(p_describe) == -1 and p_describe.find(d_describe) == -1:
                info = d_describe + ' - ' + p_describe


            # csvInfo = '执行人@周目标@日目标@日期@进度@日期'
            csvInfo = csvInfo  + str(sort + 1) + '@' + \
                     user +'@' + \
                     info + '@' + \
                     compelete + '%@' + \
                     str(plan['r_time']) + '@' + \
                      start_date_str + '@' + \
                     d_describe + '@' + \
                     p_describe + '@' + \
                    '\n' 



        idx = idx + 1

    print(csvInfo)
    filename = current_date.strftime("%Y%m%d")

    
    write_file(csvInfo, 'E:/Develop/dailyplan/gameTechGroup/' + filename + '.txt')
    return True


if __name__ == "__main__":
    token = read_file("E:/Develop/dailyplan/token.txt")
    if downloadInfo(token) == False:
        token = login()
        TOKEN = token
        write_file(TOKEN, "E:/Develop/dailyplan/token.txt")
        downloadInfo(token)



