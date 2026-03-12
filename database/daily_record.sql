create table daily_goals
(
    id               int auto_increment
        primary key,
    weekly_goals_id  int                         not null,
    department_id    int                         not null,
    executor         varchar(50)                 not null,
    executor_id      int                         not null,
    weekly_goal      text                        not null,
    priority         int            default 5    null comment '优先级',
    status           int                         not null,
    process          decimal(10, 2) default 0.00 null comment '进度',
    is_new_goal      tinyint(1)     default 0    null,
    cross_week       int            default 0    null comment '是否跨周任务 0-否 1- 是',
    createdate       int                         not null,
    pre_finish_date  int                         null comment '目标完成时间',
    real_finish_date int                         null,
    mondayDate       int                         not null comment '周一日期,通过这个判断出是否本周的周任务',
    remark           varchar(2000)  default ''   null comment '备注',
    country          varchar(20)    default ''   null comment '国家',
    version          varchar(20)    default ''   null comment '版本'
);

create index daily_goals_real_finish_date_index
    on daily_goals (real_finish_date, executor_id, department_id);

create table daily_tasks
(
    id             int auto_increment
        primary key,
    oa_taskid      int                         not null,
    executor_id    int                         not null,
    day_goal       varchar(1000)               not null,
    task_content   varchar(1000)               not null,
    time_spent     decimal(10, 2) default 0.00 null,
    progress       varchar(50)    default '0'  null,
    date           int                         not null,
    createdate     int                         not null,
    mondayDate     int                         not null,
    is_new_goal    tinyint(1)     default 0    null,
    daily_goals_id int            default 0    not null
);

create index index_oa_taskid_yesterday
    on daily_tasks (oa_taskid);

create table daily_tasks_today
(
    id             int auto_increment
        primary key,
    oa_taskid      int                         not null,
    executor_id    int                         not null,
    day_goal       varchar(1000)               not null,
    task_content   varchar(1000)               not null,
    time_spent     decimal(10, 2) default 0.00 null,
    progress       varchar(10)    default '0'  null,
    date           int                         not null,
    createdate     int                         not null,
    mondayDate     int                         not null,
    is_new_goal    tinyint(1)     default 0    null,
    daily_goals_id int            default 0    not null
);

create index idx_executor_date
    on daily_tasks_today (executor_id, date);

create index idx_monday_date
    on daily_tasks_today (mondayDate);

create index index_oa_today_taskid
    on daily_tasks_today (oa_taskid);

create table departments
(
    id              int auto_increment
        primary key,
    group_id        int          not null,
    department_name varchar(100) not null,
    sort            int          null comment '排序使用',
    constraint department_name
        unique (department_name)
);

create index group_id
    on departments (group_id);

create table test_tasks
(
    id            int auto_increment comment '自增ID'
        primary key,
    md5key        varchar(32) not null comment '任务唯一标识',
    creation_date varchar(20) not null comment '创建日期'
)
    comment '测试任务表' charset = utf8mb4;

create index idx_responsible_person
    on test_tasks (md5key);

create table test_tasks_info
(
    id                  int auto_increment comment '自增ID'
        primary key,
    task_id             int                         not null comment '任务ID',
    responsible_person  varchar(100)                not null comment '负责人',
    priority            varchar(20)                 not null comment '优先级',
    product             varchar(100)                not null comment '产品',
    test_content        text                        null comment '测试内容（需求链接）',
    test_status         varchar(50)                 not null comment '测试状态',
    test_progress       varchar(20)                 null comment '测试进度',
    pre_submission_time varchar(20)    default ''   null comment '预计提测时间',
    submission_time     varchar(20)                 null comment '提测时间',
    planned_online_time varchar(20)                 null comment '预计上线时间',
    actual_online_time  varchar(20)                 null comment '实际上线时间',
    planned_time_spent  decimal(10, 2) default 0.00 null comment '预计测试耗时',
    actual_time_spent   decimal(10, 2)              null comment '实际耗时（h）',
    actual_yl_time      decimal(10, 2) default 0.00 null comment '用例时间',
    creation_date       varchar(20)                 not null comment '创建日期',
    remarks             text                        null comment '备注'
)
    comment '测试详情表' charset = utf8mb4;

create index idx_creation_date
    on test_tasks_info (creation_date);

create index idx_responsible_person
    on test_tasks_info (responsible_person);

create index idx_taskid
    on test_tasks_info (task_id, submission_time);

create table today_target
(
    id            int auto_increment
        primary key,
    report_date   int  null,
    department_id int  not null,
    content       text null comment '内容摘要',
    message       text null,
    constraint report_date
        unique (report_date)
);

create index idx_message_prefix
    on today_target (message(255));

create index idx_report_date
    on today_target (report_date, id);

create table users
(
    id            int auto_increment
        primary key,
    partner_name  varchar(100)         not null,
    mode          varchar(50)          not null,
    department_id int                  not null,
    position      varchar(100)         not null,
    is_active     tinyint(1) default 1 null
);

create index department_id
    on users (department_id);

create table watch_user
(
    id              int unsigned auto_increment comment '自增主键ID'
        primary key,
    executor_id     int unsigned                 not null comment '被观察用户ID',
    executor_name   varchar(100)                 not null comment '被观察用户名称（长度优化）',
    status          tinyint unsigned default '1' not null comment '观察状态：1-观察中，0-已移除',
    department_id   int                          null comment '部门ID',
    department_name varchar(100)                 null comment '部门名称'
)
    comment '用户观察状态表';

create index idx_executor_id
    on watch_user (executor_id);

create index idx_status
    on watch_user (status);

create table weekly_goals
(
    id               int auto_increment
        primary key,
    department_id    int                         not null,
    executor         varchar(250)                not null,
    executor_id      text                        not null,
    weekly_goal      text                        not null,
    priority         int                         not null,
    status           int            default 0    not null,
    process          decimal(10, 2) default 0.00 null comment '进度',
    remark           text                        not null,
    cross_week       int            default 0    null comment '是否跨周任务 0-否 1- 是',
    is_new_goal      tinyint(1)     default 0    null,
    createdate       int                         not null,
    mondayDate       int                         not null comment '周一日期,通过这个判断出是否本周的周任务',
    pre_finish_date  int                         null comment '预计完成时间',
    real_finish_date int                         null,
    country          varchar(20)    default ''   null comment '国家',
    version          varchar(20)    default ''   null comment '版本'
);

create index weekly_goals_mondayDate_index
    on weekly_goals (mondayDate, real_finish_date, department_id);

create index weekly_goals_version_index
    on weekly_goals (version);

