<?php
/**
 * 数据分析模型数据库操作类
 * @author zxb<2013.9.11>
 */
defined('IN_PHPCMS') or exit('No permission resources.');
pc_base::load_sys_class('model', '', 0);
class yp_record_model extends model {
    public function __construct() {
        $this->db_config = pc_base::load_config('database');
        $this->db_setting = 'default';
        $this->table_name = '';
        parent::__construct();
        $this->day_time = date('Ymd', SYS_TIME);
        $this->month_time = date('Ym', SYS_TIME);
    }

    public function setTableName($tableName) {
        $this->table_name = $this->db_tablepre . $tableName;
    }

    /**
     * 更新访问数或留言数+1
     * @param int $comuid
     * @param string $item 项目（访问数'views'|留言数'msgs'）
     */
    public function setInc($comuid, $item) {
        if (!in_array($item, array('views', 'msgs')))
            return FALSE;
        //更新日访问数
        $this->setTableName('yp_record_day');
        if ($this->count(array('comuid' => $comuid, 'recordtime' => $this->day_time))) {
            $this->update(array($item => '+=1', 'indexes' => '+=1'), array('comuid' => $comuid, 'recordtime' => $this->day_time));
        } else {
            $this->insert(array($item => 1, 'indexes' => 1, 'comuid' => $comuid, 'recordtime' => $this->day_time));
        }

        //更新月访问数
        $this->setTableName('yp_record_month');
        if ($this->count(array('comuid' => $comuid, 'recordtime' => $this->month_time))) {
            $this->update(array($item => '+=1', 'indexes' => '+=1'), array('comuid' => $comuid, 'recordtime' => $this->month_time));
        } else {
            $this->insert(array($item => 1, 'indexes' => 1, 'comuid' => $comuid, 'recordtime' => $this->month_time));
        }
    }

    /**
     * 更新综合指数+1
     * @param int $comuid
     */
    public function setIncIndex($comuid) {
        //更新日指数
        $this->setTableName('yp_record_day');
        if ($this->count(array('comuid' => $comuid, 'recordtime' => $this->daytime))) {
            $this->update(array('indexes' => '+=1'), array('comuid' => $comuid, 'recordtime' => $this->daytime));
        } else {
            $this->insert(array('indexes' => 1, 'comuid' => $comuid, 'recordtime' => $this->daytime));
        }

        //更新月指数
        $this->setTableName('yp_record_month');
        if ($this->count(array('comuid' => $comuid, 'recordtime' => $this->month_time))) {
            $this->update(array('indexes' => '+=1'), array('comuid' => $comuid, 'recordtime' => $this->month_time));
        } else {
            $this->insert(array('indexes' => 1, 'comuid' => $comuid, 'recordtime' => $this->month_time));
        }
    }

    /**
     * 获得排名
     * @param int $comuid 品牌id
     * @param int $recordtime 记录时间（格式：20130915或201309,未指定时间则获得总排名）
     * @return int
     */
    public function getRank($comuid, $recordtime = FALSE) {
        if ($recordtime) {
            strlen($recordtime) > 6 ? $this->setTableName('yp_record_day') : $this->setTableName('yp_record_month');
            extract($this->get_one(array('comuid' => $comuid, 'recordtime' => $recordtime), 'indexes'));
            return $indexes ? $this->count("`recordtime`=$recordtime AND `indexes`>$indexes") + 1 : 0;
        } else {
            //获取总排名
            $this->setTableName('yp_record_month');
            extract($this->get_one(array('comuid' => $comuid), 'SUM(`indexes`) AS `total_indexes`'));
            $total_index = $total_index ? : 0;
            $sql = "SELECT `comuid` FROM {$this->db_tablepre}yp_record_month GROUP BY `comuid` HAVING SUM(`indexes`)>$total_index";
            $this->query($sql);
            return $this->affected_rows() ? $this->affected_rows() + 1 : 0;
        }
    }

    /**
     * 获取总数据
     * @param int $comuid 品牌id
     * @param string $item 项目（综合指数'indexes'|总访问数'views'|总留言数'msgs'）
     * @return int
     */
    public function getTotal($comuid, $item) {
        $this->setTableName('yp_record_month');
        extract($this->get_one(array('comuid' => $comuid), "SUM(`$item`) AS total"));
        return $total ? $total : 0;
    }

    /**
     * 获取某月或某日的数据
     * @param int $comuid 品牌id
     * @param string $item 项目（指数'indexes'|访问数'views'|留言数'msgs'）
     * @param int $recordtime 记录时间（格式：20130915或201309）
     */
    public function getOne($comuid, $item, $recordtime) {
        strlen($recordtime) > 6 ? $this->setTableName('yp_record_day') : $this->setTableName('yp_record_month');
        extract($this->get_one(array('comuid' => $comuid, 'recordtime' => $recordtime), $item));
        return ${$item} ? ${$item} : 0;
    }

    /**
     * 获取日统计记录数据(30天)
     * @param int $comuid 品牌id
     * @param string $item 项目（指数'indexes'|访问数'views'|留言数'msgs'）
     * @param int $starttime 起始时间（时间戳格式）
     * @return array
     */
    public function getDays($comuid, $item, $starttime = FALSE) {
        $starttime = $starttime ? : strtotime('-29 days', SYS_TIME);
        $endtime = strtotime('+29 days', $starttime);
        $startday = date('Ymd', $starttime);
        $endday = date('Ymd', $endtime);

        $this->setTableName('yp_record_day');
        $data_tmp = $this->select("`comuid`=$comuid AND `recordtime` BETWEEN $startday AND $endday", "`recordtime`,`$item`", 30, "`recordtime`");

        foreach ($data_tmp as $v) {
            $k = date('j', strtotime($v['recordtime']));
            $data_tmp_new[$k] = $v[$item];
        }

        for ($i = 0; $i < 30; $i++) {
            $k = date('j', strtotime("+$i days", $starttime));
            $data[$k] = $data_tmp_new[$k] ? : 0;
        }
        return $data;
    }

    /**
     * 获取月统计记录数据（12个月）
     * @param int $comuid 品牌id
     * @param string $item 项目（指数'indexes'|访问数'views'|留言数'msgs'）
     * @param int $starttime 起始时间（时间戳格式）
     * @return array
     */
    public function getMonths($comuid, $item, $starttime = FALSE) {
        $starttime = $starttime ? : strtotime('-11 month', SYS_TIME);
        $endtime = strtotime('+11 month', $starttime);
        $startmonth = date('Ym', $starttime);
        $endmonth = date('Ym', $endtime);

        $this->setTableName('yp_record_month');
        $data_tmp = $this->select("`comuid`=$comuid AND `recordtime` BETWEEN $startmonth AND $endmonth", "`recordtime`,`$item`", 12, "`recordtime`");

        foreach ($data_tmp as $v) {
            $k = date('n', strtotime($v['recordtime'] . '01'));
            $data_tmp_new[$k] = $v[$item];
        }

        for ($i = 0; $i < 12; $i++) {
            $k = date('n', strtotime("+$i month", $starttime));
            $data[$k] = $data_tmp_new[$k] ? : 0;
        }
        return $data;
    }

    /**
     * 获取排名日统计记录（30天）
     * @param int $comuid 品牌id
     * @param int $starttime 起始时间（时间戳格式）
     * @return array
     */
    public function getDaysRank($comuid, $starttime = FALSE) {
        $starttime = $starttime ? : strtotime('-29 days', SYS_TIME);
        for ($i = 0; $i < 30; $i++) {
            $time = strtotime("+$i days", $starttime);
            $data[date('j', $time)] = $this->getRank($comuid, date('Ymd', $time));
        }
        return $data;
    }

    /**
     * 获取排名月统计记录（12个月）
     * @param int $comuid
     * @param int $starttime 起始时间（时间戳格式）
     * @return array
     */
    public function getMonthsRank($comuid, $starttime = FALSE) {
        $starttime = $starttime ? : strtotime('-11 month', SYS_TIME);
        for ($i = 0; $i < 12; $i++) {
            $time = strtotime("+$i month", $starttime);
            $data[date('n', $time)] = $this->getRank($comuid, date('Ym', $time));
        }
        return $data;
    }

    /**
     * 获取30天、12个月的数据
     * @param int $comuid 品牌id
     * @param int $starttime 起始时间（时间戳格式）
     * @return array 图标数据（指数、访问、留言、排名的值、刻度、高度）
     */
    public function getData($comuid, $starttime = FALSE, $per = 100) {
        $item_arr = array('indexes' => '指数', 'views' => '访问', 'msgs' => '留言');
        foreach ($item_arr as $item => $item_name) {
            $datas[$item]['item_name'] = $item_name;

            $month_arr = $this->getMonths($comuid, $item, $starttime);
            $max_month = max($month_arr) > 100 ? max($month_arr) : 100;
            $datas[$item]['month']['mark'] = array($max_month, round($max_month / 4 * 3), round($max_month / 4 * 2), round($max_month / 4 * 1));//左边刻度
            foreach ($month_arr as $k => $v) {
                $datas[$item]['month']['data'][$k]['value'] = $v;//值
                $datas[$item]['month']['data'][$k]['height'] = $v * $per / $max_month;//高度
            }

            $day_arr = $this->getDays($comuid, $item, $starttime);
            $max_day = max($day_arr) > 100 ? max($day_arr) : 100;
            $datas[$item]['day']['mark'] = array($max_day, round($max_day / 4 * 3), round($max_day / 4 * 2), round($max_day / 4 * 1));//左边刻度
            foreach ($day_arr as $k => $v) {
                $datas[$item]['day']['data'][$k]['value'] = $v;//值
                $datas[$item]['day']['data'][$k]['height'] = $v * $per / $max_day;//高度
            }
        }

        //加入排名数据
        $datas['rank']['item_name'] = '排名';
        //月排名数据======================
        $month_arr = $this->getMonthsRank($comuid, $starttime);
        $max_month = max($month_arr) > 100 ? max($month_arr) : 100;//最大值为最小排名
        //最小值为最高排名,需去除0
        $month_arr_cp = $month_arr;
        $month_arr_cp = array_flip($month_arr_cp);
        unset($month_arr_cp[0]);
        $month_arr_cp = array_flip($month_arr_cp);
        $min_month = 1;

        $datas['rank']['month']['mark'] = array($min_month, $min_month + round(($max_month - $min_month) / 3), $min_month + round(($max_month - $min_month) / 3 * 2), $max_month);//左边刻度
        foreach ($month_arr as $k => $v) {
            $datas['rank']['month']['data'][$k]['value'] = $v;//值
            $datas['rank']['month']['data'][$k]['height'] = $v ? ($max_month - $v) / ($max_month - $min_month) * $per : 0;//高度
        }

        //日排名数据======================
        $day_arr = $this->getDaysRank($comuid, $starttime);
        $max_day = max($day_arr) > 100 ? max($day_arr) : 100;
        $day_arr_cp = array_flip($day_arr_cp);
        unset($day_arr_cp[0]);
        $day_arr_cp = array_flip($day_arr_cp);
        $min_day = 1;

        $datas['rank']['day']['mark'] = array($min_day, round($max_day / 3), round($max_day / 3 * 2), $max_day);//左边刻度
        foreach ($day_arr as $k => $v) {
            $datas['rank']['day']['data'][$k]['value'] = $v;//值
            $datas['rank']['day']['data'][$k]['height'] = $v ? ($max_day - $v) / ($max_day - $min_day) * $per : 0;//高度
        }
        return $datas;
    }

}