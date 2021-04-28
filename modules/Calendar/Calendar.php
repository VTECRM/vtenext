<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/

/* crmv@208173 */

require_once('modules/Calendar/Appointment.php');
require_once('modules/Calendar/Date.php');
class Calendar
{
    /* for dayview */
    public $day_start_hour = 0;
    public $day_end_hour = 23;
    public $sharedusers = [];

    public $view = 'day';
    public $date_time;
    public $day_slice;
    public $week_slice;
    public $week_array;
    public $month_array;
    public $year_array;
    public $hour_format = 'am/pm';
    public $week_hour_slices = [];
    public $slices = [];

    /**
     * Calendar constructor.
     * @param string $view
     * @param array $data
     */
    public function __construct($view='', $data=[])
    {
        $this->view = $view;
        $this->date_time = new vt_DateTime($data,true);
        $this->constructLayout();
    }
    /**
     * Function to get calendarview Label
     * @param string  $view   - calendarview
     * return string  - calendarview Label
     */
    public function getCalendarView($view)
    {
        switch($view)
        {
            case 'week':
                return "WEEK";
            case 'month':
                return "MON";
            case 'day':
                return "DAY";
            case 'year':
                return "YEAR";
            default:
                return null;
        }
    }

    /**
     * Function to get date info depends on calendarview
     * @param  string   $type  - string 'increment' or 'decrment'
     */

    public function get_datechange_info($type)
    {
        if($type == 'next')
            $mode = 'increment';
        else if($type == 'prev')
            $mode = 'decrment';
        switch($this->view)
        {
            case 'day':
                $day = $this->date_time->get_changed_day($mode);
                break;
            case 'week':
                $day = $this->date_time->get_first_day_of_changed_week($mode);
                break;
            case 'month':
                $day = $this->date_time->get_first_day_of_changed_month($mode);
                break;
            case 'year':
                $day = $this->date_time->get_first_day_of_changed_year($mode);
                break;
            default:
                return "view is not supported";
        }
        return $day->get_date_str();
    }

    /**
     * Function to get activities
     * @param  array $current_user  - user data
     * @param  string $free_busy    -
     */
    public function add_Activities($current_user, $free_busy='', $view_filter='') //crmv@7633s
    {
        if(isset($current_user->start_hour) && $current_user->start_hour !='')
        {
            list($sthour,$stmin)= explode(":",$current_user->start_hour);
            $hr = $sthour+0;
            $this->day_start_hour=$hr;
        }
        else
        {
            $this->day_start_hour=8;
        }

        if(isset($current_user->end_hour) && trim($current_user->end_hour) !='')
        {
            list($endhour,$endmin)=explode(":",$current_user->end_hour);
            $endhour = $endhour+0;
            $this->day_end_hour=$endhour;
        }
        else
        {
            $this->day_end_hour=23;
        }

        if ( $this->view == 'week')
        {
            $start_datetime = $this->date_time->getThisweekDaysbyIndex(1);	//crmv@17001
            $end_datetime = $this->date_time->getThisweekDaysbyIndex(7);	//crmv@17001
        } elseif($this->view == 'month') {
            $start_datetime = $this->date_time->getThismonthDaysbyIndex(0);
            $end_datetime = $this->date_time->getThismonthDaysbyIndex($this->date_time->daysinmonth-1);
        } elseif($this->view == 'year'){
            $start_datetime = $this->date_time->getThisyearMonthsbyIndex(0);
            $end_datetime = $this->date_time->get_first_day_of_changed_year('increment');
        }else {
            $start_datetime = $this->date_time;
            $end_datetime = $this->date_time->getTodayDatetimebyIndex(23);
        }

        $appointmentInstance = Appointment::getInstance();
        $activities = $appointmentInstance->readAppointment($current_user->id,$start_datetime,$end_datetime,$this->view,$view_filter); //crmv@7633s
        if(!empty($activities))
        {
            foreach($activities as $key=>$value)
            {
                if($this->view == 'day')
                {
                    array_push($this->day_slice[$value->formatted_datetime]->activities, $value);
                }
                elseif($this->view == 'week')
                {
                    array_push($this->week_slice[$value->formatted_datetime]->activities, $value);
                    $this->crmv_extend_week_view($value);
                }
                elseif($this->view == 'month')
                {
                    array_push($this->month_array[$value->formatted_datetime]->activities,$value);
                }
                elseif($this->view == 'year')
                {
                    array_push($this->year_array[$value->formatted_datetime]->activities,$value);
                }
                else
                    die("view:".$this->view." is not defined");

            }
        }

    }

    /**
     * Function to set values for calendar object depends on calendar view
     */
    public function constructLayout()
    {
        global $current_user;
        switch($this->view)
        {
            case 'day':
                for($i=-1;$i<=23;$i++)
                {
                    if($i == -1)
                    {
                        $layout = new Layout('hour',$this->date_time->getTodayDatetimebyIndex(0));
                        $this->day_slice[$layout->start_time->get_formatted_date().':notime'] = $layout;
                        $this->slices['notime'] = $layout->start_time->get_formatted_date().":notime";
                    }
                    else
                    {
                        $layout = new Layout('hour',$this->date_time->getTodayDatetimebyIndex($i));
                        $this->day_slice[$layout->start_time->get_formatted_date().':'.$layout->start_time->z_hour] = $layout;
                        array_push($this->slices,  $layout->start_time->get_formatted_date().":".$layout->start_time->z_hour);
                    }
                }
                break;
            case 'week':
                $weekview_days = 7;
                for($i=1;$i<=$weekview_days;$i++)	//crmv@17001
                {
                    $layout = new Layout('day',$this->date_time->getThisweekDaysbyIndex($i));
                    $this->week_array[$layout->start_time->get_formatted_date()] = $layout;
                    for($h=-1;$h<=23;$h++)
                    {
                        if($h == -1)
                        {
                            $hour_list = new Layout('hour',$this->date_time->getTodayDatetimebyIndex(0,$layout->start_time->day,$layout->start_time->month,$layout->start_time->year));
                            $this->week_slice[$layout->start_time->get_formatted_date().':notime'] = $hour_list;
                            $this->week_hour_slices['notime'] = $layout->start_time->get_formatted_date().":notime";
                        }
                        else
                        {
                            $hour_list = new Layout('hour',$this->date_time->getTodayDatetimebyIndex($h,$layout->start_time->day,$layout->start_time->month,$layout->start_time->year));
                            $this->week_slice[$layout->start_time->get_formatted_date().':'.$hour_list->start_time->z_hour] = $hour_list;
                            array_push($this->week_hour_slices,  $layout->start_time->get_formatted_date().":".$hour_list->start_time->z_hour);
                        }
                    }
                    array_push($this->slices,  $layout->start_time->get_formatted_date());

                }
                break;
            case 'month':
                $monthview_days = $this->date_time->daysinmonth;
                $firstday_of_month = $this->date_time->getThismonthDaysbyIndex(0);
                $num_of_prev_days = $firstday_of_month->dayofweek;
                for($i=0;$i<42;$i++)
                {
                    $layout = new Layout('day',$this->date_time->getThismonthDaysbyIndex($i-$num_of_prev_days));
                    $this->month_array[$layout->start_time->get_formatted_date()] = $layout;
                    array_push($this->slices,  $layout->start_time->get_formatted_date());
                }
                break;
            case 'year':
                $this->month_day_slices = [];
                for($i=0;$i<12;$i++)
                {
                    $layout = new Layout('month',$this->date_time->getThisyearMonthsbyIndex($i));
                    $this->year_array[$layout->start_time->z_month] = $layout;
                    $daysinmonth = $this->year_array[$layout->start_time->z_month]->start_time->daysinmonth;
                    $firstday_of_month = $this->year_array[$layout->start_time->z_month]->start_time->getThismonthDaysbyIndex(0);
                    $noof_prevdays = $firstday_of_month->dayofweek;
                    $year_monthdays = [];
                    for($m=0;$m<42;$m++)
                    {
                        $mday_list = new Layout('day',$this->year_array[$layout->start_time->z_month]->start_time->getThismonthDaysbyIndex($m-$noof_prevdays));
                        $year_monthdays[] = $mday_list->start_time->get_formatted_date();
                    }
                    $this->month_day_slices[$i] = $year_monthdays;
                    array_push($this->slices,  $layout->start_time->z_month);
                }
                break;
        }
    }

    public function crmv_extend_week_view($value) {
        list($data_,$hour_) = explode(":",$value->formatted_datetime);
        list($year_,$month_,$day_) = explode("-",$value->formatted_datetime);
        $day_diff = $value->end_time->day - $day_;
        if($day_diff != 0) {
            $count = 24 - $hour__;
        } else {
            $count = $value->end_time->hour - $hour__;
            if($value->end_time->minute > 0)
                $count++;
        }
        for($i=1;$i<$count;$i++) {
            $hour_2 = $hour_+$i;

            if(strlen($hour_2) < 2)
                $hour_2 = "0".$hour_2;
            //crmv@32334
            if (!is_array($this->week_slice[$data_.":".$hour_2]->activities)){
                $this->week_slice[$data_.":".$hour_2]->activities = [];
            }
            //crmv@32334 e
            array_push($this->week_slice[$data_.":".$hour_2]->activities, $value);
        }
    }
}

class Layout
{
    public $view = 'day';
    public $start_time;
    public $end_time;
    public $activities = [];

    /**
     * Constructor for Layout class
     * @param  string   $view - calendarview
     * @param  string   $time - time string
     */

    public function __construct($view,$time)
    {
        $this->view = $view;
        $this->start_time = $time;
        if ( $view == 'month')
            $this->end_time = $this->start_time->getMonthendtime();
        if ( $view == 'day')
            $this->end_time = $this->start_time->getDayendtime();
        if ( $view == 'hour')
            $this->end_time = $this->start_time->getHourendtime();
    }

    /**
     * Function to get view
     * return currentview
     */

    public function getView()
    {
        return $this->view;
    }
}