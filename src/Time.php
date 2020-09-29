<?php

/**
 * Author: 胡镇华
 * Website: www.cocong.cn
 * Project address: https://github.com/hzh-cocong/Time-Helper
 */

class Time {
    public function __construct() {
        //ini_set('date.timezone','Asia/Shanghai');
    }

    public function deal($query) {
        $query = trim($query);

        // default(current time)
        if($query === '') {
            $items = $this->dealDefault();
            echo json_encode($items);
            exit;
        }

        // pure number
        if(preg_match('/^\d+$/', $query)) {
            if(strlen($query) == 8 && strtotime($query) !== false) {
                // date like 20120202
                $items = $this->dealDate($query);
                echo json_encode($items);
                exit;
            } else {
                // timestamp
                $items = $this->dealTimestamp($query);
                echo json_encode($items);
                exit;
            }
        }

        // 2020-09-29 19:21:22 or 2020-09-29
        if(preg_match('/^\d{4}-\d{2}-\d{2}(\s+\d{2}:\d{2}:\d{2})*$/', $query)
            && strtotime($query) !== false) {
                $items = $this->dealTime($query);
                echo json_encode($items);
                exit;
        }

        // +1d or -1d
        if(preg_match('/^([+-])(\d+)([sihdwmy]?)$/', $query, $matches)) {
            $items = $this->dealDays($matches[1], $matches[2], $matches[3]);
            echo json_encode($items);
            exit;
        }

        // now today date log
        if(in_array($query, array('now', 'today', 'date', 'log'))) {
            $items = $this->dealFormat($query);
            echo json_encode($items);
            exit;
        }

        // 2020-09-29 15:56:27 - 2020-09-29 15:56:20
        if(preg_match('/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\s*)-(\s*\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})|(\d{4}-\d{2}-\d{2}\s*)-(\s*\d{4}-\d{2}-\d{2})|(\d{4}\d{2}\d{2}\s*)-(\s*\d{4}\d{2}\d{2})$/', $query, $matches)) {
            $left = empty($matches[1]) ? (empty($matches[3]) ? $matches[5]: $matches[3]) : $matches[1];
            $right =empty($matches[2]) ? (empty($matches[4]) ? $matches[6]: $matches[4]) : $matches[2];
            $items = $this->dealSubtraction(strtotime($left), strtotime($right));
            echo json_encode($items);
            exit;
        }

        // 1d
        if(preg_match('/^(\d+\.{0,1}\d*)([sihdwmy])$/', $query, $matches)) {
            $items = $this->dealConversion($matches[1], $matches[2]);
            echo json_encode($items);
            exit;
        }

        // no result
        $items = $this->dealTip($query);
        echo json_encode($items);
        exit;
    }

    protected function dealDefault() {
        $timestamp = time();

        $list = $this->getTime($timestamp);
        $list = array_merge($list, $this->getHumanReadable($timestamp));

        $items = array(
            'rerun' => 1, // auto refresh(once per second)
            'items' => $list
        );

        return $items;
    }

    protected function dealTimestamp($timestamp) {
        $list = $this->getTime($timestamp);
        $list = array_merge($list, $this->getHumanReadable($timestamp));

        $swap = $list[0];
        $list[0] = $list[1];
        $list[1] = $swap;

        $items = array(
            'items' => $list
        );

        return $items;
    }

    protected function dealDate($date) {
        $timestamp = strtotime($date);

        $list = $this->getTime($timestamp);
        $list = array_merge($list, $this->getHumanReadable($timestamp));

        $items = array(
            'items' => $list
        );

        return $items;
    }

    protected function dealDays($type, $time, $unit) {
        $map = array(
            's' => 'seconds',
            'i' => 'minutes',
            'h' => 'hours',
            'd' => 'days',
            'w' => 'weeks',
            'm' => 'months',
            'y' => 'years',
        );

        if( ! isset($map[$unit])) {
            $list[] = array(
                'title' => 'You can input like bellow',
                'arg' => '',
            );
            $list[] = array(
                'title' => "Format: time [numbers][option], Example: {$type}{$time}d",
                'subtitle' => 'Options: s(seconds), i(minitues), h(hours), d(days), w(weeks), m(monthes), y(years)',
                'arg' => '',
            );
        } else {
            $timestamp = strtotime("{$type}{$time}{$map[$unit]}");
            $list = $this->getTime($timestamp);
            $list = array_merge($list, $this->getHumanReadable($timestamp));
        }

        $items = array(
            'rerun' => 1, // auto refresh(once per second)
            'items' => $list
        );

        return $items;
    }

    protected function dealTime($date) {
        $timestamp = strtotime($date);

        $list = $this->getTime($timestamp);
        $list = array_merge($list, $this->getHumanReadable($timestamp));

        $items = array(
            'items' => $list
        );

        return $items;
    }

    protected function dealFormat($date) {
        $timestamp = time();

        $l = $this->getTime($timestamp);

        $position = array('now', 'date', 'today', 'log');
        $list[] = $l[ array_search($date, $position) ];

        $list = array_merge($list, $this->getHumanReadable($timestamp));

        $items = array(
            'rerun' => 1, // auto refresh(once per second)
            'items' => $list
        );

        return $items;
    }

    protected function dealSubtraction($time1, $time2) {
        $subtract = round($time1-$time2, 2);

        $list = $this->getDuration($subtract);
        foreach($list as &$row) {
            $row['title'] = $row['title'] . ' apart';
        }
        unset($row);

        $items = array(
            'items' => $list
        );

        return $items;
    }

    protected function dealConversion($time, $unit) {
        switch($unit) {
            case 's':
                $timestamp = $time*1;
            break;
            case 'i':
                $timestamp = $time*60;
            break;
            case 'h':
                $timestamp = $time*3600;
            break;
            case 'd':
                $timestamp = $time*3600*24;
            break;
            case 'w':
                $timestamp = $time*3600*24*7;
            break;
            case 'm':
                $timestamp = $time*3600*24*30;
            break;
            case 'y':
                $timestamp = $time*3600*24*365;
            break;
        }

        $list = $this->getDuration($timestamp);

        $items = array(
            'items' => $list
        );

        return $items;
    }

    protected function dealTip($query) {
        $list[] = array(
            'title' => 'You can input like bellow',
            'arg' => '',
        );
        $list[] = array(
            'title' => 'Format: time [timestamp], Example: time 1601375920',
            'subtitle' => 'Timestamp: any number',
            'arg' => '',
        );
        $list[] = array(
            'title' => 'Format: time [time], Example: time 2020-09-29 19:09:37',
            'subtitle' => 'Time: any legal time',
            'arg' => '',
        );
        $list[] = array(
            'title' => 'Format: time [date], Example: time 2020-09-29',
            'subtitle' => 'Date: any legal date',
            'arg' => '',
        );
        $list[] = array(
            'title' => 'Format: time [+|-][numbers][option], Example: time +1d',
            'subtitle' => 'Options: s(seconds), i(minitues), h(hours), d(days), w(weeks), m(monthes), y(years)',
            'arg' => '',
        );
        $list[] = array(
            'title' => 'Format: time [numbers][option], Example: 1d',
            'subtitle' => 'Options: s(seconds), i(minitues), h(hours), d(days), w(weeks), m(monthes), y(years)',
            'arg' => '',
        );
        $list[] = array(
            'title' => 'Format: time [option], Example: time now',
            'subtitle' => 'Options: now, today, date, log',
            'arg' => '',
        );
        $list[] = array(
            'title' => 'Format: time [time]-[time]',
            'subtitle' => 'Example: time 2020-09-29 15:56:27 - 2020-09-29 15:56:20',
            'arg' => '',
        );

        $items = array(
            'items' => $list
        );

        return $items;
    }

    protected function getTime($timestamp) {
        $list[] = array(
            'title' => $timestamp,
            'arg' => $timestamp,
        );
        $list[] = array(
            'title' => date('Y-m-d H:i:s', $timestamp),
            'arg' => date('Y-m-d H:i:s', $timestamp),
        );
        $list[] = array(
            'title' => date('Y-m-d', $timestamp),
            'arg' => date('Y-m-d', $timestamp),
        );
        $list[] = array(
            'title' => date('Ymd', $timestamp),
            'arg' => date('Ymd', $timestamp),
        );

        return $list;
    }

    protected function getDuration($duration) {
        $second = abs(round($duration/1, 2));
        $minute = abs(round($duration/60, 2));
        $hour = abs(round($duration/3600, 2));
        $day = abs(round($duration/3600/24, 2));
        $week = abs(round($duration/3600/24/7, 2));
        $month = abs(round($duration/3600/24/30, 2));
        $year = abs(round($duration/3600/24/365, 2));

        $sign = $duration > 0 ? 1 : -1;

        $list[] = array(
            'title' => $sign*$second . ' seconds',
            'arg' => $sign*$second,
        );

        if($minute > 0) {
            $list[] = array(
                'title' => $sign*$minute . ' minutes',
                'arg' => $sign*$minute,
            );
        }

        if($hour > 0) {
            $list[] = array(
                'title' => $sign*$hour . ' hours',
                'arg' => $sign*$hour,
            );
        }

        if($day > 0) {
            $list[] = array(
                'title' => $sign*$day . ' days',
                'arg' => $sign*$day,
            );
        }

        if($week > 0) {
            $list[] = array(
                'title' => $sign*$week . ' weeks',
                'arg' => $sign*$week,
            );
        }

        if($month > 0) {
            $list[] = array(
                'title' => $sign*$month . ' months',
                'arg' => $sign*$month,
            );
        }

        if($year > 0) {
            $list[] = array(
                'title' => $sign*$year . ' years',
                'arg' => $sign*$year,
            );
        }

        return $list;
    }

    protected function getHumanReadable($timestamp) {
        $time = time();

        if($time == $timestamp) {
            $list[] = array(
                'title' => 'Tip: just now',
                'arg' => '',
            );
            return $list;
        }

        $second = abs(round(($time-$timestamp)/1, 2));
        $minute = abs(round(($time-$timestamp)/60, 2));
        $hour = abs(round(($time-$timestamp)/3600, 2));
        $day = abs(round(($time-$timestamp)/3600/24, 2));
        $week = abs(round(($time-$timestamp)/3600/24/7, 2));
        $month = abs(round(($time-$timestamp)/3600/24/30, 2));
        $year = abs(round(($time-$timestamp)/3600/24/365, 2));

        if($year >= 1) {
            $list[] = array(
                'title' => $time > $timestamp ? "Tip: {$year} years ago" : "Tip: in {$year} years",
                'arg' => $year,
            );
        } else if($month >= 1) {
            $list[] = array(
                'title' => $time > $timestamp ? "Tip: {$month} months ago" : "Tip: in {$month} months",
                'arg' => $month,
            );
        } else if($week >= 1) {
            $list[] = array(
                'title' => $time > $timestamp ? "Tip: {$week} weeks ago" : "Tip: in {$week} weeks",
                'arg' => $week,
            );
        } else if($day >= 1) {
            $list[] = array(
                'title' => $time > $timestamp ? "Tip: {$day} days ago" : "Tip: in {$day} days",
                'arg' => $day,
            );
        } else if($hour >= 1) {
            $list[] = array(
                'title' => $time > $timestamp ? "Tip: {$hour} hours ago" : "Tip: in {$hour} hours",
                'arg' => $hour,
            );
        } else if($minute >= 1) {
            $list[] = array(
                'title' => $time > $timestamp ? "Tip: {$minute} minutes ago" : "Tip: in {$minute} minutes",
                'arg' => $minute,
            );
        } else {
            $list[] = array(
                'title' => $time > $timestamp ? "Tip: {$second} seconds ago" : "Tip: in {$second} seconds",
                'arg' => $second,
            );
        }

        return $list;
    }
}

//=================Test===============
/*

$time = new Time();
// $time->deal('');
// $time->deal('1601377724');
//$time->deal('2020-09-29 18:13:52');
$time->deal('+1d');
// $time->deal('1d');
// $time->deal('now');
// $time->deal('2020-09-29 15:56:27 - 2020-09-29 15:56:20');

//*/