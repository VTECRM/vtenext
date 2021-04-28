/**
  * @description {Class} wdCalendar
  * This is the main class of wdCalendar.
  */

/* crmv@193430 */

//crmv@17001
var other_params = {};
var edit_permission;
var delete_permission;
var crmv_date_format;
var buddle_add_type = 'event'; //crmv@26030m
//crmv@17001e
//crmv@23542
//var fullcleargridcontainer = false;	//crmv@19218
//crmv@23542 end

function MarkDay() {
	parent.VTE.CalendarView.switchView('showdaybtn'); // crmv@199838
}

; (function($) {
    var __WDAY = new Array(i18n.xgcalendar.dateformat.sun, i18n.xgcalendar.dateformat.mon, i18n.xgcalendar.dateformat.tue, i18n.xgcalendar.dateformat.wed, i18n.xgcalendar.dateformat.thu, i18n.xgcalendar.dateformat.fri, i18n.xgcalendar.dateformat.sat);
    var __MonthName = new Array(i18n.xgcalendar.dateformat.jan, i18n.xgcalendar.dateformat.feb, i18n.xgcalendar.dateformat.mar, i18n.xgcalendar.dateformat.apr, i18n.xgcalendar.dateformat.may, i18n.xgcalendar.dateformat.jun, i18n.xgcalendar.dateformat.jul, i18n.xgcalendar.dateformat.aug, i18n.xgcalendar.dateformat.sep, i18n.xgcalendar.dateformat.oct, i18n.xgcalendar.dateformat.nov, i18n.xgcalendar.dateformat.dec);
    if (!Clone || typeof (Clone) != "function") {
        var Clone = function(obj) {
            var objClone = new Object();
            if (obj.constructor == Object) {
                objClone = new obj.constructor();
            } else {
                objClone = new obj.constructor(obj.valueOf());
            }
            for (var key in obj) {
                if (objClone[key] != obj[key]) {
                    if (typeof (obj[key]) == 'object') {
                        objClone[key] = Clone(obj[key]);
                    } else {
                        objClone[key] = obj[key];
                    }
                }
            }
            objClone.toString = obj.toString;
            objClone.valueOf = obj.valueOf;
            return objClone;
        }
    }
    if (!dateFormat || typeof (dateFormat) != "function") {
        var dateFormat = function(format) {
            var o = {
                "M+": this.getMonth() + 1,
                "d+": this.getDate(),
                "h+": this.getHours(),
                "H+": this.getHours(),
                "m+": this.getMinutes(),
                "s+": this.getSeconds(),
                "q+": Math.floor((this.getMonth() + 3) / 3),
                "w": "0123456".indexOf(this.getDay()),
                //crmv@17001
                "L": __MonthName[this.getMonth()], //non-standard
                "W": __WDAY[this.getDay()]
                //crmv@17001e
            };
            if (/(y+)/.test(format)) {
                format = format.replace(RegExp.$1, (this.getFullYear() + "").substr(4 - RegExp.$1.length));
            }
            for (var k in o) {
                if (new RegExp("(" + k + ")").test(format))
                    format = format.replace(RegExp.$1, RegExp.$1.length == 1 ? o[k] : ("00" + o[k]).substr(("" + o[k]).length));
            }
            return format;
        };
    }
    if (!DateAdd || typeof (DateDiff) != "function") {
        var DateAdd = function(interval, number, idate) {
            number = parseInt(number);
            var date;
            if (typeof (idate) == "string") {
                date = idate.split(/\D/);
                eval("var date = new Date(" + date.join(",") + ")");
            }

            if (typeof (idate) == "object") {
                date = new Date(idate.toString());
            }
            switch (interval) {
                case "y": date.setFullYear(date.getFullYear() + number); break;
                case "m": date.setMonth(date.getMonth() + number); break;
                case "d": date.setDate(date.getDate() + number); break;
                case "w": date.setDate(date.getDate() + 7 * number); break;
                case "h": date.setHours(date.getHours() + number); break;
                case "n": date.setMinutes(date.getMinutes() + number); break;
                case "s": date.setSeconds(date.getSeconds() + number); break;
                case "l": date.setMilliseconds(date.getMilliseconds() + number); break;
            }
            return date;
        }
    }
    if (!DateDiff || typeof (DateDiff) != "function") {
        var DateDiff = function(interval, d1, d2) {
            switch (interval) {
                case "d": //date
                case "w":
                    d1 = new Date(d1.getFullYear(), d1.getMonth(), d1.getDate());
                    d2 = new Date(d2.getFullYear(), d2.getMonth(), d2.getDate());
                    break;  //w
                case "h":
                    d1 = new Date(d1.getFullYear(), d1.getMonth(), d1.getDate(), d1.getHours());
                    d2 = new Date(d2.getFullYear(), d2.getMonth(), d2.getDate(), d2.getHours());
                    break; //h
                case "n":
                    d1 = new Date(d1.getFullYear(), d1.getMonth(), d1.getDate(), d1.getHours(), d1.getMinutes());
                    d2 = new Date(d2.getFullYear(), d2.getMonth(), d2.getDate(), d2.getHours(), d2.getMinutes());
                    break;
                case "s":
                    d1 = new Date(d1.getFullYear(), d1.getMonth(), d1.getDate(), d1.getHours(), d1.getMinutes(), d1.getSeconds());
                    d2 = new Date(d2.getFullYear(), d2.getMonth(), d2.getDate(), d2.getHours(), d2.getMinutes(), d2.getSeconds());
                    break;
            }
            var t1 = d1.getTime(), t2 = d2.getTime();
            var diff = NaN;
            switch (interval) {
                case "y": diff = d2.getFullYear() - d1.getFullYear(); break; //y
                case "m": diff = (d2.getFullYear() - d1.getFullYear()) * 12 + d2.getMonth() - d1.getMonth(); break;    //m
                case "d": diff = Math.floor(t2 / 86400000) - Math.floor(t1 / 86400000); break;
                case "w": diff = Math.floor((t2 + 345600000) / (604800000)) - Math.floor((t1 + 345600000) / (604800000)); break; //w
                case "h": diff = Math.floor(t2 / 3600000) - Math.floor(t1 / 3600000); break; //h
                case "n": diff = Math.floor(t2 / 60000) - Math.floor(t1 / 60000); break; //
                case "s": diff = Math.floor(t2 / 1000) - Math.floor(t1 / 1000); break; //s
                case "l": diff = t2 - t1; break;
            }
            return diff;

        }
    }
    if ($.fn.noSelect == undefined) {
        $.fn.noSelect = function(p) { //no select plugin by me :-)
            if (p == null)
                prevent = true;
            else
                prevent = p;
            if (prevent) {
                return this.each(function() {
                    if ($.browser.msie || $.browser.safari) $(this).bind('selectstart', function() { return false; });
                    else if ($.browser.mozilla) {
                        $(this).css('MozUserSelect', 'none');
                        $('body').trigger('focus');
                    }
                    else if ($.browser.opera) $(this).bind('mousedown', function() { return false; });
                    else $(this).attr('unselectable', 'on');
                });

            } else {
                return this.each(function() {
                    if ($.browser.msie || $.browser.safari) $(this).unbind('selectstart');
                    else if ($.browser.mozilla) $(this).css('MozUserSelect', 'inherit');
                    else if ($.browser.opera) $(this).unbind('mousedown');
                    else $(this).removeAttr('unselectable', 'on');
                });

            }
        }; //end noSelect
    }
    // crmv@170379
    // This script is released to the public domain and may be used, modified and
	// distributed without restrictions. Attribution not necessary but appreciated.
	// Source: https://weeknumber.net/how-to/javascript
    if (!Date.prototype.getWeek) {
    	// Returns the ISO week of the date.
    	Date.prototype.getWeek = function() {
    		var date = new Date(this.getTime());
    		date.setHours(0, 0, 0, 0);
    		// Thursday in current week decides the year.
    		date.setDate(date.getDate() + 3 - (date.getDay() + 6) % 7);
    		// January 4 is always in week 1.
    		var week1 = new Date(date.getFullYear(), 0, 4);
    		// Adjust to Thursday in week 1 and count number of weeks from date to week1.
    		return 1 + Math.round(((date.getTime() - week1.getTime()) / 86400000 - 3 + (week1.getDay() + 6) % 7) / 7);
    	}
    }
    if (!Date.prototype.getWeekYear) {
        // Returns the four-digit year corresponding to the ISO week of the date.
    	Date.prototype.getWeekYear = function() {
    		var date = new Date(this.getTime());
    		date.setDate(date.getDate() + 3 - (date.getDay() + 6) % 7);
    		return date.getFullYear();
    	}
    }
    // crmv@170379e
    $.fn.bcalendar = function(option) {
        var def = {
            /**
             * @description {Config} view
             * {String} Three calendar view provided, 'day','week','month'. 'week' by default.
             */
            view: "week",
            num_days: "",	//crmv@17001
            /**
             * @description {Config} weekstartday
             * {Number} First day of week 0 for Sun, 1 for Mon, 2 for Tue.
             */
            weekstartday: (('weekstart' in window) ? window.weekstart : 1), // crmv@150808
            theme: 0, //theme no
            /**
             * @description {Config} height
             * {Number} Calendar height, false for page height by default.
             */
            height: false,
            /**
             * @description {Config} url
             * {String} Url to request calendar data.
             */
            url: "",
            /**
             * @description {Config} eventItems
             * {Array} event items for initialization.
             */
            eventItems: [],
            method: "POST",
            /**
             * @description {Config} showday
             * {Date} Current date. today by default.
             */
            showday: new Date(),
            /**
	 	         * @description {Event} onBeforeRequestData:function(stage)
	 	         * Fired before any ajax request is sent.
	 	         * @param {Number} stage. 1 for retrieving events, 2 - adding event, 3 - removiing event, 4 - update event.
	           */
            onBeforeRequestData: false,
            /**
	 	         * @description {Event} onAfterRequestData:function(stage)
	 	         * Fired before any ajax request is finished.
	 	         * @param {Number} stage. 1 for retrieving events, 2 - adding event, 3 - removiing event, 4 - update event.
	           */
            onAfterRequestData: false,
            /**
	 	         * @description {Event} onAfterRequestData:function(stage)
	 	         * Fired when some errors occur while any ajax request is finished.
	 	         * @param {Number} stage. 1 for retrieving events, 2 - adding event, 3 - removiing event, 4 - update event.
	           */
            onRequestDataError: false,

            onWeekOrMonthToDay: false,
            /**
	 	         * @description {Event} quickAddHandler:function(calendar, param )
	 	         * Fired when user quick adds an item. If this function is set, ajax request to quickAddUrl will abort.
	 	         * @param {Object} calendar Calendar object.
	 	         * @param {Array} param Format [{name:"name1", value:"value1"}, ...]
	 	         *
	           */
            quickAddHandler: false,
            /**
             * @description {Config} quickAddUrl
             * {String} Url for quick adding.
             */
            quickAddUrl: "",
            /**
             * @description {Config} quickUpdateUrl
             * {String} Url for time span update.
             */
            quickUpdateUrl: "",
            /**
             * @description {Config} quickDeleteUrl
             * {String} Url for removing an event.
             */
            quickDeleteUrl: "",
            /**
             * @description {Config} autoload
             * {Boolean} If event items is empty, and this param is set to true.
             * Event will be retrieved by ajax call right after calendar is initialized.
             */
            autoload: false,
            /**
             * @description {Config} readonly
             * {Boolean} Indicate calendar is readonly or editable
             */
            readonly: false,
            /**
             * @description {Config} extParam
             * {Array} Extra params submitted to server.
             * Sample - [{name:"param1", value:"value1"}, {name:"param2", value:"value2"}]
             */
            extParam: [],
            /**
             * @description {Config} enableDrag
             * {Boolean} Whether end user can drag event item by mouse.
             */
            enableDrag: true,
            loadDateR: [],
   
			enableQuickCreate: true, // crmv@68357
        };
        var eventDiv = $("#gridEvent");
        if (eventDiv.length == 0) {
            eventDiv = $("<div id='gridEvent' style='display:none;'></div>").appendTo(document.body);
        }
        var gridcontainer = $(this);
        option = $.extend(def, option);
        //no quickUpdateUrl, dragging disabled.
        if (option.quickUpdateUrl == null || option.quickUpdateUrl == "") {
            option.enableDrag = false;
        }
        //template for month and date
        var __SCOLLEVENTTEMP = "<DIV style=\"WIDTH:${width};top:${top};left:${left};border: 1px solid ${bdcolor};border-radius:5px\" title=\"${title}\" class=\"chip chip${i} ${drag}\"><div class=\"dhdV\" style=\"display:none\">${data}</div><DIV class=ct>&nbsp;</DIV><DL style=\"BACKGROUND-COLOR:${bgcolor1}; COLOR: ${titletextcolor}; HEIGHT: ${height}px;\"><DT style=\"BACKGROUND-COLOR:${bgcolor2};COLOR:${titletextcolor}\">${invitedIcon} ${starttime} - ${endtime} ${icon}</DT><DD><SPAN>${content}</SPAN></DD><DIV class='resizer' style='display:${redisplay}'><DIV class=rszr_icon>&nbsp;</DIV></DIV></DL><DIV style=\"BACKGROUND-COLOR:${bgcolor1};\" class=cb1>&nbsp;</DIV><DIV class=cb2>&nbsp;</DIV></DIV>"; //crmv@20324 crmv@98866 crmv@140802
        var __ALLDAYEVENTTEMP = '<div class="rb-o ${eclass}" id="${id}" title="${title}" style="color:${color};"><div class="dhdV" style="display:none">${data}</div><div class="${extendClass} rb-m" style="background-color:${color}">${extendHTML}<div class="rb-i" style="COLOR:${titletextcolor}">${content}</div></div></div>'; // crmv@98866
        var __MonthDays = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        var __LASSOTEMP = "<div class='drag-lasso' style='left:${left}px;top:${top}px;width:${width}px;height:${height}px;'>&nbsp;</div>";
        //for dragging var
        var _dragdata;
        var _dragevent;

        //clear DOM
        clearcontainer();

        //no height specified in options, we get page height.
        if (!option.height) {
            option.height = document.documentElement.clientHeight;
        }
		
		// crmv@196645 - wait until the dom has the right nodes
        var countHeight = 0;
        var checkHeight = setInterval(function() {
            if (document.documentElement.clientHeight > 100) {
                option.height = document.documentElement.clientHeight;
                gridcontainer.height(option.height - 8);
                jQuery("#filterDivCalendar").height(parent.jQuery('#wdCalendar').height() - 40);
                clearInterval(checkHeight);
            } else {
                if (countHeight >= 20) clearInterval(checkHeight);
                countHeight++;                
            }
        }, 100);
        // crmv@196645e
		
        gridcontainer.css("overflow-y", "visible").height(option.height - 8);

        //populate events data for first display.
        if (option.url && option.autoload) {
            populate();
        }
        else {
            //contruct HTML
            render();
            //get date range
            var d = getRdate();
            pushER(d.start, d.end);
        }
        
        //clear DOM
        function clearcontainer() {
            gridcontainer.empty();
        }
        //get range
        function getRdate() {
            return { start: option.vstart, end: option.vend };
        }
        //add date range to cache.
        function pushER(start, end) {
            var ll = option.loadDateR.length;
            if (!end) {
                end = start;
            }
            if (ll == 0) {
                option.loadDateR.push({ startdate: start, enddate: end });
            }
            else {
                for (var i = 0; i < ll; i++) {
                    var dr = option.loadDateR[i];
                    var diff = DateDiff("d", start, dr.startdate);
                    if (diff == 0 || diff == 1) {
                        if (dr.enddate < end) {
                            dr.enddate = end;
                        }
                        break;
                    }
                    else if (diff > 1) {
                        var d2 = DateDiff("d", end, dr.startdate);
                        if (d2 > 1) {
                            option.loadDateR.splice(0, 0, { startdate: start, enddate: end });
                        }
                        else {
                            dr.startdate = start;
                            if (dr.enddate < end) {
                                dr.enddate = end;
                            }
                        }
                        break;
                    }
                    else {
                        var d3 = DateDiff("d", end, dr.startdate);

                        if (dr.enddate < end) {
                            if (d3 < 1) {
                                dr.enddate = end;
                                break;
                            }
                            else {
                                if (i == ll - 1) {
                                    option.loadDateR.push({ startdate: start, enddate: end });
                                }
                            }
                        }
                    }
                }
                //end for
                //clear
                ll = option.loadDateR.length;
                if (ll > 1) {
                    for (var i = 0; i < ll - 1; ) {
                        var d1 = option.loadDateR[i];
                        var d2 = option.loadDateR[i + 1];

                        var diff1 = DateDiff("d", d2.startdate, d1.enddate);
                        if (diff1 <= 1) {
                            d1.startdate = d2.startdate > d1.startdate ? d1.startdate : d2.startdate;
                            d1.enddate = d2.enddate > d1.enddate ? d2.enddate : d1.enddate;
                            option.loadDateR.splice(i + 1, 1);
                            ll--;
                            continue;
                        }
                        i++;
                    }
                }
            }
        }
        //contruct DOM
        function render() {
            //params needed
            //viewType, showday, events, config
            var showday = new Date(option.showday.getFullYear(), option.showday.getMonth(), option.showday.getDate());
            var events = option.eventItems;
            var config = { view: option.view, weekstartday: option.weekstartday, theme: option.theme };
            if (option.view == "day" || option.view == "week") {
                var $dvtec = $("#dvtec");
                if ($dvtec.length > 0) {
                    option.scoll = $dvtec.attr("scrollTop"); //get scroll bar position
                }
            }
            switch (option.view) {
                case "day":
                    BuildDaysAndWeekView(showday, 1, events, config);
                    break;
                case "week":
                	//crmv@17001
                	if (option.num_days != '')
                		BuildDaysAndWeekView(showday, option.num_days, events, config);
                	else
                	//crmv@17001e
                    	BuildDaysAndWeekView(showday, 7, events, config);
                    break;
                case "month":
                    BuildMonthView(showday, events, config);
                    break;
                default:
                    alert(i18n.xgcalendar.no_implement);
                    break;
            }
            initevents(option.view);
            ResizeView();
        }

        //build day view
        function BuildDaysAndWeekView(startday, l, events, config) {
            var days = [];
            if (l == 1) {
                var show = dateFormat.call(startday, i18n.xgcalendar.dateformat.Md);
                days.push({ display: show, date: startday, day: startday.getDate(), year: startday.getFullYear(), month: startday.getMonth() + 1 });
                option.datestrshow = CalDateShow(days[0].date);
                //crmv@24904
                option.datestrshow_hidden = CalDateShow2(days[0].date);
                //crmv@24904e
                option.vstart = days[0].date;
                option.vend = days[0].date;
            }
            else {
                var w = 0;

                if (l == 7) {
                    w = config.weekstartday - startday.getDay();
                    if (w > 0)  w = w - 7;
                }

                var ndate;

                // crmv@150808 - remove sunday
                var isSunday = (new Date()).getDay() === 0; // crmv@170379
                if (no_week_sunday && !isSunday) {
					--l;
					if (config.weekstartday == 0) {
						++w;
					}
                }
                // crmv@150808e

                for (var i = w, j = 0; j < l; i = i + 1, j++) {
                    ndate = DateAdd("d", i, startday);
                    var show = dateFormat.call(ndate, i18n.xgcalendar.dateformat.Md);
                    days.push({ display: show, date: ndate, day: ndate.getDate(), year: ndate.getFullYear(), month: ndate.getMonth() + 1 });
                }
                option.vstart = days[0].date;
                option.vend = days[l - 1].date;
                option.datestrshow = CalDateShow(days[0].date, days[l - 1].date);
                //crmv@24904
                option.datestrshow_hidden = CalDateShow2(days[0].date, days[l - 1].date);
                //crmv@24904e
            }

            var allDayEvents = [];
            var scollDayEvents = [];
            //get number of all-day events, including more-than-one-day events.
            var dM = PropareEvents(days, events, allDayEvents, scollDayEvents);

            var html = [];
            html.push("<div class=\"week-wrapper\"><span class=\"week-label\">"+i18n.xgcalendar.LBL_WEEK+"</span><span class=\"week-number\">"+startday.getWeek()+"</span> - <span class=\"week-year-label\">"+i18n.xgcalendar.LBL_YEAR+"</span><span class=\"week-year\">"+startday.getWeekYear()+"</span></div>"); // crmv@170379 // crmv@194723
            html.push("<div id=\"dvwkcontaienr\" class=\"wktopcontainer\">");
            html.push("<table class=\"wk-top\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">");
            BuildWT(html, days, allDayEvents, dM);
            html.push("</table>");
            html.push("</div>");

            //onclick=\"javascript:FunProxy('rowhandler',event,this);\"
            html.push("<div id=\"dvtec\"  class=\"scolltimeevent\"><table style=\"table-layout: fixed;", jQuery.browser.msie ? "" : "width:100%", "\" cellspacing=\"0\" cellpadding=\"0\"><tbody><tr><td>");
            html.push("<table style=\"height: "+((24-start_hour)*42)+"px\" id=\"tgTable\" class=\"tg-timedevents\" cellspacing=\"0\" cellpadding=\"0\"><tbody>");	//crmv@17001 : StartHour
            BuildDayScollEventContainer(html, days, scollDayEvents);
            html.push("</tbody></table></td></tr></tbody></table></div>");
            gridcontainer.html(html.join(""));
            html = null;
            //TODO event handlers
            //$("#weekViewAllDaywk").click(RowHandler);
            setHeightOpenAllDay();	//crmv@26548
        }
        //build month view
        function BuildMonthView(showday, events, config) {
            var cc = "<div id='cal-month-cc' class='cc'><div id='cal-month-cc-header'><div class='cc-close' id='cal-month-closebtn'></div><div id='cal-month-cc-title' class='cc-title'></div></div><div id='cal-month-cc-body' class='cc-body'><div id='cal-month-cc-content' class='st-contents'><table class='st-grid' cellSpacing='0' cellPadding='0'><tbody></tbody></table></div></div></div>";
            var html = [];
            html.push(cc);
            //build header
            html.push("<div id=\"mvcontainer\" class=\"mv-container\">");
            html.push("<table id=\"mvweek\" class=\"mv-daynames-table\" cellSpacing=\"0\" cellPadding=\"0\"><tbody><tr>");
            for (var i = config.weekstartday, j = 0; j < 7; i++, j++) {
                if (i > 6) i = 0;
                var p = { dayname: __WDAY[i] };
                html.push("<th class=\"mv-dayname\" title=\"", __WDAY[i], "\">", __WDAY[i], "");
            }
            html.push("</tr></tbody></table>");
            html.push("</div>");
            var bH = GetMonthViewBodyHeight() - GetMonthViewHeaderHeight();

            html.push("<div id=\"mvEventContainer\" class=\"mv-event-container\" style=\"height:", bH, "px;", "\">");
            BuilderMonthBody(html, showday, config.weekstartday, events, bH);
            html.push("</div>");
            gridcontainer.html(html.join(""));
            html = null;
            $("#cal-month-closebtn").click(closeCc);
        }
        function closeCc() {
            $("#cal-month-cc").css("visibility", "hidden");
        }

        //all-day event, including more-than-one-day events
        function PropareEvents(dayarrs, events, aDE, sDE) {
            var l = dayarrs.length;
            var el = events.length;
            var fE = [];
            var deB = aDE;
            var deA = sDE;
            for (var j = 0; j < el; j++) {
                var sD = events[j][2];
                var eD = events[j][3];
                var s = {};
                s.event = events[j];
                s.day = sD.getDate();
                s.year = sD.getFullYear();
                s.month = sD.getMonth() + 1;
                s.allday = events[j][4] == 1;
                s.crossday = events[j][5] == 1;
                s.reevent = events[j][6] == 1; //Recurring event
                s.daystr = [s.year, s.month, s.day].join("/");
                s.st = {};
                s.st.hour = sD.getHours();
                s.st.minute = sD.getMinutes();
                s.st.p = s.st.hour * 60 + s.st.minute; // start time
                s.et = {};
                s.et.hour = eD.getHours();
                s.et.minute = eD.getMinutes();
                s.et.p = s.et.hour * 60 + s.et.minute; // end time
                fE.push(s);
            }
            var dMax = 0;
            for (var i = 0; i < l; i++) {
                var da = dayarrs[i];
                deA[i] = []; deB[i] = [];
                da.daystr = da.year + "/" + da.month + "/" + da.day;
                for (var j = 0; j < fE.length; j++) {
                    if (!fE[j].crossday && !fE[j].allday) {
                        if (da.daystr == fE[j].daystr)
                            deA[i].push(fE[j]);
                    }
                    else {
                        if (da.daystr == fE[j].daystr) {
                            deB[i].push(fE[j]);
                            dMax++;
                        }
                        else {
                            if (i == 0 && da.date >= fE[j].event[2] && da.date <= fE[j].event[3])//first more-than-one-day event
                            {
                                deB[i].push(fE[j]);
                                dMax++;
                            }
                        }
                    }
                }
            }
            var lrdate = dayarrs[l - 1].date;
            for (var i = 0; i < l; i++) { //to deal with more-than-one-day event
                var de = deB[i];
                if (de.length > 0) { //
                    for (var j = 0; j < de.length; j++) {
                        var end = DateDiff("d", lrdate, de[j].event[3]) > 0 ? lrdate : de[j].event[3];
                        de[j].colSpan = DateDiff("d", dayarrs[i].date, end) + 1
                    }
                }
                de = null;
            }
            //for all-day events
            for (var i = 0; i < l; i++) {
                var de = deA[i];
                if (de.length > 0) {
                    var x = [];
                    var y = [];
                    var D = [];
                    var dl = de.length;
                    var Ia;
                    for (var j = 0; j < dl; ++j) {
                        var ge = de[j];
                        for (var La = ge.st.p, Ia = 0; y[Ia] > La; ) Ia++;
                        ge.PO = Ia; ge.ne = []; //PO is how many events before this one
                        y[Ia] = ge.et.p || 1440;
                        x[Ia] = ge;
                        if (!D[Ia]) {
                            D[Ia] = [];
                        }
                        D[Ia].push(ge);
                        if (Ia != 0) {
                            ge.pe = [x[Ia - 1]]; //previous event
                            x[Ia - 1].ne.push(ge); //next event
                        }
                        for (Ia = Ia + 1; y[Ia] <= La; ) Ia++;
                        if (x[Ia]) {
                            var k = x[Ia];
                            ge.ne.push(k);
                            k.pe.push(ge);
                        }
                        ge.width = 1 / (ge.PO + 1);
                        ge.left = 1 - ge.width;
                    }
                    var k = Array.prototype.concat.apply([], D);
                    x = y = D = null;
                    var t = k.length;
                    for (var y = t; y--; ) {
                        var H = 1;
                        var La = 0;
                        var x = k[y];
                        for (var D = x.ne.length; D--; ) {
                            var Ia = x.ne[D];
                            La = Math.max(La, Ia.VL);
                            H = Math.min(H, Ia.left)
                        }
                        x.VL = La + 1;
                        x.width = H / (x.PO + 1);
                        x.left = H - x.width;
                    }
                    for (var y = 0; y < t; y++) {
                        var x = k[y];
                        x.left = 0;
                        if (x.pe) for (var D = x.pe.length; D--; ) {
                            var H = x.pe[D];
                            x.left = Math.max(x.left, H.left + H.width);
                        }
                        var p = (1 - x.left) / x.VL;
                        x.width = Math.max(x.width, p);
                        x.aQ = Math.min(1 - x.left, x.width + 0.7 * p); //width offset
                    }
                    de = null;
                    deA[i] = k;
                }
            }
            return dMax;
        }

        function BuildWT(ht, dayarrs, events, dMax) {
            //1:
            ht.push("<tr>", "<th></th><th width=\"60\">&nbsp;</th>");//crmv@vte10usersFix

            for (var i = 0; i < dayarrs.length; i++) {
                var ev, title, cl, flagsLimit; // crmv@201442
                if (dayarrs.length == 1) {
                    ev = "";
                    title = "";
                    cl = "";
					flagsLimit = 4; // crmv@201442
                }
                else {
                    ev = ""; // "onclick=\"javascript:FunProxy('week2day',event,this);\"";
                    title = i18n.xgcalendar.to_date_view;
                    cl = "wk-daylink";
					flagsLimit = 2; // crmv@201442
                }
                
                // crmv@201442
                var flags = [];
                var dayDate = dateFormat.call(dayarrs[i].date, "dd-MM");
				if (holidays.indexOf(dayDate) > -1) {
					cl += ' wk-holiday';
					// now add classes for all countries only if more than 1 country is selected
					if (window.holidays_countries && holidays_countries.length > 1) {
						if (window.holidays_by_date && holidays_by_date[dayDate].push) {
							for (var j=0; j<holidays_by_date[dayDate].length; ++j) {
								var country = holidays_by_date[dayDate][j];
								flags.push('<span title="'+country+'" class="u-flag u-flag-'+country+'"></span>');
							}
						}
					}
				}
				if (flags.length > 0) {
					if (flags.length > flagsLimit) {
						var otherFlags = flags.splice(flagsLimit, flags.length-flagsLimit);
						var tooltip = '<span class=&quot;u-flags-cont&quot;>'+otherFlags.join('').replace(/"/g, '&quot;')+'</span>';
						flags.push('<i class="u-flag-more vteicon md-link" data-toggle="tooltip" data-html="true" title="'+tooltip+'">more_horiz</i>');
					}
					flags = '<span class="u-flags-cont">'+flags.join('')+'</span>';
				} else {
					flags = '';
				}
				// crmv@201442e

                //crmv@150808
                if (dayarrs.length == 7) {
	                var dayWidth = 'width=\"16%\"';
					var dow = dayarrs[i].date.getDay();
	                if (dow == 0 || dow == 6) { // sab e dom corti
	                	dayWidth = 'width=\"10%\"';
	                }
                } else {
                	var wday = 100 / dayarrs.length;
                	var dayWidth = 'width=\"' + wday + '%\"';
                }
                //crmv@150808e

                //crmv@17001
                ht.push("<th ", dayWidth, " abbr='", dateFormat.call(dayarrs[i].date, i18n.xgcalendar.dateformat.fulldayvalue), "' class='gcweekname' scope=\"col\"><div title='", title, "' ", ev, " class='wk-dayname' onClick=\"MarkDay();\"><span class='", cl, "'>", flags, dayarrs[i].display, "</span></div></th>"); // crmv@201442
				//crmv@17001e
            }
            ht.push("<th id=\"thAllDayLeft\" width=\"0px\" rowspan=\"3\"></th>");//crmv@vte10usersFix
            ht.push("</tr>"); //end tr1;
            //2:
            ht.push("<tr>");

            //crmv@vte10usersFix
            ht.push('<td></td>');

            var taskButtonLabel = '';
    		if (other_params["task"] && other_params["task"].indexOf('&noCompletedTask=0') > -1) {
    			taskButtonLabel = i18n.xgcalendar.buttonTaskHide;
    			taskImg = resourcever('modules/Calendar/wdCalendar/css/images/calendar/task.png');
    		}
    		else {
    			taskButtonLabel = i18n.xgcalendar.buttonTaskShow;
    			taskImg = resourcever('modules/Calendar/wdCalendar/css/images/calendar/task_grey.png');
    		}
			
    		// crmv@98866
    		//crmv@59091 
            ht.push('<td id="completedTaskButton" title="'+taskButtonLabel+'" ');
            // ht.push('style="overflow:hidden;border:1px solid #C3C3C3;font-size:7pt;text-align:center;background-color:#E8EEF7" ');
			//crmv@59091e
            ht.push('onclick="completedTask();"\">');
            // crmv@98866 end
            ht.push('<a href="javascript:void(0);" style="display:block;text-decoration:none;">');
            ht.push(taskButtonLabel);
            ht.push('</a>');
            ht.push('</td>');
            //crmv@vte10usersFix e

            ht.push("<td class=\"wk-allday\"");

            if (dayarrs.length > 1) {
                ht.push(" colSpan='", dayarrs.length, "'");
            }
            //onclick=\"javascript:FunProxy('rowhandler',event,this);\"

            //crmv@vte10usersFix
            var openAllDay = jQuery("#openAllDay").val(); // true = default
            var weekViewAllDaywk_container_Height = '';

            if (openAllDay == 'true') {
            	weekViewAllDaywk_container_Height = 'height:42px;';
            }

            ht.push("><div id=\"weekViewAllDaywk_container\" style=\"overflow-y:scroll;" + weekViewAllDaywk_container_Height + "\"><div id=\"weekViewAllDaywk\" ><table class=\"st-grid\" cellpadding=\"0\" cellspacing=\"0\"><tbody>");//crmv@23542 aggiunto scroll
        	//crmv@vte10usersFix e

            if (dMax == 0) {
                ht.push("<tr id='OpenAllDayWhite'>"); //crmv@vte10usersFix	//crmv@26548
                for (var i = 0; i < dayarrs.length; i++) {
                	//crmv@150808
                    if (dayarrs.length == 7) {
						var dayWidth = 'width=\"16%\"';
						var dow = dayarrs[i].date.getDay();
						if (dow == 0 || dow == 6) { // sab e dom corti
							dayWidth = 'width=\"10%\"';
						}
					} else {
						var wday = 100 / dayarrs.length;
						var dayWidth = 'width=\"' + wday + '%\"';
					}
                    //crmv@150808e

                    ht.push("<td ", dayWidth, " class=\"st-c st-s\"", " ch='qkadd' abbr='", dateFormat.call(dayarrs[i].date, "yyyy-M-d"), "' axis='00:00'>&nbsp;</td>");
                }
                ht.push("</tr>");
            }
            else {
                var l = events.length;
                var el = 0;
                var x = [];
                for (var j = 0; j < l; j++) {
                    x.push(0);
                }

                // crmv@20760
                ht.push("<tr>");
                for (var h = 0; h < l; h++) {
                	//crmv@150808
                    if (dayarrs.length == 7) {
						var dayWidth = 'width=\"16%\"';
						var dow = dayarrs[h].date.getDay();
						if (dow == 0 || dow == 6) { // sab e dom corti
							dayWidth = 'width=\"10%\"';
						}
					} else {
						var wday = 100 / dayarrs.length;
						var dayWidth = 'width=\"' + wday + '%\"';
					}
                    //crmv@150808e
                    ht.push("<td style=\"height: 0px\" ", dayWidth, " class='st-c st-s' ch='qkadd' abbr='", dateFormat.call(dayarrs[h].date, i18n.xgcalendar.dateformat.fulldayvalue), "' axis='00:00'> </td>");
                }
                ht.push("</tr>");
                // crmv@20760 e

                for (var j = 0; el < dMax; j++) {

                	//crmv@26629
                	//crmv@vte10usersFix
//                	if (el ==  (dMax - 1)) {
//                		ht.push("<tr id='OpenAllDayWhite'>");	//crmv@26548
//                	}
//                	else {
//                		ht.push("<tr>");
//                	}
                    //crmv@vte10usersFix e
                	ht.push("<tr>");
                	//crmv@26629e

                    for (var h = 0; h < l; ) {
                        var e = events[h][x[h]];

                        //crmv@150808
						if (dayarrs.length == 7) {
							var dayWidth = 'width=\"16%\"';
							var dow = dayarrs[h].date.getDay();
							if (dow == 0 || dow == 6) { // sab e dom corti
								dayWidth = 'width=\"10%\"';
							}
						} else {
							var wday = 100 / dayarrs.length;
							var dayWidth = 'width=\"' + wday + '%\"';
						}
						//crmv@150808e

                     // crmv@20760
                        //ht.push("<td ", dayWidth, " class='st-c");
                        ht.push("<td class='st-c");
                        if (e) { //if exists
                            x[h] = x[h] + 1;
                            ht.push("'");
                            var t = BuildMonthDayEvent(e, dayarrs[h].date, l - h);
                            if (e.colSpan > 1) {
                            	if (dayarrs.length == 7) {
                            		if ( (h+e.colSpan-1) < 5) {
                            			dayWidth = 16 * e.colSpan;
                            			dayWidth = 'width=\"'+dayWidth+'%\"';
                                    }
                            		else if ((h+e.colSpan-1) == 5) { // sab corto
                            			dayWidth = 16 * (e.colSpan-1) + 10;
                            			dayWidth = 'width=\"'+dayWidth+'%\"';
                            		}
                            		else if ((h+e.colSpan-1) > 5) { // dom corta
                            			dayWidth = 16 * (e.colSpan-2) + 10 + 10;
                            			dayWidth = 'width=\"'+dayWidth+'%\"';
                            		}
                            	}

                                ht.push(" ", dayWidth, "  colSpan='", e.colSpan, "'");
                                h += e.colSpan;
                            }
                            else {
                                h++;
                            }
                            ht.push(" ch='show'>", t);
                            t = null;
                            el++;
                        }
                        else {
                            ht.push(" st-s' ", dayWidth, " ch='qkadd' abbr='", dateFormat.call(dayarrs[h].date, i18n.xgcalendar.dateformat.fulldayvalue), "' axis='00:00'>&nbsp;");
                            h++;
                        }

                        ht.push("</td>");
                        // crmv@20760e
                    }
                    ht.push("</tr>");
                }

                // crmv@20760
                ht.push("<tr>");
                for (var h = 0; h < l; h++) {
                	//crmv@150808
                    if (dayarrs.length == 7) {
						var dayWidth = 'width=\"16%\"';
						var dow = dayarrs[h].date.getDay();
						if (dow == 0 || dow == 6) { // sab e dom corti
							dayWidth = 'width=\"10%\"';
						}
					} else {
						var wday = 100 / dayarrs.length;
						var dayWidth = 'width=\"' + wday + '%\"';
					}
                    //crmv@150808e
                    ht.push("<td style=\"height: 0px\" ", dayWidth, " class='st-c st-s' ch='qkadd' abbr='", dateFormat.call(dayarrs[h].date, i18n.xgcalendar.dateformat.fulldayvalue), "' axis='00:00'> </td>");
                }
                ht.push("</tr>");
                // crmv@20760 e
            }
            //crmv@23542
            ht.push("</tbody></table></div></div></td></tr>"); // stgrid end //wvAd end //td2 end //tr2 end
            //crmv@23542 end
            //3:
            ht.push("<tr>");

            //crmv@vte10usersFix
            ht.push('<td></td>');

            ht.push("<td></td><td id=\"allDayMinimizerTd\" onmouseover=\"allDayMinimizerTd_over();\" onmouseout=\"allDayMinimizerTd_out();\" onclick=\"javascript:allDayMinimizerTd_click();\" style=\"height:5px;\""); //crmv@vte10usersFix
            if (dayarrs.length > 1) {
                ht.push(" colSpan='", dayarrs.length, "'");
            }
            ht.push(">");

            ht.push('<a href="javascript:void(0);" style="display:block;text-decoration:none;">');
            ht.push('<div class="rszr_icon">&nbsp;</div>');
            ht.push('</a>');
            ht.push('</td>');
            ht.push('</tr>');
            //crmv@vte10usersFix e
        }

        function BuildDayScollEventContainer(ht, dayarrs, events) {
            //1:
            ht.push("<tr>");
            ht.push("<td style='width:60px;'></td>");

            //crmv@diego
            if (dayarrs.length == 7) {
				var dayWidth = 'width=\"16%\"';
	            for (var i = 0; i < dayarrs.length; i++) {
		            ht.push("<td ");
					//crmv@150808
                   	var dow = dayarrs[i].date.getDay();
					if (dow == 0 || dow == 6) { // sab e dom corti
						dayWidth = 'width=\"10%\"';
					} else {
						dayWidth = 'width=\"16%\"';
					}
					//crmv@150808e
		            ht.push(dayWidth, "><div id=\"tgspanningwrapper\" class=\"tg-spanningwrapper\"><div style=\"font-size: 20px\" class=\"tg-hourmarkers\">");
		            for (var j = 0; j < 24; j++) {
		            	//crmv@17001 : StartHour
		            	if (j >= start_hour)
		                	ht.push("<div class=\"tg-dualmarker\"></div>");
		                //crmv@17001 : StartHour e
		            }
	            }
            }
            else {
	            ht.push("<td");
	            if (dayarrs.length > 1) {
	                ht.push(" colSpan='", dayarrs.length, "'");
	            }
	            ht.push("><div id=\"tgspanningwrapper\" class=\"tg-spanningwrapper\"><div style=\"font-size: 20px\" class=\"tg-hourmarkers\">");
	            for (var i = 0; i < 24; i++) {
	            	//crmv@17001 : StartHour
	            	if (i >= start_hour)
	                	ht.push("<div class=\"tg-dualmarker\"></div>");
	                //crmv@17001 : StartHour e
	            }
	        }
            //crmv@diego e

            ht.push("</div></div></td></tr>");

            //2:
            ht.push("<tr>");
            ht.push("<td style=\"width: 60px\" class=\"tg-times\">");

            //get current time
            var now = new Date(); var h = now.getHours(); var m = now.getMinutes();
            var mHg = gP(h, m) - 4; //make middle alignment vertically
            ht.push("<div id=\"tgnowptr\" class=\"tg-nowptr\" style=\"left:0px;top:", mHg, "px\"></div>");
            var tmt = "";
            for (var i = 0; i < 24; i++) {
                tmt = fomartTimeShow(i);
                //crmv@17001 : StartHour
            	if (i >= start_hour)
	                ht.push("<div style=\"height: 41px\" class=\"tg-time\">", tmt, "</div>");
                //crmv@17001 : StartHour e
            }
            ht.push("</td>");

            var l = dayarrs.length;
            for (var i = 0; i < l; i++) {
                ht.push("<td class=\"tg-col\" ch='qkadd' abbr='", dateFormat.call(dayarrs[i].date, i18n.xgcalendar.dateformat.fulldayvalue), "'>");
                var istoday = dateFormat.call(dayarrs[i].date, "yyyyMMdd") == dateFormat.call(new Date(), "yyyyMMdd");
                // Today
                if (istoday) {
                    ht.push("<div style=\"margin-bottom: -"+((24-start_hour)*42)+"px; height:"+((24-start_hour)*42)+"px\" class=\"tg-today\">&nbsp;</div>");	//crmv@17001 : StartHour
					setResourcesTodayColumn(); //crmv@201468
                }
                //crmv@51216
                else if (holidays.indexOf(dateFormat.call(dayarrs[i].date, "dd-MM")) > -1) {
               		ht.push("<div style=\"margin-bottom: -"+((24-start_hour)*42)+"px; height:"+((24-start_hour)*42)+"px\" class=\"tg-holiday\">&nbsp;</div>");
                }
                //crmv@51216e
                //var eventC = $(eventWrap);
                //onclick=\"javascript:FunProxy('rowhandler',event,this);\"
                ht.push("<div style=\"margin-bottom: -"+((24-start_hour)*42)+"px; height: "+((24-start_hour)*42)+"px\" id='tgCol", i, "' class=\"tg-col-eventwrapper\">");	//crmv@17001 : StartHour
                BuildEvents(ht, events[i], dayarrs[i]);
                ht.push("</div>");

                ht.push("<div class=\"tg-col-overlaywrapper\" id='tgOver", i, "'>");
                if (istoday) {
                    var mhh = mHg + 4;
                    ht.push("<div id=\"tgnowmarker\" class=\"tg-hourmarker tg-nowmarker\" style=\"left:0px;top:", mhh, "px\"></div>");
                }
                ht.push("</div>");
                ht.push("</td>");
            }
            ht.push("</tr>");
        }
        //show events to calendar
        function BuildEvents(hv, events, sday) {
            for (var i = 0; i < events.length; i++) {
                var c;
                if (events[i].event[7]) { //crmv@20324
                    c = tc(events[i].event[7]); //theme
                }
                else {
                    c = tc(); //default theme
                }
                var tt = BuildDayEvent(c, events[i], i);
                hv.push(tt);
            }
        }
        function getTitle(event) {
            var timeshow, locationshow, attendsshow, eventshow;
            var showtime = event[4] != 1;
            eventshow = event[1];
            eventshow = eventshow.replace(/!#dollar#!/g,'$');	//crmv@26932
            var startformat = getymformat(event[2], null, showtime, true);
            var endformat = getymformat(event[3], event[2], showtime, true);
            timeshow = dateFormat.call(event[2], startformat) + " - " + dateFormat.call(event[3], endformat);
            locationshow = (event[9] != undefined && event[9] != "") ? event[9] : i18n.xgcalendar.i_undefined;
            attendsshow = (event[10] != undefined && event[10] != "") ? event[10] : "";
            var ret = [];
            if (event[4] == 1) {
                ret.push("[" + i18n.xgcalendar.allday_event + "]",$.browser.mozilla?"":"\r\n" );
            }
            else {
                if (event[5] == 1) {
                    ret.push("[" + i18n.xgcalendar.repeat_event + "]",$.browser.mozilla?"":"\r\n");
                }
            }
            ret.push(i18n.xgcalendar.time + ": ", timeshow+"\r\n", i18n.xgcalendar.event + ": ", eventshow, "\r\n", i18n.xgcalendar.location + ": ", locationshow+"\r\n", i18n.xgcalendar.event_description+": "+event[12]+"\r\n" ,i18n.xgcalendar.LBL_ASSIGNED_TO+": "+event[19]); // crmv@24270 crmv@198749
            if (attendsshow != "") {
                ret.push($.browser.mozilla?"":"\r\n", i18n.xgcalendar.participant + ":", attendsshow);
            }
            //crm@32837
            var iter = ret.length;
            for (var j=0;j<iter;j++){
            	ret[j] = ret[j].replace(/"/g,"'");
            }
            //crm@32837 e
            return ret.join("");
        }
        function BuildDayEvent(theme, e, index) {
        	//bdcolor : events border color
        	var bdcolor = window.darkMode && parseInt(darkMode) > 0 ? 'black' : 'white'; // crmv@187406
            var p = { bdcolor: bdcolor, bgcolor2: theme[0], bgcolor1: theme[2], width: "70%", icon: "", title: "", data: "", invitedIcon: "" }; // crmv@20871 crmv@46109 crmv@140802
            p.starttime = pZero(e.st.hour) + ":" + pZero(e.st.minute);
            p.endtime = pZero(e.et.hour) + ":" + pZero(e.et.minute);
            p.content = e.event[1];
            p.content = p.content.replace(/!#dollar#!/g,'$');	//crmv@26932
            p.title = getTitle(e.event);
            p.data = e.event.join("$");
            p.invitedIcon = e.event[14]; //crmv@20324
            /*var icons = [];
            icons.push("<I class=\"cic cic-tmr\">&nbsp;</I>");
            if (e.reevent) {
                icons.push("<I class=\"cic cic-spcl\">&nbsp;</I>");
            }*/
            p.icon = '';//icons.join(""); //crmv@20324
            var sP = gP(e.st.hour, e.st.minute);
            var eP = gP(e.et.hour, e.et.minute);
            p.top = sP + "px";
            p.left = (e.left * 100) + "%";
            p.width = (e.aQ * 100) + "%";
            //crmv@20871
            if ((e.aQ * 100) < 40) {
            	p.width = 40 + "%";
            }
            //crmv@20871e
            p.height = Math.max(eP - sP - 4, 20); // crmv@140802 - minimum height 20px
            p.i = index;
            if (option.enableDrag && e.event[8] == 1) {
                p.drag = "drag";
                p.redisplay = "block";
            }
            else {
                p.drag = "";
                p.redisplay = "none";
            }
            p.titletextcolor = parent.Color.getTitleTextColorHEX(theme[0]); // crmv@98866
            var newtemp = Tp(__SCOLLEVENTTEMP, p);
            p = null;
            return newtemp;
        }

        //get body height in month view
        function GetMonthViewBodyHeight() {
            return jQuery('#gridcontainer').height()+10; //return option.height; //crmv@21996
        }
        function GetMonthViewHeaderHeight() {
            return 21;
        }
        function BuilderMonthBody(htb, showday, startday, events, bodyHeight) {

            var firstdate = new Date(showday.getFullYear(), showday.getMonth(), 1);
            var diffday = startday - firstdate.getDay();
            var showmonth = showday.getMonth();
            if (diffday > 0) {
                diffday -= 7;
            }
            var startdate = DateAdd("d", diffday, firstdate);
            var enddate = DateAdd("d", 34, startdate);
            var rc = 5;

            if (enddate.getFullYear() == showday.getFullYear() && enddate.getMonth() == showday.getMonth() && enddate.getDate() < __MonthDays[showmonth]) {
                enddate = DateAdd("d", 7, enddate);
                rc = 6;
            }
            option.vstart = startdate;
            option.vend = enddate;
            option.datestrshow = CalDateShow(startdate, enddate);
            //crmv@24904
            option.datestrshow_hidden = CalDateShow2(startdate, enddate);
            //crmv@24904e
            bodyHeight = bodyHeight - 18 * rc;
            var rowheight = bodyHeight / rc;
            var roweventcount = parseInt(rowheight / 21);
            if (rowheight % 21 > 15) {
                roweventcount++;
            }
            var p = 100 / rc;
            var formatevents = [];
            var hastdata = formartEventsInHashtable(events, startday, 7, startdate, enddate);
            var B = [];
            var C = [];
            for (var j = 0; j < rc; j++) {
                var k = 0;
                formatevents[j] = b = [];
                for (var i = 0; i < 7; i++) {
                    var newkeyDate = DateAdd("d", j * 7 + i, startdate);
                    C[j * 7 + i] = newkeyDate;
                    var newkey = dateFormat.call(newkeyDate, i18n.xgcalendar.dateformat.fulldaykey);
                    b[i] = hastdata[newkey];
                    if (b[i] && b[i].length > 0) {
                        k += b[i].length;
                    }
                }
                B[j] = k;
            }

            eventDiv.data("mvdata", formatevents);
            for (var j = 0; j < rc; j++) {
                //onclick=\"javascript:FunProxy('rowhandler',event,this);\"
                htb.push("<div id='mvrow_", j, "' style=\"HEIGHT:", p, "%; TOP:", p * j, "%\"  class=\"month-row\">");
                htb.push("<table class=\"st-bg-table\" cellSpacing=\"0\" cellPadding=\"0\"><tbody><tr>");
                var dMax = B[j];

                for (var i = 0; i < 7; i++) {
                    var day = C[j * 7 + i];
                    htb.push("<td abbr='", dateFormat.call(day, i18n.xgcalendar.dateformat.fulldayvalue), "' ch='qkadd' axis='00:00' title=''");

                    if (dateFormat.call(day, "yyyyMMdd") == dateFormat.call(new Date(), "yyyyMMdd")) {
                        htb.push(" class=\"st-bg st-bg-today\">");
                    }
                    //crmv@51216
	                else if (holidays.indexOf(dateFormat.call(day, "dd-MM")) > -1) {
	               		htb.push(" class=\"st-bg st-bg-holiday\">");
	                }
	                //crmv@51216e
                    else {
                        htb.push(" class=\"st-bg\">");
                    }
                    htb.push("&nbsp;</td>");
                }
                //bgtable
                htb.push("</tr></tbody></table>");

                //stgrid
                htb.push("<table class=\"st-grid fixed\" cellpadding=\"0\" cellspacing=\"0\"><tbody>"); // crmv@98866

                //title tr
                htb.push("<tr>");
                //crmv@17001
                var titletemp = "<td class=\"st-dtitle${titleClass}\" ch='qkadd' abbr='${abbr}' axis='00:00' title=\"${title}\"><span class='monthdayshow' onClick=\"MarkDay();\">${dayshow}</span></a></td>";
				//crmv@17001e

                for (var i = 0; i < 7; i++) {
                    var o = { titleClass: "", dayshow: "" };
                    var day = C[j * 7 + i];
                    if (dateFormat.call(day, "yyyyMMdd") == dateFormat.call(new Date(), "yyyyMMdd")) {
                        o.titleClass = " st-dtitle-today";
                    }
                    if (day.getMonth() != showmonth) {
                        o.titleClass = " st-dtitle-nonmonth";
                    }
                    o.title = dateFormat.call(day, i18n.xgcalendar.dateformat.fulldayshow);
                    if (day.getDate() == 1) {
                        if (day.getMonth == 0) {
                            o.dayshow = dateFormat.call(day, i18n.xgcalendar.dateformat.fulldayshow);
                        }
                        else {
                            o.dayshow = dateFormat.call(day, i18n.xgcalendar.dateformat.Md3);
                        }
                    }
                    else {
                        o.dayshow = day.getDate();
                    }
                    o.abbr = dateFormat.call(day, i18n.xgcalendar.dateformat.fulldayvalue);
                    htb.push(Tp(titletemp, o));
                }
                htb.push("</tr>");
                var sfirstday = C[j * 7];
                BuildMonthRow(htb, formatevents[j], dMax, roweventcount, sfirstday);
                //htb=htb.concat(rowHtml); rowHtml = null;

                htb.push("</tbody></table>");
                //month-row
                htb.push("</div>");
            }

            formatevents = B = C = hastdata = null;
            //return htb;
        }

        //formate datetime
        function formartEventsInHashtable(events, startday, daylength, rbdate, redate) {
            var hast = new Object();
            var l = events.length;
            for (var i = 0; i < l; i++) {
                var sD = events[i][2];
                var eD = events[i][3];
                var diff = DateDiff("d", sD, eD);
                var s = {};
                s.event = events[i];
                s.day = sD.getDate();
                s.year = sD.getFullYear();
                s.month = sD.getMonth() + 1;
                s.allday = events[i][4] == 1;
                s.crossday = events[i][5] == 1;
                s.reevent = events[i][6] == 1; //Recurring event
                s.daystr = s.year + "/" + s.month + "/" + s.day;
                s.st = {};
                s.st.hour = sD.getHours();
                s.st.minute = sD.getMinutes();
                s.st.p = s.st.hour * 60 + s.st.minute; // start time position
                s.et = {};
                s.et.hour = eD.getHours();
                s.et.minute = eD.getMinutes();
                s.et.p = s.et.hour * 60 + s.et.minute; // end time postition

                if (diff > 0) {
                    if (sD < rbdate) { //start date out of range
                        sD = rbdate;
                    }
                    if (eD > redate) { //end date out of range
                        eD = redate;
                    }
                    var f = startday - sD.getDay();
                    if (f > 0) { f -= daylength; }
                    var sdtemp = DateAdd("d", f, sD);
                    for (; sdtemp <= eD; sD = sdtemp = DateAdd("d", daylength, sdtemp)) {
                        var d = Clone(s);
                        var key = dateFormat.call(sD, i18n.xgcalendar.dateformat.fulldaykey);
                        var x = DateDiff("d", sdtemp, eD);
                        if (hast[key] == null) {
                            hast[key] = [];
                        }
                        d.colSpan = (x >= daylength) ? daylength - DateDiff("d", sdtemp, sD) : DateDiff("d", sD, eD) + 1;
                        hast[key].push(d);
                        d = null;
                    }
                }
                else {
                    var key = dateFormat.call(events[i][2], i18n.xgcalendar.dateformat.fulldaykey);
                    if (hast[key] == null) {
                        hast[key] = [];
                    }
                    s.colSpan = 1;
                    hast[key].push(s);
                }
                s = null;
            }
            return hast;
        }
        function BuildMonthRow(htr, events, dMax, sc, day) {
            var x = [];
            var y = [];
            var z = [];
            var cday = [];
            var l = events.length;
            var el = 0;

            for (var j = 0; j < l; j++) {
                x.push(0);
                y.push(0);
                z.push(0);
                cday.push(DateAdd("d", j, day));
            }
            for (var j = 0; j < l; j++) {
                var ec = events[j] ? events[j].length : 0;
                y[j] += ec;
                for (var k = 0; k < ec; k++) {
                    var e = events[j][k];
                    if (e && e.colSpan > 1) {
                        for (var m = 1; m < e.colSpan; m++) {
                            y[j + m]++;
                        }
                    }
                }
            }
            //var htr=[];
            var tdtemp = "<td class='${cssclass}' axis='${axis}' ch='${ch}' abbr='${abbr}' title='${title}' ${otherAttr}>${html}</td>";
            for (var j = 0; j < sc && el < dMax; j++) {
                htr.push("<tr>");
                //var gridtr = $(__TRTEMP);
                for (var h = 0; h < l; ) {
                    var e = events[h] ? events[h][x[h]] : undefined;
                    var tempdata = { "class": "", axis: "", ch: "", title: "", abbr: "", html: "", otherAttr: "", click: "javascript:void(0);" };
                    var tempCss = ["st-c"];

                    if (e) {
                        x[h] = x[h] + 1;
                        //last event of the day
                        var bs = false;
                        if (z[h] + 1 == y[h] && e.colSpan == 1) {
                            bs = true;
                        }
                        if (!bs && j == (sc - 1) && z[h] < y[h]) {
                            el++;
                            $.extend(tempdata, { "axis": h, ch: "more", "abbr": dateFormat.call(cday[h], i18n.xgcalendar.dateformat.fulldayvalue), html: i18n.xgcalendar.others + (y[h] - z[h]) + i18n.xgcalendar.item, click: "javascript:alert('more event');" });
                            tempCss.push("st-more st-moreul");
                            h++;
                        }
                        else {
                            tempdata.html = BuildMonthDayEvent(e, cday[h], l - h);
                            tempdata.ch = "show";
                            if (e.colSpan > 1) {
                                tempdata.otherAttr = " colSpan='" + e.colSpan + "'";
                                for (var m = 0; m < e.colSpan; m++) {
                                    z[h + m] = z[h + m] + 1;
                                }
                                h += e.colSpan;

                            }
                            else {
                                z[h] = z[h] + 1;
                                h++;
                            }
                            el++;
                        }
                    }
                    else {
                        if (j == (sc - 1) && z[h] < y[h] && y[h] > 0) {
                            $.extend(tempdata, { "axis": h, ch: "more", "abbr": dateFormat.call(cday[h], i18n.xgcalendar.dateformat.fulldayvalue), html: i18n.xgcalendar.others + (y[h] - z[h]) + i18n.xgcalendar.item, click: "javascript:alert('more event');" });
                            tempCss.push("st-more st-moreul");
                            h++;
                        }
                        else {
                            $.extend(tempdata, { html: "&nbsp;", ch: "qkadd", "axis": "00:00", "abbr": dateFormat.call(cday[h], i18n.xgcalendar.dateformat.fulldayvalue), title: "" });
                            tempCss.push("st-s");
                            h++;
                        }
                    }
                    tempdata.cssclass = tempCss.join(" ");
                    tempCss = null;
                    htr.push(Tp(tdtemp, tempdata));
                    tempdata = null;
                }
                htr.push("</tr>");
            }
            x = y = z = cday = null;
            //return htr;
        }
        function BuildMonthDayEvent(e, cday, length) {
            var theme;
            if (e.event[7]) { //crmv@20324
                theme = tc(e.event[7]);
            }
            else {
                theme = tc();
            }
            var p = { color: theme[2], title: "", extendClass: "", extendHTML: "", data: "", invitedIcon: "" };
            p.invitedIcon = 'dd';//e.event[14]; //crmv@20324
            p.title = getTitle(e.event);
            p.id = "bbit_cal_event_" + e.event[0];
            if (option.enableDrag && e.event[8] == 1) {
                p.eclass = "drag";
            }
            else {
                p.eclass = "cal_" + e.event[0];
            }
            p.data = e.event.join("$");

            var sp = "<span style=\"cursor: pointer\">${content}</span>";
            var i = e.event[14] + ' ';//"<I class=\"cic cic-tmr\">&nbsp;</I>"; //crmv@20324
            //crmv@21363
            var i2 = "<I class=\"cic cic-rcr\">&nbsp;</I>";
            var ml = "<div class=\"st-ad-ml\"></div>";
            var mr = "<div class=\"st-ad-mr\"></div>";
            //crmv@21363e
            var arrm = [];
            var sf = e.event[2] < cday;
            var ef = DateDiff("d", cday, e.event[3]) >= length;  //e.event[3] >= DateAdd("d", 1, cday);
            if (sf || ef) {
                if (sf) {
                    arrm.push(ml);
                    p.extendClass = "st-ad-mpad ";
                }
                if (ef)
                { arrm.push(mr); }
                p.extendHTML = arrm.join("");

            }
            var cen;
            if (!e.allday && !sf) {
                cen = pZero(e.st.hour) + ":" + pZero(e.st.minute) + " " + e.event[1];
            }
            else {
                cen = e.event[1];
            }
            cen = cen.replace(/!#dollar#!/g,'$');	//crmv@26932
            var content = [];
            //crmv@20324
            content.push(i);
            content.push(Tp(sp, { content: cen }));
            //crmv@20324e

            if (e.reevent)
            { content.push(i2); }
            p.content = content.join("");
            
            p.titletextcolor = parent.Color.getTitleTextColorHEX(theme[0]); // crmv@98866
            
            return Tp(__ALLDAYEVENTTEMP, p);
        }
        //to populate the data
        function populate() {
            if (option.isloading) {
            	ajax_request.abort(); //crmv@26030m
            }
            if (option.url && option.url != "") {
                option.isloading = true;
                //clearcontainer();
                if (option.onBeforeRequestData && $.isFunction(option.onBeforeRequestData)) {
					//crmv@29954
					if (!option.onBeforeRequestData(1)) {
						option.isloading = false;
						return;
					}
					//crmv@29954e
                }
                var zone = new Date().getTimezoneOffset() / 60 * -1;
                //crmv@24648
                var d = getRdate();
                var date_start = '';
	            var date_end = '';
                if(typeof(d.start) != 'undefined' && typeof(d.end) != 'undefined'){
	                date_start = d.start.getFullYear()+'-'+("0" + (d.start.getMonth() + 1)).slice(-2)+'-'+("0" + d.start.getDate()).slice(-2);
	                date_end = d.end.getFullYear()+'-'+("0" + (d.end.getMonth() + 1)).slice(-2)+'-'+("0" + d.end.getDate()).slice(-2);
                }
                var param = [
                { name: "showdate", value: dateFormat.call(option.showday, i18n.xgcalendar.dateformat.fulldayvalue) },
                { name: "viewtype", value: option.view },
				{ name: "timezone", value: zone },
				{ name: "date_start", value: date_start },
				{ name: "date_end", value: date_end }
                ];
                //crmv@24648e
                if (option.extParam) {
                    for (var pi = 0; pi < option.extParam.length; pi++) {
                        param[param.length] = option.extParam[pi];
                    }
                }

				//crmv@17001
				tmp_url = option.url;

				//crmv@21363
				function searchAssignedUser(search, separator, str) {
					//crmv@62447
		        	if(parent.jQuery('#from_related').val()=='1'){
		            	parent.jQuery('#done_button').addClass('disabled');
		            	parent.jQuery('#more_button').addClass('disabled');
		            	parent.jQuery('#more_button').addClass('nextButtonDisSave');
		            	parent.jQuery('#done_button').removeClass('save');
		            	parent.jQuery('#more_button').removeClass('save');
		            	parent.jQuery('#done_button').prop('disabled',true);
		            	parent.jQuery('#more_button').prop('disabled',true);
		        	}
		        	//crmv@62447e
					if ((str) && str.indexOf(search + "=") > -1) {
						var fromIndex = str.indexOf(search + "=");
						var searchLen = (search + "=").length;
						var toIndex = str.indexOf(separator, fromIndex + searchLen);
						if ((toIndex > '-1') && (str.substring(fromIndex + searchLen, toIndex).lengh > 0)) {
							str = str.substr(0,fromIndex-1) + str.substr(toIndex,str.length);
						}
						else {
							str = str.substr(0,fromIndex-1);
						}
					}
					return str;
				}
				tmp_url = searchAssignedUser('filter_assigned_user_id', '&', tmp_url);
				//crmv@21363e

				if (other_params && other_params != '') {
					other_url = other_params["url"];

					//crmv@vte10usersFix
					if (other_params["task"]) {
						if (other_url) {
							other_url += other_params["task"];
						}
						else {
							other_url = '&filter_assigned_user_id=mine' + other_params["task"];
						}
					}
					//crmv@vte10usersFix e

					if (other_url && other_url != '')
						option.url = tmp_url+other_url;
					/*
					if (other_url["view"] && other_url["view"] != '')
						option.view = other_url["view"];
					if (other_url["num_days"] && other_url["num_days"] != '')
						option.view = other_url["num_days"];
					*/
				}
				//crmv@17001e

                ajax_request = $.ajax({ //crmv@26030m
                    type: option.method,
                    url: option.url,
                    data: param,
			        //dataType: "text",  // fixed jquery 1.4 not support Ms Date Json Format /Date(@Tickets)/
                    dataType: "json",
                    dataFilter: function(data, type) {
                        //return data.replace(/"\\\/(Date\([0-9-]+\))\\\/"/gi, "new $1");

                        return data;
                      },
                    success: function(data) {//function(datastr) {
						//datastr =datastr.replace(/"\\\/(Date\([0-9-]+\))\\\/"/gi, 'new $1');
                        //var data = (new Function("return " + datastr))();

                    	if (data != null && data.error != null) {
                            if (option.onRequestDataError) {
                                option.onRequestDataError(1, data);
                            }
                        }
                        else {
                            data["start"] = parseDate(data["start"]);
                            data["end"] = parseDate(data["end"]);
                            $.each(data.events, function(index, value) {
                                value[2] = parseDate(value[2]);
                                value[3] = parseDate(value[3]);
                            });
							// crmv@123735
							if (data.holidays) {
								window.holidays = data.holidays;
								window.holidays_countries = data.holidays_countries; // crmv@201442
								window.holidays_by_date = data.holidays_by_date; // crmv@201442
							}
							// crmv@123735e
                            responseData(data, data.start, data.end);
                            pushER(data.start, data.end);
                        }
                        if (option.onAfterRequestData && $.isFunction(option.onAfterRequestData)) {
                            option.onAfterRequestData(1);
                        }

                        option.isloading = false;
                    },
                    error: function(data) {
						try {
                            if (option.onRequestDataError) {
                                option.onRequestDataError(1, data);
                            } else {
                                alert(i18n.xgcalendar.get_data_exception);
                            }
                            if (option.onAfterRequestData && $.isFunction(option.onAfterRequestData)) {
                                option.onAfterRequestData(1);
                            }
                            option.isloading = false;
                        } catch (e) { }
                    }
                });
            }
            else {
                alert("url" + i18n.xgcalendar.i_undefined);
            }
        }
        function responseData(data, start, end) {
            var events;
            if (data.issort == false) {
                if (data.events && data.events.length > 0) {
                    events = data.sort(function(l, r) { return l[2] > r[2] ? -1 : 1; });
                }
                else {
                    events = [];
                }
            }
            else {
                events = data.events;
            }
            
            // crmv@68357
            // this is only a preliminary implementation
            if (option.preloadEvents && option.preloadEvents.length > 0) {
				for (var i=0; i<option.preloadEvents.length; ++i) {
					var pe = option.preloadEvents[i];
					var evt = [
						0,					// activityid
						pe.subject,			// subject
						new Date(pe.js_start_date),
						new Date(pe.js_end_date),
						false,				// all day event ?
						false,				// ??
						false,				// recurring ?
						'b0b0b0b0b0b0',		// color
						false,				// editable ?
						pe.location,		// location
						null,				// attendees
						pe.activitytype,	// activitytype
						pe.description,		// description
						0,					// related to
						'',					// invited icon ??
						'Events',			// moduletype ??
						true,				// visible?
						pe.eventstatus,		// status
						pe.eventstatus,		// status string
						pe.assigned_user_id,	// owner
						false,				// closable
					];
					events.push(evt);
				}
				if (parent.jQuery('#from_related').val()=='1') {
					parent.jQuery('#done_button').removeClass('disabled');
					parent.jQuery('#more_button').removeClass('disabled');
					parent.jQuery('#more_button').removeClass('nextButtonDisSave');
					parent.jQuery('#done_button').addClass('save');
					parent.jQuery('#more_button').addClass('save');
					parent.jQuery('#more_button').addClass('nextButtonSave');
					parent.jQuery('#done_button').prop('disabled', false);
					parent.jQuery('#more_button').prop('disabled', false);
				}
            }
            // crmv@68357e
            
            ConcatEvents(events, start, end);
            render();

        }
        function clearrepeat(events, start, end) {
            var jl = events.length;
            if (jl > 0) {
                var es = events[0][2];
                var el = events[jl - 1][2];
                for (var i = 0, l = option.eventItems.length; i < l; i++) {

                    if (option.eventItems[i][2] > el || jl == 0) {
                        break;
                    }
                    if (option.eventItems[i][2] >= es) {
                        for (var j = 0; j < jl; j++) {
                            if (option.eventItems[i][0] == events[j][0] && option.eventItems[i][2] < start) {
                                events.splice(j, 1); //for duplicated event
                                jl--;
                                break;
                            }
                        }
                    }
                }
            }
        }
        function ConcatEvents(events, start, end) {
            if (!events) {
                events = [];
            }
            //crmv@23542
            /*
            if (fullcleargridcontainer == true) {
            	option.eventItems = '';
            	fullcleargridcontainer = false;
           	}
           	*/
           	//crmv@23542 end
           	option.eventItems = '';
            //crmv@19218e
            if (events) {
                if (option.eventItems.length == 0) {
                    option.eventItems = events;
                }
                else {
                    //remove duplicated one
                    clearrepeat(events, start, end);
                    var l = events.length;
                    var sl = option.eventItems.length;
                    var sI = -1;
                    var eI = sl;
                    var s = start;
                    var e = end;
                    if (option.eventItems[0][2] > e)
                    {
                        option.eventItems = events.concat(option.eventItems);
                        return;
                    }
                    if (option.eventItems[sl - 1][2] < s)
                    {
                        option.eventItems = option.eventItems.concat(events);
                        return;
                    }
                    for (var i = 0; i < sl; i++) {
                        if (option.eventItems[i][2] >= s && sI < 0) {
                            sI = i;
                            continue;
                        }
                        if (option.eventItems[i][2] > e) {
                            eI = i;
                            break;
                        }
                    }

                    var e1 = sI <= 0 ? [] : option.eventItems.slice(0, sI);
                    var e2 = eI == sl ? [] : option.eventItems.slice(eI);
                    option.eventItems = [].concat(e1, events, e2);
                    events = e1 = e2 = null;
                }
            }
        }
        //utils goes here
        function weekormonthtoday(e) {
            var th = $(this);
            var daystr = th.attr("abbr");
            option.showday = strtodate(daystr + " 00:00");
            option.view = "day";
            MarkDay();
            render();
            if (option.onweekormonthtoday) {
                option.onweekormonthtoday(option);
            }
            return false;
        }
        function parseDate(str){
            return new Date(Date.parse(str));
        }
        function gP(h, m) {
        	h = h - start_hour;	//crmv@17001 : StartHour
            return h * 42 + parseInt(m / 60 * 42);
        }
        function gW(ts1, ts2) {
            var t1 = ts1 / 42;
            var t2 = parseInt(t1);
           	var t3 = t1 - t2 >= 0.5 ? 30 : 0;
            var t4 = ts2 / 42;
            var t5 = parseInt(t4);
           	var t6 = t4 - t5 >= 0.5 ? 30 : 0;
            //crmv@17001 : StartHour
            t2 = t2 + start_hour;
            t5 = t5 + start_hour;
            //crmv@17001 : StartHour e
            return { sh: t2, sm: t3, eh: t5, em: t6, h: ts2 - ts1 };
        }
        function gH(y1, y2, pt) {
            var sy1 = Math.min(y1, y2);
            var sy2 = Math.max(y1, y2);
            var t1 = (sy1 - pt) / 42;
            var t2 = parseInt(t1);
            var t3 = t1 - t2 >= 0.5 ? 30 : 0;
            var t4 = (sy2 - pt) / 42;
            var t5 = parseInt(t4);
            var t6 = t4 - t5 >= 0.5 ? 30 : 0;
            //crmv@17001 : StartHour
            t2 = t2 + start_hour;
            t5 = t5 + start_hour;
            //crmv@17001 : StartHour e
            return { sh: t2, sm: t3, eh: t5, em: t6, h: sy2 - sy1 };
        }
        function pZero(n) {
            return n < 10 ? "0" + n : "" + n;
        }
        //to get color list array
        function tc(d) {
        	//crmv@20324
        	function zc(c, i) {
                return '#' + c.substring(i*6,i*6+6);
            }

            //var c = d != null && d != undefined ? d : option.theme;
        	var c = d != null && d != undefined ? d : 'babababababa';// crmv@20871

        	return [zc(c, 0), 0, zc(c, 1), 0];
            //crmv@20324e
        }
        function Tp(temp, dataarry) {
            return temp.replace(/\$\{([\w]+)\}/g, function(s1, s2) { var s = dataarry[s2]; if (typeof (s) != "undefined") { return s; } else { return s1; } });
        }
        function Ta(temp, dataarry) {
            return temp.replace(/\{([\d])\}/g, function(s1, s2) { var s = dataarry[s2]; if (typeof (s) != "undefined") { return encodeURIComponent(s); } else { return ""; } });
        }
        function fomartTimeShow(h) {
            return h < 10 ? "0" + h + ":00" : h + ":00";
        }
        function getymformat(date, comparedate, isshowtime, isshowweek, showcompare) {
            var showyear = isshowtime != undefined ? (date.getFullYear() != new Date().getFullYear()) : true;
            var showmonth = true;
            var showday = true;
            var showtime = isshowtime || false;
            var showweek = isshowweek || false;
            if (comparedate) {
                //showyear = comparedate.getFullYear() != date.getFullYear(); // crmv@20210
                //showmonth = comparedate.getFullYear() != date.getFullYear() || date.getMonth() != comparedate.getMonth();
                if (comparedate.getFullYear() == date.getFullYear() &&
					date.getMonth() == comparedate.getMonth() &&
					date.getDate() == comparedate.getDate()
					) {
                    showyear = showmonth = showday = showweek = false;
                }
            }

            var a = [];
            if (showyear) {
                a.push(i18n.xgcalendar.dateformat.fulldayshow)
            } else if (showmonth) {
                a.push(i18n.xgcalendar.dateformat.Md3)
            } else if (showday) {
                a.push(i18n.xgcalendar.dateformat.day);
            }
            a.push(showweek ? " (W)" : "", showtime ? " HH:mm" : "");
            return a.join("");
        }
        //crmv@24904
        function getymformat2(date, comparedate, isshowtime, isshowweek, showcompare) {
            var showyear = isshowtime != undefined ? (date.getFullYear() != new Date().getFullYear()) : true;
            var showmonth = true;
            var showday = true;
            var showtime = isshowtime || false;
            var showweek = isshowweek || false;
            if (comparedate) {
                //showyear = comparedate.getFullYear() != date.getFullYear(); // crmv@20210
                //showmonth = comparedate.getFullYear() != date.getFullYear() || date.getMonth() != comparedate.getMonth();
                if (comparedate.getFullYear() == date.getFullYear() &&
					date.getMonth() == comparedate.getMonth() &&
					date.getDate() == comparedate.getDate()
					) {
                    showyear = showmonth = showday = showweek = false;
                }
            }

            var a = [];
            if (showyear) {
                a.push('d L yyyy')
            } else if (showmonth) {
                a.push('L d')
            } else if (showday) {
                a.push('d');
            }
            a.push(showweek ? " (W)" : "", showtime ? " HH:mm" : "");
            return a.join("");
        }
        //crmv@24904e
        function CalDateShow(startday, endday, isshowtime, isshowweek) {
            if (!endday) {
                return dateFormat.call(startday, getymformat(startday,null,isshowtime));
            } else {
                var strstart= dateFormat.call(startday, getymformat(startday, null, isshowtime, isshowweek));
				var strend=dateFormat.call(endday, getymformat(endday, startday, isshowtime, isshowweek));
				var join = (strend!=""? " - ":"");

				return [strstart,strend].join(join);
            }
        }
        //crmv@24904
        function CalDateShow2(startday, endday, isshowtime, isshowweek) {
            if (!endday) {
            	startday;
                return dateFormat.call(startday, getymformat2(startday,null,isshowtime));
            } else {
                var strstart= dateFormat.call(startday, getymformat2(startday, null, isshowtime, isshowweek));
				var strend=dateFormat.call(endday, getymformat2(endday, startday, isshowtime, isshowweek));
				var join = (strend!=""? " - ":"");

				return [strstart,strend].join(join);
            }
        }
        //crmv@24904e
        function dochange() {
            var d = getRdate();
            //crmv@23542
            /*
            var loaded = checkInEr(d.start, d.end);
            if (!loaded) {
                populate();
            }
            */
            populate();
            //crmv@23542 end
        }

        function checkInEr(start, end) {
        	//crmv@17001
			if (other_params && other_params != '') {
				other_url = other_params["url"];

				//crmv@vte10usersFix
				if (other_params["task"]) {
					if (other_url) {
						other_url += other_params["task"];
					}
					else {
						other_url = '&filter_assigned_user_id=mine' + other_params["task"];
					}
				}
				//crmv@vte10usersFix e

				if (other_url && other_url != '')
					return false;
			}
			//crmv@17001e
            var ll = option.loadDateR.length;
            if (ll == 0) {
                return false;
            }
            var r = false;
            var r2 = false;
            for (var i = 0; i < ll; i++) {
                r = false, r2 = false;
                var dr = option.loadDateR[i];
                if (start >= dr.startdate && start <= dr.enddate) {
                    r = true;
                }
                if (dateFormat.call(start, "yyyyMMdd") == dateFormat.call(dr.startdate, "yyyyMMdd") || dateFormat.call(start, "yyyyMMdd") == dateFormat.call(dr.enddate, "yyyyMMdd")) {
                    r = true;
                }
                if (!end)
                { r2 = true; }
                else {
                    if (end >= dr.startdate && end <= dr.enddate) {
                        r2 = true;
                    }
                    if (dateFormat.call(end, "yyyyMMdd") == dateFormat.call(dr.startdate, "yyyyMMdd") || dateFormat.call(end, "yyyyMMdd") == dateFormat.call(dr.enddate, "yyyyMMdd")) {
                        r2 = true;
                    }
                }
                if (r && r2) {
                    break;
                }
            }
            return r && r2;
        }

        function buildtempdayevent(sh, sm, eh, em, h, title, w, resize, thindex) {
            var theme = thindex != undefined && thindex >= 0 ? tc(thindex) : tc();
            var newtemp = Tp(__SCOLLEVENTTEMP, {
                bdcolor: theme[0],
                bgcolor2: theme[0],
                bgcolor1: theme[2],
                titletextcolor: parent.Color.getTitleTextColorHEX(theme[0]), // crmv@98866
                data: "",
                starttime: [pZero(sh), pZero(sm)].join(":"),
                endtime: [pZero(eh), pZero(em)].join(":"),
                content: title ? title : i18n.xgcalendar.new_event,
                title: title ? title : i18n.xgcalendar.new_event,
                invitedIcon: '', //crmv@20324
                icon: '',//"<I class=\"cic cic-tmr\">&nbsp;</I>", //crmv@20324
                top: "0px",
                left: "",
                width: w ? w : "100%",
                height: h - 4,
                i: "-1",
                drag: "drag-chip",
                redisplay: resize ? "block" : "none"
            });
            return newtemp;
        }

        function getdata(chip) {
            var hddata = chip.find("div.dhdV");
            if (hddata.length == 1) {
            	//crmv@17001
                //var str = hddata.text();
                var str = hddata.html();
                //crmv@17001e
                return parseED(str.split("$"));
            }
            return null;
        }
        function parseED(data) {
            if (data.length > 6) {
                var e = [];
                //crmv@26932
                data[1] = data[1].replace(/!#dollar#!/g,'$');
                data[12] = data[12].replace(/!#dollar#!/g,'$');
                //crmv@26932e
                //crmv@17001
				e.push(data[0], data[1], new Date(data[2]), new Date(data[3]), parseInt(data[4]), parseInt(data[5]), parseInt(data[6]), data[7] != undefined ? data[7] : 0, data[8] != undefined ? parseInt(data[8]) : 0, data[9], data[10], data[11], data[12], data[13], data[14], data[15],data[16] != undefined ? parseInt(data[16]) : 0, data[17], data[18], data[19], data[20], data[21]); //crmv@20211 crmv@21618 crmv@24883 crmv@25396 crmv@24270 crmv@120227
                //crmv@17001e
                return e;
            }
            return null;
        }
        function quickd(type) {
            $("#bbit-cs-buddle").css("visibility", "hidden");
            var calid = $("#bbit-cs-id").val();
            var param = [{ "name": "calendarId", value: calid },
                        { "name": "type", value: type}];
            var de = rebyKey(calid, true);
            option.onBeforeRequestData && option.onBeforeRequestData(3);
            $.post(option.quickDeleteUrl, param, function(data) {
                if (data) {
                    if (data.IsSuccess) {
                        de = null;
						//crmv@29954
						if (option.onAfterRequestData) {
							if (!option.onAfterRequestData(3)) {
								option.isloading = false;
								return;
							}
						}
						//crmv@29954e
                    }
                    else {
                    	alert(data.Msg);	//crmv@24041
                        option.onRequestDataError && option.onRequestDataError(3, data);
                        Ind(de);
                        render();
                        option.onAfterRequestData && option.onAfterRequestData(3);
                    }
                }
            }, "json");
            render();
        }
        function getbuddlepos(x, y) {
            var tleft = x - 110;
            var ttop = y - 217; //crmv@17001
            var maxLeft = document.documentElement.clientWidth;
            var maxTop = document.documentElement.clientHeight;
            var ishide = false;
            if (tleft <= 0 || ttop <= 0 || tleft + 400 > maxLeft) {
                tleft = x - 200 <= 0 ? 10 : x - 200;
                ttop = y - 159 <= 0 ? 10 : y - 159;
                if (tleft + 400 >= maxLeft) {
                    tleft = maxLeft - 410;
                }
                if (ttop + 164 >= maxTop) {
                    ttop = maxTop - 165;
                }
                ishide = true;
            }
            ishide = true;	//crmv@17001
            return { left: tleft, top: ttop, hide: ishide };
        }
        function dayshow(e, data) {
        	//crmv@62447
			if(parent.jQuery('#from_related').val()=='1' && !option.preloadEvents){ // crmv@68357
            	parent.jQuery('#done_button').addClass('disabled');
            	parent.jQuery('#more_button').addClass('disabled');
            	parent.jQuery('#more_button').addClass('nextButtonDisSave');
            	parent.jQuery('#done_button').removeClass('save');
            	parent.jQuery('#more_button').removeClass('save');
            	parent.jQuery('#done_button').prop('disabled',true);
            	parent.jQuery('#more_button').prop('disabled',true);
        	}
        	//crmv@62447e
            if (data == undefined) {
                data = getdata($(this));
            }
            activity_mode = data[15];	//crmv@22577
            if (data != null) {
            	//crmv@24883
                if (option.quickDeleteUrl != "" && option.readonly != true && data[16] == 1) {
                	//crmv@26807
					var csbuddle = '<div id="bbit-cs-buddle" style="z-index:1000;width:410px;visibility:hidden;" class="bubble crmvDiv">';
					csbuddle += '<table class="bubble-table" cellSpacing="0" cellPadding="0"><tbody>';
					csbuddle += '	<tr>';
					csbuddle += '		<td class="bubble-cell-main level3Bg" " valign="middle" style="text-align:center;padding:5px">';
					csbuddle += '			<div>'; //class="bubble-top"
					csbuddle += '				<div align="right">';
					csbuddle += '					<input id="bbit-cs-delete" class="crmbutton small delete" value="" type="button"/>';
					csbuddle += '					<input id="bbit-cs-close" class="crmbutton small save" value="" type="button"/>';
					//crmv@62447
					if(parent.jQuery('#from_related').val()!='1'){
						csbuddle += '					<input id="bbit-cs-editLink" class="crmbutton small edit" value="" type="button"/>';
					}
					//crmv@62447e
					csbuddle += '				</div>';
					csbuddle += '			</div>';
					csbuddle += '		</td>';
					csbuddle += '	</tr>';
					csbuddle += '	<tr>';
					csbuddle += '		<td class="bubble-mid">';
					csbuddle += '			<div style="overflow: hidden" id="bubbleContent1"><div><div></div>';
					csbuddle += '			<div class="cb-root">';
					csbuddle += '				<table class="cb-table" cellSpacing="0" cellPadding="0"><tbody><tr><td class="cb-value"><div class="textbox-fill-wrapper"><div class="textbox-fill-mid"><div id="bbit-cs-what" title="'
									+ i18n.xgcalendar.click_to_detail + '" class="textbox-fill-div lk" style="cursor:pointer;"></div></div></div></td></tr><tr><td class=cb-value><div id="bbit-cs-buddle-timeshow"></div></td></tr>';
					//crmv@17001	//preview event
                    csbuddle += '<tr><td>'+i18n.xgcalendar.event_type+': <span id="bbit-cs-eventtype"></span></td></tr>';
                    csbuddle += '<tr><td>'+i18n.xgcalendar.event_description+': <span id="bbit-cs-description"></span></td></tr>';
                    csbuddle += '<tr><td>'+i18n.xgcalendar.event_location+': <span id="bbit-cs-location"></span></td></tr>';
                    csbuddle += '<tr><td><span id="bbit-cs-status"></span></td></tr>';	//crmv@25396e
                    csbuddle += '<tr><td><span id="bbit-cs-assignedto"></span></td></tr>';	//crmv@24270
                    csbuddle += '<tr><td>&nbsp;</td></tr>';
                    csbuddle += '<tr><td>'+i18n.xgcalendar.event_related_to+': <span id="bbit-cs-relatedto"></span></td></tr>';
                    //crmv@17001e
                    csbuddle += '</tbody></table><div class="bbit-cs-split"><input id="bbit-cs-id" type="hidden" value=""/>';
                    csbuddle += '</div></div></div></div>';
                    csbuddle += '</tbody></table><div id="bubbleClose2" class="bubble-closebutton"></div><div id="prong1" class="prong"><div class=bubble-sprite></div></div></div>';
                    //crmv@26807e

                    var bud = $("#bbit-cs-buddle");
                    if (bud.length == 0) {
                        bud = $(csbuddle).appendTo(document.body);
					}

                    var calbutton = $("#bbit-cs-delete");
                    var lbtn = $("#bbit-cs-editLink");
                    //crmv@22577
					var quickCloseButton = $("#bbit-cs-close");
					if (data[8] == 1){
					quickCloseButton.unbind("click");	//crmv@91155
					quickCloseButton.click(function() {
						isloadingNew(true); //crmv@25392
						if (activity_mode == 'Task') {
							evt_status='&status=Completed';
						}
						else {
							evt_status='&eventstatus=Held';
						}
						jQuery("#bbit-cs-buddle").css("visibility", "hidden");//crmv@25392
						// crmv@192033
						jQuery.ajax({
							url: 'index.php',
							method: 'POST',
							data: "action=Save&module=Calendar&record="+$("#bbit-cs-id").val()+"&change_status=true"+evt_status,
							success: function(result) {
								jQuery("#gridcontainer").reload();	//crmv@25392
							}
						});
						// crmv@192033e
					});
					}
                    //crmv@22577e
                    var closebtn = $("#bubbleClose2").click(function() {
                        $("#bbit-cs-buddle").css("visibility", "hidden");
                    });
                    if (data[8] == 1){
                     calbutton.click(function() {
                         var data = $("#bbit-cs-buddle").data("cdata");
                         if (option.DeleteCmdhandler && $.isFunction(option.DeleteCmdhandler)) {
                             option.DeleteCmdhandler.call(this, data, quickd);
                         }
                         else {
                             if (confirm(i18n.xgcalendar.confirm_delete_event + "?")) {
                                 var s = 0; //0 single event , 1 for Recurring event
                                 if (data[6] == 1) {
                                     if (confirm(i18n.xgcalendar.confrim_delete_event_or_all)) {
                                         s = 0;
                                     }
                                     else {
                                         s = 1;
                                     }
                                 }
                                 else {
                                     s = 0;
                                 }
                                 quickd(s);
                             }
                         }
                     });
                    }
                    $("#bbit-cs-what").click(function(e) {
                        if (!option.ViewCmdhandler) {
                            alert("ViewCmdhandler" + i18n.xgcalendar.i_undefined);
                        }
                        else {
                        	//crmv@17001
                        	/*
                            if (option.ViewCmdhandler && $.isFunction(option.ViewCmdhandler)) {
                                option.ViewCmdhandler.call(this, $("#bbit-cs-buddle").data("cdata"));
                            }
                            */
                            //crmv@62447
                        	if(parent.jQuery('#from_related').val()=='1'){
                        		/* TODO click on related_add
                        		var activ_id = $("#bbit-cs-id").val();
                        		window.open('index.php?module=Calendar&action=DetailView&record='+activ_id,'_blank');
                        		*/
                        	}else{
                            	parent.location = "index.php?module=Calendar&action=DetailView&record="+$("#bbit-cs-id").val();
                            }
                        	//crmv@62447e
                    		//crmv@17001e
                        }
                        $("#bbit-cs-buddle").css("visibility", "hidden");
                        return false;
                    });
                    if (data[8] == 1){
                     lbtn.click(function(e) {
                     	//crmv@17001
                     	//crmv@21618
                     	if (data[15] == 'Task') {
                     		var act_mode = 'Task';
                     	}
                     	else {
                     		var act_mode = 'Events';
                     	}
                     	parent.location = "index.php?module=Calendar&action=EditView&activity_mode="+act_mode+"&record="+$("#bbit-cs-id").val()+"&return_module=Calendar&return_action=index";
                     	//crmv@21618e
                     	/*
                         if (!option.EditCmdhandler) {
                             alert("EditCmdhandler" + i18n.xgcalendar.i_undefined);
                         }
                         else {
                             if (option.EditCmdhandler && $.isFunction(option.EditCmdhandler)) {
                                 option.EditCmdhandler.call(this, $("#bbit-cs-buddle").data("cdata"));
                             }
                         }
                         */
                         //crmv@17001e
                         $("#bbit-cs-buddle").css("visibility", "hidden");
                         return false;
                     });
                    }
                    bud.click(function() { return false });

                    var pos = getbuddlepos(e.pageX, e.pageY);
                    if (pos.hide) {
                        $("#prong1").hide()
                    }
                    else {
                        $("#prong1").show()
                    }
                    var ss = [];
                    var iscos = DateDiff("d", data[2], data[3]) != 0;
                    ss.push(dateFormat.call(data[2], i18n.xgcalendar.dateformat.Md3), " (", __WDAY[data[2].getDay()], ")");
                    if (data[4] != 1) {
                        ss.push(",", dateFormat.call(data[2], "HH:mm"));
                    }

                    if (iscos) {
                        ss.push(" - ", dateFormat.call(data[3], i18n.xgcalendar.dateformat.Md3), " (", __WDAY[data[3].getDay()], ")");
                        if (data[4] != 1) {
                            ss.push(",", dateFormat.call(data[3], "HH:mm"));
                        }
                    }
                    var ts = $("#bbit-cs-buddle-timeshow").html(ss.join(""));
                    $("#bbit-cs-what").html(data[1]);
                    $("#bbit-cs-id").val(data[0]);
                    //crmv@17001	//crmv@22577	//crmv@25527
                    $("#bbit-cs-eventtype").html(data[11]);
                    $("#bbit-cs-description").html(data[12]);
                    $("#bbit-cs-location").html(data[9]);
                    $("#bbit-cs-relatedto").html(data[13]);
                    $("#bbit-cs-status").html(data[18]);
                    $("#bbit-cs-assignedto").html(i18n.xgcalendar.LBL_ASSIGNED_TO+': '+data[19]);	//crmv@24270

					//crmv@40498
                    // delete button
                   	if (delete_permission == 'yes' && data[8] == 1) {
                   		$("#bbit-cs-delete").val(i18n.xgcalendar.i_delete);
                   		$("#bbit-cs-delete").show();
                  	} else {
                  		$("#bbit-cs-delete").hide();
                  	}

                   	// edit button
                   	if (edit_permission == 'yes' && data[8] == 1) {
                    	$("#bbit-cs-editLink").val(i18n.xgcalendar.LBL_EDIT).show();
                    } else {
                    	$("#bbit-cs-editLink").hide();
                    }

                   	// close button
                    if (edit_permission == 'yes' && data[20] == 1) {
                    	if ((data[17] == 'Held') || (data[17] == 'Completed')) {
                    		jQuery("#bbit-cs-close").hide();
                    	}                    	else {
                    		jQuery("#bbit-cs-close").val(i18n.xgcalendar.i_close).show();
                    	}
                    } else {
                    	$("#bbit-cs-close").hide();
                    }
                    //crmv@40498e

                    //crmv@17001e	//crmv@22577e	//crmv@25527e
                    $("#bbit-cs-relatedto a").click(function() {
                   		parent.location = $(this).attr('href');
                    });
                    //crmv@17001e
                    bud.data("cdata", data);

                    //crmv@26807
                    if (pos.left > (jQuery(document).width() - 460)) {
                    	pos_left = jQuery(document).width() - 460;
                    }
                    else {
                    	pos_left = pos.left;
                    }
                    bud.css({ "visibility": "visible", left: pos_left, top: '86px' });//pos.top
                    //crmv@26807e
                    
                    $(document).one("click", function() {
                        $("#bbit-cs-buddle").css("visibility", "hidden");
                    });
                }
                else {
                    if (!option.ViewCmdhandler) {
                        alert("ViewCmdhandler" + i18n.xgcalendar.i_undefined);
                    }
                    else {
                    	//crmv@17001
                        /*
                        if (option.ViewCmdhandler && $.isFunction(option.ViewCmdhandler)) {
                            option.ViewCmdhandler.call(this, data);
                        }
                        */
                        //crmv@17001e
                    }
                }
                //crmv@24883e
                jQuery('#bbit-cs-buddle a').css('color','#112ABB');	//crmv@27010
            }
            else {
                alert(i18n.xgcalendar.data_format_error);
            }
            return false;
        }

        function moreshow(mv) {
            var me = $(this);
            var divIndex = mv.id.split('_')[1];
            var pdiv = $(mv);
            var offsetMe = me.position();
            var offsetP = pdiv.position();
            var width = (me.width() + 2) * 1.5;
            var top = offsetP.top + 15;
            var left = offsetMe.left;

            var daystr = this.abbr;
            var arrdays = daystr.split('/');
			//crmv@24656
			var day = new Date();
			day.setFullYear(arrdays[2]);
			day.setMonth(arrdays[0] - 1);
			day.setDate(arrdays[1]);
			//crmv@24656e
            var cc = $("#cal-month-cc");
            var ccontent = $("#cal-month-cc-content table tbody");
            var ctitle = $("#cal-month-cc-title");
            ctitle.html(dateFormat.call(day, i18n.xgcalendar.dateformat.Md3) + " " + __WDAY[day.getDay()]);
            ccontent.empty();

            var edata = $("#gridEvent").data("mvdata");
            var events = edata[divIndex];
            var index = parseInt(this.axis);
            var htm = [];
            for (var i = 0; i <= index; i++) {
                var ec = events[i] ? events[i].length : 0;
                for (var j = 0; j < ec; j++) {
                    var e = events[i][j];
                    if (e) {
                        if ((e.colSpan + i - 1) >= index) {
                            htm.push("<tr><td class='st-c'>");
                            htm.push(BuildMonthDayEvent(e, day, 1));
                            htm.push("</td></tr>");
                        }
                    }
                }
            }
            ccontent.html(htm.join(""));
            //click
            ccontent.find("div.rb-o").each(function(i) {
                $(this).click(function() {
                	var data = getdata($(this));
                	eventQuickEditView(data);
                });
				// crmv@fix - add drag to more panel
				if ($(this).hasClass("drag")) {
					//drag;
					$(this).mousedown(function(e) { dragStart.call(this, "m2", e); return false; });
				} else {
					$(this).mousedown(returnfalse)
				}
				// crmv@fix-e
            });

            edata = events = null;
            var height = cc.height();
            var maxleft = document.documentElement.clientWidth;
            var maxtop = document.documentElement.clientHeight;
            if (left + width >= maxleft) {
                left = offsetMe.left - (me.width() + 2) * 0.5;
            }
            if (top + height >= maxtop) {
                top = maxtop - height - 2;
            }
            var newOff = { left: left, top: top, "z-index": 180, width: width, "visibility": "visible" };
            cc.css(newOff);
            $(document).one("click", closeCc);
            return false;
        }
        function dayupdate(data, start, end) {
            if (option.quickUpdateUrl != "" && data[8] == 1 && option.readonly != true) {
                if (option.isloading) {
                    return false;
                }
                option.isloading = true;
                var id = data[0];
                var os = data[2];
                var od = data[3];
                var zone = new Date().getTimezoneOffset() / 60 * -1;
                var param = [{ "name": "calendarId", value: id },
							{ "name": "CalendarStartTime", value: dateFormat.call(start, i18n.xgcalendar.dateformat.fulldayvalue + " HH:mm") },
							{ "name": "CalendarEndTime", value: dateFormat.call(end, i18n.xgcalendar.dateformat.fulldayvalue + " HH:mm") },
							{ "name": "timezone", value: zone }
						   ];
                var d;
                if (option.quickUpdateHandler && $.isFunction(option.quickUpdateHandler)) {
                    option.quickUpdateHandler.call(this, param);
                }
                else {
					//crmv@29954
					if (option.onBeforeRequestData) {
						//crmv@33986
						if (!option.onBeforeRequestData(4,param)) {
	                        option.isloading = false;
	                        d = rebyKey(id, true);
	                        d[2] = os;
	                        d[3] = od;
	                        Ind(d);
	                        render();
	                        d = null;
	                        option.onAfterRequestData && option.onAfterRequestData(4);
	                        return;
						}
						//crmv@33986e
					}
					//crmv@29954e
                    $.post(option.quickUpdateUrl, param, function(data) {
                        if (data) {
                            if (data.IsSuccess == true) {
                                option.isloading = false;
                                option.onAfterRequestData && option.onAfterRequestData(4);
                            }
                            else {
                                option.onRequestDataError && option.onRequestDataError(4, data);
                                option.isloading = false;
                                d = rebyKey(id, true);
                                d[2] = os;
                                d[3] = od;
                                Ind(d);
                                render();
                                d = null;
                                option.onAfterRequestData && option.onAfterRequestData(4);
                            }
                        }
                    }, "json");
                    d = rebyKey(id, true);
                    if (d) {
                        d[2] = start;
                        d[3] = end;
                    }
                    //crmv@26030m
                    d[14] = d[14].replace("yes.png","pending.png");
                    d[14] = d[14].replace("no.png","pending.png");
                    //crmv@26030m e
                    Ind(d);
                    render();
                }
            }
        }
        function quickadd(start, end, isallday, pos) {
            if ((!option.quickAddHandler && option.quickAddUrl == "") || option.readonly) {
                return;
            }
            var buddle = $("#bbit-cal-buddle");
            resetBubble(isallday); //crmv@26030m
            if (buddle.length == 0) {
                var temparr = [];
                //crmv@26807
				temparr.push('<div id="bbit-cal-buddle" style="z-index:100;width:565px;visibility:hidden;" class="bubble crmvDiv">'); //crmv@20838 //crmv@59091	//crmv@OPER6317
                temparr.push('<table class="bubble-table" cellSpacing="0" cellPadding="0"><tbody>');

//				temparr.push('	<tr><td>');
//				temparr.push('		<table style="width:100%;padding:6px" border="0" cellspacing="0" cellpadding="0" class="mailClientWriteEmailHeader" id="emailHeader">');
//				temparr.push('			<tr>');
//				temparr.push('				<td></td>');
//				temparr.push('			</tr>');
//				temparr.push('		</table>');
//				temparr.push('	</td></tr>');

                temparr.push('	<tr>');
//                temparr.push('		<td class="bubble-cell-side">');
//                temparr.push('			<div id="tl1" class="bubble-corner"><div class="bubble-sprite bubble-tl"></div></div>');
//                temparr.push('		</td>');
                //crmv@26030m
                temparr.push('		<td class="bubble-cell-main level3Bg" " valign="middle" style="text-align:center;padding:5px">');
                temparr.push('			<div>'); //class="bubble-top"
                temparr.push('			<table width="100%">');
                temparr.push('				<tr>');
                temparr.push('					<td align="left">');
                temparr.push('						<div id="bbit-cal-buddle-timeshow" class="small"></div>');
                temparr.push('					</td>');
                temparr.push('					<td align="right">');
                temparr.push('						<input id="bbit-cal-start" type="hidden"/><input id="bbit-cal-end" type="hidden"/><input id="bbit-cal-allday" type="hidden"/>');
                temparr.push('						<input id="bbit-cal-editLink" class="crmbutton small edit" value="'+i18n.xgcalendar.update_detail+'" type="button"/>');
                temparr.push('						<input id="bbit-cal-quickAddBTN" class="crmbutton small save" value="'+i18n.xgcalendar.save+'" type="button"/>');
                temparr.push('					</td>');
                temparr.push('				</tr>');
                temparr.push('			</table>');
                temparr.push('			</div>');
                temparr.push('		</td>');
//                temparr.push('		<td class="bubble-cell-side"><div id="tr1" class="bubble-corner">');
//                temparr.push('			<div class="bubble-sprite bubble-tr"></div></div>');
                temparr.push('	</tr>');
                temparr.push('	<tr style="background-color:white;">');
                temparr.push('		<td>');
                //crmv@26935
                temparr.push('			<table id="event_task_div" cellSpacing="0" cellPadding="0" style="margin-top:3px;width:100%;">'); //crmv@59091
                temparr.push('				<tr>');
                temparr.push('					<td class="dvtTabCache" style="width: 10px;" nowrap="">&nbsp;</td>');
                temparr.push('					<td class="dvtSelectedCell">');
                temparr.push('						<a id="change_to_event_type" href="javascript:eventButton();">'+i18n.xgcalendar.event+'</a>');
                temparr.push('					</td>');
                temparr.push('					<td class="dvtTabCache" style="width: 10px;" nowrap="">&nbsp;</td>');
                temparr.push('					<td class="dvtUnSelectedCell">');
                temparr.push('						<a id="change_to_task_type" href="javascript:taskButton();">'+i18n.xgcalendar.task+'</a></div>');
                temparr.push('					</td>');
                temparr.push('					<td class="dvtTabCache" style="width: 289px;" nowrap="">&nbsp;</td>');
                temparr.push('				</tr>');
                temparr.push('			</table>');
                //crmv@26935e
                temparr.push('		</td>');
                temparr.push('	</tr>');

                temparr.push('	<tr>');
                temparr.push('		<td class="bubble-mid" >');//colSpan="3"
                temparr.push('			<div style="overflow: hidden" id="bubbleContent1"><div><div></div>');
                temparr.push('			<div class="cb-root">');

                temparr.push('<table class="cb-table" cellSpacing="0" cellPadding="0"><tbody><tr><th class="cb-key">');
                //temparr.push(i18n.xgcalendar.time, ':</th><td class=cb-value><div id="bbit-cal-buddle-timeshow"></div></td></tr><tr><th id="bubble_concent_th" class="cb-key">');
                temparr.push(i18n.xgcalendar.content, ':</th><td class="cb-value"><div class="textbox-fill-wrapper"><div class="textbox-fill-mid"><input id="bbit-cal-what" class="textbox-fill-input detailedViewTextBox"/></div></div><div class="cb-example">');	//crmv@OPER6317
                temparr.push(i18n.xgcalendar.example, '</div></td></tr>');
                //crmv@17001	//crmv@OPER6317
                temparr.push('<tr id="buddle_tr_event_type"><th class="cb-key">'+i18n.xgcalendar.event_type+':</th><td class="cb-value"><div class="textbox-fill-wrapper"><div class="textbox-fill-mid" style="position:relative;">'+eventType+'</div></div></td></tr>');	//crmv@OPER5104
                temparr.push('<tr id="buddle_tr_event_description"><th class="cb-key">'+i18n.xgcalendar.event_description+':</th><td class="cb-value"><div class="textbox-fill-wrapper"><div class="textbox-fill-mid"><input id="bbit-cal-description" class="textbox-fill-input detailedViewTextBox"/></div></div></td></tr>');
                temparr.push('<tr id="buddle_tr_event_location"><th class="cb-key">'+i18n.xgcalendar.event_location+':</th><td class="cb-value"><div class="textbox-fill-wrapper"><div class="textbox-fill-mid"><input id="bbit-cal-location" class="textbox-fill-input detailedViewTextBox"/></div></div></td></tr>');
                //crmv@17001e	//crmv@OPER6317e
                //crmv@26030m e

                temparr.push('<tr id="buddle_tr_invite_search"><th class="cb-key">'+ i18n.xgcalendar.invite + ':' + '</th>'+ '<td class="cb-value" id="optionUserDetails_search"></td></tr>'+
			                '<tr id="buddle_tr_invite_add"><th class="cb-key"></th>'+ '<td class="cb-value" id="optionUserDetails_add"></td></tr>');
                temparr.push('<tr id="buddle_tr_link">'+addEventLinkUI(i18n.xgcalendar.relate)+'</tr>');
                temparr.push('<tr id="buddle_tr_singleContactlink" style="display:none;">'+addEventSingleContactLinkUI()+'</tr>');
                temparr.push('<tr id="buddle_tr_contactslink">'+addEventContactsLinkUI()+'</tr>');
                temparr.push('</tbody></table>');
                temparr.push('</div></div></div>');
                //temparr.push('<tr><td><div id="bl1" class="bubble-corner"><div class="bubble-sprite bubble-bl"></div></div><td><div class="bubble-bottom"></div><td><div id="br1" class="bubble-corner"><div class="bubble-sprite bubble-br"></div></div></tr>');
                temparr.push('</tbody></table><div id="bubbleClose1" class="bubble-closebutton"></div><div id="prong2" class="prong"><div class=bubble-sprite></div></div></div>');
                //crmv@26807 e

                var tempquickAddHanler = temparr.join("");
                temparr = null;
                $(document.body).append(tempquickAddHanler);
                getInviteesDetail();
                buddle = $("#bbit-cal-buddle");
                var calbutton = $("#bbit-cal-quickAddBTN");
                var lbtn = $("#bbit-cal-editLink");
                var closebtn = $("#bubbleClose1").click(function() {
                    $("#bbit-cal-buddle").css("visibility", "hidden");
                    realsedragevent();
                    //crmv@22173
                    $("#bbit-cal-description").val('');
                    $("#bbit-cal-location").val('');
                    //crmv@22173e
                    buddle.find('.picklistTab').css('display','none');	//crmv@26935
                });
                calbutton.click(function(e) {
                	if (buddle_add_type == 'event') {	//crmv@26030m
	                    if (option.isloading) {
	                        return false;
	                    }
	                    option.isloading = true;
	                    var what = $("#bbit-cal-what").val();
	                    var datestart = $("#bbit-cal-start").val();
	                    var dateend = $("#bbit-cal-end").val();
	                    var allday = $("#bbit-cal-allday").val();

	                    //crmv@26807
	                    var inviteesid_users = new Array();
	                    var inviteesid_contacts = new Array();
	                    var invId = '';
	                    jQuery('#selectedTable tr').each(function(){
                    		invId = jQuery(this).attr('id').split('_');
                    		if (invId[1] == 'Users') {
                    			inviteesid_users.push(invId[0]);
                    		}
                    		else if (invId[1] == 'Contacts') {
                    			inviteesid_contacts.push(invId[0]);
                    		}
	                    });
	                    inviteesid_users = inviteesid_users.join(';');
	                    inviteesid_contacts = inviteesid_contacts.join(';');

	                    var contactidlist = new Array();
	                    jQuery('#contacts_div table tr').each(function(){
	                    	contactidlist.push(jQuery(this).find('td').attr('id'));
	                    });
	                    contactidlist = contactidlist.join(';');
	                    //crmv@26807e

	                    var f = /^[^\$\<\>]+$/.test(what);
	                    if (!f) {
	                        alert(i18n.xgcalendar.invalid_title);
	                        $("#bbit-cal-what").focus();
	                        option.isloading = false;
	                        return false;
	                    }
	                    var zone = new Date().getTimezoneOffset() / 60 * -1;
	                    var param = [{ "name": "CalendarTitle", value: what },
							{ "name": "CalendarStartTime", value: datestart },
							{ "name": "CalendarEndTime", value: dateend },
							{ "name": "IsAllDayEvent", value: allday },
							{ "name": "timezone", value: zone},
							//crmv@26807
							{ "name": "EventType", value: $("#activitytype_show_button").attr('name')},	//crmv@17001
							{ "name": "Description", value: $("#bbit-cal-description").val()},	//crmv@17001
							{ "name": "Location", value: $("#bbit-cal-location").val()},	//crmv@17001
							{ "name": "inviteesid", value: inviteesid_users },
							{ "name": "inviteesid_con", value: inviteesid_contacts },
							{ "name": "parent_id", value: jQuery("#parent_id_link").val() },
							{ "name": "contactidlist", value: contactidlist },
							//crmv@26807e
							];

	                    if (option.extParam) {
	                        for (var pi = 0; pi < option.extParam.length; pi++) {
	                            param[param.length] = option.extParam[pi];
	                        }
	                    }

	                    if (option.quickAddHandler && $.isFunction(option.quickAddHandler)) {
	                        option.quickAddHandler.call(this, param);
	                        $("#bbit-cal-buddle").css("visibility", "hidden");
	                        realsedragevent();
	                    }
	                    else {
	                        var newdata = [];
	                        var tId = -1;
							//crmv@29954
							if (option.onBeforeRequestData) {
								if (!option.onBeforeRequestData(2, param)) {
									option.isloading = false;
									return;
							}
							}
							$("#bbit-cal-buddle").css("visibility", "hidden");
							//crmv@29954e
	                        $.post(option.quickAddUrl, param, function(data) {
	                            if (data) {
	                                if (data.IsSuccess == true) {
	                                    option.isloading = false;
	                                    option.eventItems[tId][0] = data.Data;
	                                    option.eventItems[tId][8] = 1;
	                                    render();
	                                    option.onAfterRequestData && option.onAfterRequestData(2);
	                                }
	                                else {
	                                    option.onRequestDataError && option.onRequestDataError(2, data);
	                                    option.isloading = false;
	                                    option.onAfterRequestData && option.onAfterRequestData(2);
	                                }
	                                jQuery("#gridcontainer").reload(); //crmv@25392
	                            }

	                        }, "json");

	                        newdata.push(-1, what);
	                        var sd = strtodate(datestart);
	                        var ed = strtodate(dateend);
	                        var diff = DateDiff("d", sd, ed);
	                        newdata.push(sd, ed, allday == "1" ? 1 : 0, diff > 0 ? 1 : 0, 0);
	                        newdata.push(-1, 0, $("#bbit-cal-location").val(), "", $("#activitytype_show_button").attr('name'), $("#bbit-cal-description").val());	//crmv@26807
	                        tId = Ind(newdata);
	                        //crmv@20324
	                        option.eventItems[tId][7] = current_user_color;
	                        option.eventItems[tId][14] = '';
	                        //crmv@20324e
	                        realsedragevent();
	                    }
					//crmv@26030m
	                } else if (buddle_add_type == 'task') {
                		var datestart = new Date($("#bbit-cal-start").val());
	                    var dateend = new Date($("#bbit-cal-end").val());
	                    datestart = js2Php(datestart,crmv_date_format);
                		dateend = js2Php(dateend,crmv_date_format);

                		parent.jQuery('[name="task_subject"]').val(jQuery("#bbit-cal-what").val());
                		parent.jQuery('[name="task_description"]').val(jQuery("#bbit-cal-description").val());
                		parent.jQuery('[name="task_date_start"]').val(datestart);
                		parent.jQuery('[name="task_due_date"]').val(dateend);
                		//crmv@26807
                		parent.jQuery('[name="task_parent_id"]').val(jQuery("#parent_id_link").val());
                		parent.jQuery('[name="task_contact_id"]').val(jQuery("#parent_id_link_singleContact").val());
                		//crmv@26807e

                		parent.jQuery('[name="createTodo"]').submit();

                		$("#bbit-cal-buddle").css("visibility", "hidden");
                		realsedragevent();
                	}
                	//crmv@26030m e
                });
                // crmv@98866
                /*lbtn.click(function(e) {
                	//crmv@17001
                	
                    if (!option.EditCmdhandler) {
                        alert("EditCmdhandler" + i18n.xgcalendar.i_undefined);
                    }
                    else {
                        if (option.EditCmdhandler && $.isFunction(option.EditCmdhandler)) {
                            option.EditCmdhandler.call(this, ['0', $("#bbit-cal-what").val(), $("#bbit-cal-start").val(), $("#bbit-cal-end").val(), $("#bbit-cal-allday").val()]);
                        }
                        $("#bbit-cal-buddle").css("visibility", "hidden");
                        realsedragevent();
                    }
                    return false;

                	var what = $("#bbit-cal-what").val();
                	var sd = new Date($("#bbit-cal-start").val());
                	sddate = js2Php(sd,crmv_date_format);
                    var ed = strtodate($("#bbit-cal-end").val());
                    eddate = js2Php(ed,crmv_date_format);
                    var activitytype = jQuery("#activitytype_show_button").attr('name'); //crmv@26807
                    //crmv@20156
                    var calWhat = jQuery('#bbit-cal-what').val();
                    var calDescription = jQuery('#bbit-cal-description').val();
                    var calLocation = jQuery('#bbit-cal-location').val();
                    //crmv@20156e

                    var allday = $("#bbit-cal-allday").val();
                    //crmv@26030m
                    if (buddle_add_type == 'event') {
                    	parent.window.gshow('addEvent',activitytype,sddate,eddate,sd.getHours(),sd.getMinutes(),'',ed.getHours(),ed.getMinutes(),'','hourview','event','all',allday,calWhat,calDescription,calLocation);//crmv@20156
                    	placeAtCenter(parent.window.document.getElementById('addEvent'));
                    	parent.window.document.getElementById('addEvent').style.top = findPosY(parent.window.document.getElementById('wdCalendar')) + "px";
	                }
                	else if (buddle_add_type == 'task') {
                		parent.window.gshow('createTodo','todo',sddate,eddate,sd.getHours(),sd.getMinutes(),'',ed.getHours(),ed.getMinutes(),'','hourview','event','all',allday,calWhat,calDescription,calLocation);//crmv@20156
                    	placeAtCenter(parent.window.document.getElementById('createTodo'));
                    	parent.window.document.getElementById('createTodo').style.top = findPosY(parent.window.document.getElementById('wdCalendar')) + "px";
                	}
                	//crmv@26030m e

                	$("#bbit-cal-buddle").css("visibility", "hidden");
                    realsedragevent();
                    //crmv@17001e
                });*/
                // crmv@98866 end
                //crmv@26935
                buddle.mousedown(function(e) {
                	return false
                });
                //crmv@26935e
            }

            var dateshow = CalDateShow(start, end, !isallday, true);
            var off = getbuddlepos(pos.left, pos.top);
            if (off.hide) {
                $("#prong2").hide()
            }
            else {
                $("#prong2").show()
            }
            $("#bbit-cal-buddle-timeshow").html(dateshow);
            var calwhat = $("#bbit-cal-what").val("");
            $("#bbit-cal-allday").val(isallday ? "1" : "0");
            $("#bbit-cal-start").val(dateFormat.call(start, i18n.xgcalendar.dateformat.fulldayvalue + " HH:mm"));
            $("#bbit-cal-end").val(dateFormat.call(end, i18n.xgcalendar.dateformat.fulldayvalue + " HH:mm"));

            //crmv@26807
            if (off.left > (jQuery(document).width() - 560)) {
            	off_left = jQuery(document).width() - 560;
            }
            else {
            	off_left = off.left;
            }

            //crmv@26921	//crmv@OPER6317
            if (jQuery('#dvtec').height() < 425) {
            	off_top = '40px';
            }
            else {
            	off_top = '40px';
            }
            //crmv@OPER6317e
            //crmv@62447
            if(parent.jQuery('#from_related').val()=='1'){

            	parent.jQuery('#done_button').removeClass('disabled');
            	parent.jQuery('#more_button').removeClass('disabled');
            	parent.jQuery('#more_button').removeClass('nextButtonDisSave');
            	parent.jQuery('#done_button').addClass('save');
            	parent.jQuery('#more_button').addClass('save');
            	parent.jQuery('#more_button').addClass('nextButtonSave');
            	parent.jQuery('#done_button').prop('disabled', false);
            	parent.jQuery('#more_button').prop('disabled', false);
        		
        		return false;
            }else{
            	// crmv@98866
            	//buddle.css({ "visibility": "visible", left: off_left, top: off_top });	//crmv@vte10usersFix
            	eventQuickEditView({
            		forceLoad: true,
            		create: true
            	});
        		
        		realsedragevent();
        		// crmv@98866 end
            }
            //crmv@62447e
            //crmv@26921e

            //crmv@26807e

			calwhat.blur().focus(); //add 2010-01-26 blur() fixed chrome
			//crmv@17001
			// crmv@98866
			/*$('input[id^="bbit-cal-"]').each(function() {
				$(this).click(function() {
					$(this).focus();
				});
 			});*/
			// crmv@98866 end
 			/*	crmv@29190
            $(document).one("mousedown", function() {
				$("#bbit-cal-buddle").css("visibility", "hidden");
				realsedragevent();
            });
            crmv@29190 e */
            //crmv@30356
            if(isMobile()){
            	jQuery('#bbit-cal-editLink').click();

            }
            //crmv@30356e
            return false;
        }
        //format datestring to Date Type
        function strtodate(str) {

            var arr = str.split(" ");
            var arr2 = arr[0].split(i18n.xgcalendar.dateformat.separator);
            var arr3 = arr[1].split(":");

            var y = arr2[i18n.xgcalendar.dateformat.year_index];
            var m = arr2[i18n.xgcalendar.dateformat.month_index].indexOf("0") == 0 ? arr2[i18n.xgcalendar.dateformat.month_index].substr(1, 1) : arr2[i18n.xgcalendar.dateformat.month_index];
            var d = arr2[i18n.xgcalendar.dateformat.day_index].indexOf("0") == 0 ? arr2[i18n.xgcalendar.dateformat.day_index].substr(1, 1) : arr2[i18n.xgcalendar.dateformat.day_index];
            var h = arr3[0].indexOf("0") == 0 ? arr3[0].substr(1, 1) : arr3[0];
            var n = arr3[1].indexOf("0") == 0 ? arr3[1].substr(1, 1) : arr3[1];
            return new Date(y, parseInt(m) - 1, d, h, n);
        }

        function rebyKey(key, remove) {
            if (option.eventItems && option.eventItems.length > 0) {
                var sl = option.eventItems.length;
                var i = -1;
                for (var j = 0; j < sl; j++) {
                    if (option.eventItems[j][0] == key) {
                        i = j;
                        break;
                    }
                }
                if (i >= 0) {
                    var t = option.eventItems[i];
                    if (remove) {
                        option.eventItems.splice(i, 1);
                    }
                    return t;
                }
            }
            return null;
        }
        function Ind(event, i) {
            var d = 0;
            if (!i) {
                if (option.eventItems && option.eventItems.length > 0) {
                    var sl = option.eventItems.length;
                    var s = event[2];
                    var d1 = s.getTime() - option.eventItems[0][2].getTime();
                    var d2 = option.eventItems[sl - 1][2].getTime() - s.getTime();
                    var diff = d1 - d2;
                    if (d1 < 0 || diff < 0) {
                        for (var j = 0; j < sl; j++) {
                            if (option.eventItems[j][2] >= s) {
                                i = j;
                                break;
                            }
                        }
                    }
                    else if (d2 < 0) {
                        i = sl;
                    }
                    else {
                        for (var j = sl - 1; j >= 0; j--) {
                            if (option.eventItems[j][2] < s) {
                                i = j + 1;
                                break;
                            }
                        }
                    }
                }
                else {
                    i = 0;
                }
            }
            else {
                d = 1;
            }
            if (option.eventItems && option.eventItems.length > 0) {
                if (i == option.eventItems.length) {
                    option.eventItems.push(event);
                }
                else { option.eventItems.splice(i, d, event); }
            }
            else {
                option.eventItems = [event];
            }
            return i;
        }


        function ResizeView() {
            var _MH = document.documentElement.clientHeight;
            var _viewType = option.view;
            if (_viewType == "day" || _viewType == "week") {
                var $dvwkcontaienr = $("#dvwkcontaienr");
                var $dvtec = $("#dvtec");
                if ($dvwkcontaienr.length == 0 || $dvtec.length == 0) {
                    alert(i18n.xgcalendar.view_no_ready); return;
                }
                var dvwkH = $dvwkcontaienr.height() + 2;
                var calH = document.documentElement.clientHeight - 8 - dvwkH; //crmv@21996

                //crmv@vte10usersFix
                var openAllDay = jQuery("#openAllDay").val();
                if (jQuery("#open_dvtec").val() != '') {
                	if (openAllDay == 'true') {
	                	calH = parseInt(jQuery("#open_dvtec").val());
	                	jQuery("#weekViewAllDaywk_container").height(parseInt(jQuery("#open_weekViewAllDaywk_container").val()));
                	}
                	else {
	                	calH = parseInt(jQuery("#open_weekViewAllDaywk_container").val());
	                	jQuery("#weekViewAllDaywk_container").height(parseInt(jQuery("#open_dvtec").val()));
                	}
                }
                $dvtec.height(calH);
                //crmv@vte10usersFix e

                if (typeof (option.scoll) == "undefined") {
                    var currentday = new Date();
                    var h = currentday.getHours();
                    var m = currentday.getMinutes();
                    var th = gP(h, m);
                    var ch = $dvtec.attr("clientHeight");
                    var sh = th - 0.5 * ch;
                    var ph = $dvtec.attr("scrollHeight");
                    if (sh < 0) sh = 0;
                    if (sh > ph - ch) sh = ph - ch - 10 * (23 - h);
                    $dvtec.attr("scrollTop", sh);
                }
                else {
                    $dvtec.attr("scrollTop", option.scoll);
                }
            }
            else if (_viewType == "month") {
                //Resize GridContainer
            }
        }
        function returnfalse() {
            return false;
        }
        function initevents(viewtype) {
            if (viewtype == "week" || viewtype == "day") {
                $("div.chip", gridcontainer).each(function(i) {
                    var chip = $(this);
                    chip.click(function() {
                    	var data = getdata($(this));
                    	eventQuickEditView(data);
                    });
                    if (chip.hasClass("drag")) {
                        chip.mousedown(function(e) { dragStart.call(this, "dw3", e); return false; });
                        //resize
                        chip.find("div.resizer").mousedown(function(e) {
                            dragStart.call($(this).parent().parent(), "dw4", e); return false;
                        });
                    }
                    else {
                        chip.mousedown(returnfalse)
                    }
                });
                $("div.rb-o", gridcontainer).each(function(i) {
                    var chip = $(this);
                    chip.click(function() {
                    	var data = getdata($(this));
                    	eventQuickEditView(data);
                    });
                    if (chip.hasClass("drag") && viewtype == "week") {
                        //drag;
                        chip.mousedown(function(e) { dragStart.call(this, "dw5", e); return false; });
                    }
                    else {
                        chip.mousedown(returnfalse)
                    }
                });
                if (option.readonly == false) {
                    $("td.tg-col", gridcontainer).each(function(i) {
                        $(this).mousedown(function(e) { dragStart.call(this, "dw1", e); return false; });
                    });
                    $("#weekViewAllDaywk").mousedown(function(e) { dragStart.call(this, "dw2", e); return false; });
                }

                if (viewtype == "week") {
                    $("#dvwkcontaienr th.gcweekname").each(function(i) {
                        $(this).click(weekormonthtoday);
                    });
                }


            }
            else if (viewtype = "month") {
                $("div.rb-o", gridcontainer).each(function(i) {
                    var chip = $(this);
                    chip.click(function() {
                    	var data = getdata($(this));
                    	eventQuickEditView(data);
                    });
                    if (chip.hasClass("drag")) {
                        //drag;
                        chip.mousedown(function(e) { dragStart.call(this, "m2", e); return false; });
                    }
                    else {
                        chip.mousedown(returnfalse)
                    }
                });
                $("td.st-more", gridcontainer).each(function(i) {

                    $(this).click(function(e) {
                        moreshow.call(this, $(this).parent().parent().parent().parent()[0]); return false;
                    }).mousedown(function() { return false; });
                });
                if (option.readonly == false) {
                    $("#mvEventContainer").mousedown(function(e) { dragStart.call(this, "m1", e); return false; });
                }
            }

        }
        function realsedragevent() {
            if (_dragevent) {
                _dragevent();
                _dragevent = null;
                //crmv@26807	//crmv@29190
                resetSearch('bbit-cal-txtSearch');
                jQuery('#selectedTable').html('');
				enableReferenceField(jQuery('#selectparent_link'));jQuery('#selectparent_link').val(empty_search_str);
				jQuery('#parent_id_link').val('');
				enableReferenceField(jQuery('#selectparent_link_singleContact'));jQuery('#selectparent_link_singleContact').val(empty_search_str);
				jQuery('#parent_id_link_singleContact').val('');
				jQuery('#contact_id').val('');
				jQuery('#contacts_div table').html('');
				jQuery('#multi_contact_autocomplete_wd').val(empty_search_str);
	            //crmv@26807e	//crmv@29190e
            }
        }
        function dragStart(type, e) {
            var obj = $(this);
            var source = e.srcElement || e.target;
            realsedragevent();
            switch (type) {
                case "dw1":
					if (option.readonly || !option.enableQuickCreate) return; // crmv@68357
                    _dragdata = { type: 1, target: obj, sx: e.pageX, sy: e.pageY };
                    break;
                case "dw2":
                    var w = obj.width();
                    var h = obj.height();
                    var offset = obj.offset();
                    var left = offset.left;
                    var top = offset.top;

                    //crmv@vte10usersFix
                    var l = jQuery('.wk-daylink').length; //option.view == "day" ? 1 : 7;
                    if (l == 0) {
                    	l = 1;
                    }
                    var py = w % l;
                    var pw = parseInt(w / l);
                    if (py > l / 2 + 1) {
                        pw++;
                    }
                    var xa = [];
                    var ya = [];
                    var pwW = 0;
                    var pwL = 0;
                    for (var i = 0; i < l; i++) {
                    	if (l == 7) {
	                    	if (i < 5) {
	                    		pwW = parseInt(0.16 * w);
	                    		xa.push({ s: i * pwW + left, e: (i + 1) * pwW + left });
	                        }
	                    	else {
	                    		pwL = parseInt(0.16 * w);
	                    		pwW = parseInt(0.10 * w);
	                    		xa.push({ s: 5 * pwL + (i-5) * pwW + left, e: 5 * pwL + ((i-5) + 1) * pwW + left });
	                    	}
                    	}
                    	else {
                    		xa.push({ s: i * pw + left, e: (i + 1) * pw + left });
                    	}
                    }
                    ya.push({ s: top, e: top + h });
                    //crmv@vte10usersFix e

                    _dragdata = { type: 2, target: obj, sx: e.pageX, sy: e.pageY, pw: pw, xa: xa, ya: ya, h: h };
                    w = left = l = py = pw = xa = null;
                    break;
                case "dw3":
                    var evid = obj.parent().attr("id").replace("tgCol", "");
                    var p = obj.parent();
                    var pos = p.offset();
                    var w = p.width() + 10;
                    var h = obj.height();
                    var data = getdata(obj);
                    _dragdata = { type: 4, target: obj, sx: e.pageX, sy: e.pageY,
                        pXMin: pos.left, pXMax: pos.left + w, pw: w, h: h,
                        cdi: parseInt(evid), fdi: parseInt(evid), data: data
                    };
                    break;
                case "dw4": //resize;
                    var h = obj.height();
                    var data = getdata(obj);
                    _dragdata = { type: 5, target: obj, sx: e.pageX, sy: e.pageY, h: h, data: data };
                    break;
                case "dw5":
                    var con = $("#weekViewAllDaywk");
                    var w = con.width();
                    var h = con.height();
                    var offset = con.offset();
                    var moffset = obj.offset();
                    var left = offset.left;
                    var top = offset.top;
                    //crmv@diego
                    if (jQuery('.wk-daylink').length == 6) { //6 giorni in week view
                    	var l = 6;
                    }
                    else {
                    	var l = 7;
                    }
                    //crmv@diego e
                    var py = w % l;
                    var pw = parseInt(w / l);
                    if (py > l / 2 + 1) {
                        pw++;
                    }
                    var xa = [];
                    var ya = [];
                    var di = 0;
                    for (var i = 0; i < l; i++) {
                        xa.push({ s: i * pw + left, e: (i + 1) * pw + left });
                        if (moffset.left >= xa[i].s && moffset.left < xa[i].e) {
                            di = i;
                        }
                    }
                    var fdi = { x: di, y: 0, di: di };
                    ya.push({ s: top, e: top + h });
                    var data = getdata(obj);
                    var dp = DateDiff("d", data[2], data[3]) + 1;
                    _dragdata = { type: 6, target: obj, sx: e.pageX, sy: e.pageY, data: data, xa: xa, ya: ya, fdi: fdi, h: h, dp: dp, pw: pw };
                    break;
                case "m1":
                    var w = obj.width();
                    var offset = obj.offset();
                    var left = offset.left;
                    var top = offset.top;
                    var l = 7;
                    var yl = obj.children().length;
                    var py = w % l;
                    var pw = parseInt(w / l);
                    if (py > l / 2 + 1) {
                        pw++;
                    }
                    var h = $("#mvrow_0").height();
                    var xa = [];
                    var ya = [];
                    for (var i = 0; i < l; i++) {
                        xa.push({ s: i * pw + left, e: (i + 1) * pw + left });
                    }
                    var xa = [];
                    var ya = [];
                    for (var i = 0; i < l; i++) {
                        xa.push({ s: i * pw + left, e: (i + 1) * pw + left });
                    }
                    for (var i = 0; i < yl; i++) {
                        ya.push({ s: i * h + top, e: (i + 1) * h + top });
                    }
                    _dragdata = { type: 3, target: obj, sx: e.pageX, sy: e.pageY, pw: pw, xa: xa, ya: ya, h: h };
                    break;
                case "m2":
                    var row0 = $("#mvrow_0");
                    var row1 = $("#mvrow_1");
                    var w = row0.width();
                    var offset = row0.offset();
                    var diffset = row1.offset();
                    var moffset = obj.offset();
                    var h = diffset.top - offset.top;
                    var left = offset.left;
                    var top = offset.top;
                    var l = 7;
                    var yl = row0.parent().children().length;
                    var py = w % l;
                    var pw = parseInt(w / l);
                    if (py > l / 2 + 1) {
                        pw++;
                    }
                    var xa = [];
                    var ya = [];
                    var xi = 0;
                    var yi = 0;
                    for (var i = 0; i < l; i++) {
                        xa.push({ s: i * pw + left, e: (i + 1) * pw + left });
                        if (moffset.left >= xa[i].s && moffset.left < xa[i].e) {
                            xi = i;
                        }
                    }
                    for (var i = 0; i < yl; i++) {
                        ya.push({ s: i * h + top, e: (i + 1) * h + top });
                        if (moffset.top >= ya[i].s && moffset.top < ya[i].e) {
                            yi = i;
                        }
                    }
                    var fdi = { x: xi, y: yi, di: yi * 7 + xi };
                    var data = getdata(obj);
                    var dp = DateDiff("d", data[2], data[3]) + 1;
                    _dragdata = { type: 7, target: obj, sx: e.pageX, sy: e.pageY, data: data, xa: xa, ya: ya, fdi: fdi, h: h, dp: dp, pw: pw };
                    break;
            }
            $('body').noSelect();
        }
        function dragMove(e) {
            if (_dragdata) {
                if (e.pageX < 0 || e.pageY < 0
					|| e.pageX > document.documentElement.clientWidth
					|| e.pageY >= document.documentElement.clientHeight) {
                    dragEnd(e);
                    return false;
                }
                var d = _dragdata;
                switch (d.type) {
                    case 1:
                        var sy = d.sy;
                        var y = e.pageY;
                        var diffy = y - sy;
                        if (diffy > 11 || diffy < -11 || d.cpwrap) {
                            if (diffy == 0) { diffy = 21; }
                            var dy = diffy % 21;
                            if (dy != 0) {
                                diffy = dy > 0 ? diffy + 21 - dy : diffy - 21 - dy;
                                y = d.sy + diffy;
                                if (diffy < 0) {
                                    sy = sy + 21;
                                }
                            }
                            if (!d.tp) {
                                d.tp = $(d.target).offset().top;
                            }
                            var gh = gH(sy, y, d.tp);
                            var ny = gP(gh.sh, gh.sm);
                            var tempdata;
                            if (!d.cpwrap) {
                                tempdata = buildtempdayevent(gh.sh, gh.sm, gh.eh, gh.em, gh.h);
                                var cpwrap = $("<div class='ca-evpi drag-chip-wrapper' style='top:" + ny + "px'/>").html(tempdata);
                                $(d.target).find("div.tg-col-overlaywrapper").append(cpwrap);
                                d.cpwrap = cpwrap;
                            }
                            else {
                                if (d.cgh.sh != gh.sh || d.cgh.eh != gh.eh || d.cgh.sm != gh.sm || d.cgh.em != gh.em) {
                                    tempdata = buildtempdayevent(gh.sh, gh.sm, gh.eh, gh.em, gh.h);
                                    d.cpwrap.css("top", ny + "px").html(tempdata);
                                }
                            }
                            d.cgh = gh;
                        }
                        break;
                    case 2:
                        var sx = d.sx;
                        var x = e.pageX;
                        var diffx = x - sx;
                        if (diffx > 5 || diffx < -5 || d.lasso) {
                            if (!d.lasso) {
                                d.lasso = $("<div style='z-index: 10; display: block' class='drag-lasso-container'/>");
                                $(document.body).append(d.lasso);
                            }
                            if (!d.sdi) {
                                d.sdi = getdi(d.xa, d.ya, sx, d.sy);
                            }
                            var ndi = getdi(d.xa, d.ya, x, e.pageY);
                            if (!d.fdi || d.fdi.di != ndi.di) {
                                addlasso(d.lasso, d.sdi, ndi, d.xa, d.ya, d.h);
                            }
                            d.fdi = ndi;
                        }
                        break;
                    case 3:
                        var sx = d.sx;
                        var x = e.pageX;
                        var sy = d.sy;
                        var y = e.pageY;
                        var diffx = x - sx;
                        var diffy = y - sy;
                        if (diffx > 5 || diffx < -5 || diffy < -5 || diffy > 5 || d.lasso) {
                            if (!d.lasso) {
                                d.lasso = $("<div style='z-index: 10; display: block' class='drag-lasso-container'/>");
                                $(document.body).append(d.lasso);
                            }
                            if (!d.sdi) {
                                d.sdi = getdi(d.xa, d.ya, sx, sy);
                            }
                            var ndi = getdi(d.xa, d.ya, x, y);
                            if (!d.fdi || d.fdi.di != ndi.di) {
                                addlasso(d.lasso, d.sdi, ndi, d.xa, d.ya, d.h);
                            }
                            d.fdi = ndi;
                        }
                        break;
                    case 4:
                        var data = d.data;
                        if (data != null && data[8] == 1) {
                            var sx = d.sx;
                            var x = e.pageX;
                            var sy = d.sy;
                            var y = e.pageY;
                            var diffx = x - sx;
                            var diffy = y - sy;
                            if (diffx > 5 || diffx < -5 || diffy > 5 || diffy < -5 || d.cpwrap) {
                                var gh, ny, tempdata;
                                if (!d.cpwrap) {
                                    gh = { sh: data[2].getHours(),
                                        sm: data[2].getMinutes(),
                                        eh: data[3].getHours(),
                                        em: data[3].getMinutes(),
                                        h: d.h
                                    };
                                    d.target.hide();
                                    ny = gP(gh.sh, gh.sm);
                                    d.top = ny;
                                    tempdata = buildtempdayevent(gh.sh, gh.sm, gh.eh, gh.em, gh.h, data[1], false, false, data[7]);
                                    var cpwrap = $("<div class='ca-evpi drag-chip-wrapper' style='top:" + ny + "px'/>").html(tempdata);
                                    var evid = d.target.parent().attr("id").replace("tgCol", "#tgOver");
                                    $(evid).append(cpwrap);
                                    d.cpwrap = cpwrap;
                                    d.ny = ny;
                                }
                                else {
                                    var pd = 0;
                                    if (x < d.pXMin) {
                                        pd = -1;
                                    }
                                    else if (x > d.pXMax) {
                                        pd = 1;
                                    }
                                    if (pd != 0) {

                                        d.cdi = d.cdi + pd;
                                        var ov = $("#tgOver" + d.cdi);
                                        if (ov.length == 1) {
                                            d.pXMin = d.pXMin + d.pw * pd;
                                            d.pXMax = d.pXMax + d.pw * pd;
                                            ov.append(d.cpwrap);
                                        }
                                        else {
                                            d.cdi = d.cdi - pd;
                                        }
                                    }
                                    ny = d.top + diffy;
                                    var pny = ny % 21;
                                    if (pny != 0) {
                                        ny = ny - pny;
                                    }
                                    if (d.ny != ny) {
                                        gh = gW(ny, ny + d.h);
                                        //crmv@44905
			                            var durationMinutes = DateDiff("n", d.data[2], d.data[3]);
										var endMinsTmp = durationMinutes + gh.sm + ( gh.sh * 60 );	// end time in minutes
										gh.eh = parseInt( endMinsTmp / 60 ); 
										gh.em = endMinsTmp % 60;
										//crmv@44905e
                                        tempdata = buildtempdayevent(gh.sh, gh.sm, gh.eh, gh.em, gh.h, data[1], false, false, data[7]);
                                        d.cpwrap.css("top", ny + "px").html(tempdata);
                                    }
                                    d.ny = ny;
                                }
                            }
                        }

                        break;
                    case 5:
                        var data = d.data;
                        if (data != null && data[8] == 1) {
                            var sy = d.sy;
                            var y = e.pageY;
                            var diffy = y - sy;
                            if (diffy != 0 || d.cpwrap) {
                                var gh, ny, tempdata;
                                if (!d.cpwrap) {
                                    gh = { sh: data[2].getHours(),
                                        sm: data[2].getMinutes(),
                                        eh: data[3].getHours(),
                                        em: data[3].getMinutes(),
                                        h: d.h
                                    };
                                    d.target.hide();
                                    ny = gP(gh.sh, gh.sm);
                                    d.top = ny;
                                    tempdata = buildtempdayevent(gh.sh, gh.sm, gh.eh, gh.em, gh.h, data[1], "100%", true, data[7]);
                                    var cpwrap = $("<div class='ca-evpi drag-chip-wrapper' style='top:" + ny + "px'/>").html(tempdata);
                                    var evid = d.target.parent().attr("id").replace("tgCol", "#tgOver");
                                    $(evid).append(cpwrap);
                                    d.cpwrap = cpwrap;
                                }
                                else {
                                    nh = d.h + diffy;
                                    var pnh = nh % 21;
                                    nh = pnh > 1 ? nh - pnh + 21 : nh - pnh;
                                    if (d.nh != nh) {
                                        var sp = gP(data[2].getHours(), data[2].getMinutes());
                                        var ep = sp + nh;
                                        gh = gW(d.top, d.top + nh);
                                        tempdata = buildtempdayevent(gh.sh, gh.sm, gh.eh, gh.em, gh.h, data[1], "100%", true, data[7]);
                                        d.cpwrap.html(tempdata);
                                    }
                                    d.nh = nh;
                                }
                            }
                        }
                        break;
                    case 6:
                        var sx = d.sx;
                        var x = e.pageX;
                        var y = e.pageY;
                        var diffx = x - sx;
                        if (diffx > 5 || diffx < -5 || d.lasso) {
                            if (!d.lasso) {
                                var w1 = d.dp > 1 ? (d.pw - 4) * 1.5 : (d.pw - 4);
                                var cp = d.target.clone();
                                if (d.dp > 1) {
                                    cp.find("div.rb-i>span").prepend("(" + d.dp + " " + i18n.xgcalendar.day_plural + ")&nbsp;");
                                }
                                var cpwrap = $("<div class='drag-event st-contents' style='width:" + 120 + "px'/>").append(cp).appendTo(document.body);
                                d.cpwrap = cpwrap;
                                d.lasso = $("<div style='z-index: 10; display: block' class='drag-lasso-container'/>");
                                $(document.body).append(d.lasso);
                                cp = cpwrap = null;
                            }
                            fixcppostion(d.cpwrap, e, d.xa, d.ya);
                            var ndi = getdi(d.xa, d.ya, x, e.pageY);
                            if (!d.cdi || d.cdi.di != ndi.di) {
                                addlasso(d.lasso, ndi, { x: ndi.x, y: ndi.y, di: ndi.di + d.dp - 1 }, d.xa, d.ya, d.h);
                            }
                            d.cdi = ndi;
                        }
                        break;
                    case 7:
                        var sx = d.sx;
                        var sy = d.sy;
                        var x = e.pageX;
                        var y = e.pageY;
                        var diffx = x - sx;
                        var diffy = y - sy;
                        if (diffx > 5 || diffx < -5 || diffy > 5 || diffy < -5 || d.lasso) {
                            if (!d.lasso) {
                                var w1 = d.dp > 1 ? (d.pw - 4) * 1.5 : (d.pw - 4);
                                var cp = d.target.clone();
                                if (d.dp > 1) {
                                    cp.find("div.rb-i>span").prepend("(" + d.dp + " " + i18n.xgcalendar.day_plural + ")&nbsp;");
                                }
                                var cpwrap = $("<div class='drag-event st-contents' style='width:" + w1 + "px'/>").append(cp).appendTo(document.body);
                                d.cpwrap = cpwrap;
                                d.lasso = $("<div style='z-index: 10; display: block' class='drag-lasso-container'/>");
                                $(document.body).append(d.lasso);
                                cp = cpwrap = null;
                            }
                            fixcppostion(d.cpwrap, e, d.xa, d.ya);
                            var ndi = getdi(d.xa, d.ya, x, e.pageY);
                            if (!d.cdi || d.cdi.di != ndi.di) {
                                addlasso(d.lasso, ndi, { x: ndi.x, y: ndi.y, di: ndi.di + d.dp - 1 }, d.xa, d.ya, d.h);
                            }
                            d.cdi = ndi;
                        }
                        break;
                }
            }
            return false;
        }
        function dragEnd(e) {
            if (_dragdata) {
                var d = _dragdata;
                switch (d.type) {
                    case 1: //day view
                        var wrapid = new Date().getTime();
                        tp = d.target.offset().top;
                        if (!d.cpwrap) {
                            var gh = gH(d.sy, d.sy + 42, tp);
                            var ny = gP(gh.sh, gh.sm);
                            var tempdata = buildtempdayevent(gh.sh, gh.sm, gh.eh, gh.em, gh.h);
                            d.cpwrap = $("<div class='ca-evpi drag-chip-wrapper' style='top:" + ny + "px'/>").html(tempdata);
                            $(d.target).find("div.tg-col-overlaywrapper").append(d.cpwrap);
                            d.cgh = gh;
                        }
                        var pos = d.cpwrap.offset();
                        pos.left = pos.left + 30;
                        d.cpwrap.attr("id", wrapid);
                        var start = strtodate(d.target.attr("abbr") + " " + d.cgh.sh + ":" + d.cgh.sm);
                        var end = strtodate(d.target.attr("abbr") + " " + d.cgh.eh + ":" + d.cgh.em);
                        _dragevent = function() { $("#" + wrapid).remove(); $("#bbit-cal-buddle").css("visibility", "hidden"); };
                       	//crmv@17001 : StartHour
                       	//quickadd(start, end, false, pos);
                       	quickadd(start, end, false, { left: e.pageX, top: e.pageY });
                       	//crmv@17001 : StartHour e
                        break;
                    case 2: //week view
                    case 3: //month view
                        var source = e.srcElement || e.target;
                        var lassoid = new Date().getTime();
                        if (!d.lasso) {
							 if ($(source).hasClass("monthdayshow"))
							{
								weekormonthtoday.call($(source).parent()[0],e);
								break;
							}
                            d.fdi = d.sdi = getdi(d.xa, d.ya, d.sx, d.sy);
                            d.lasso = $("<div style='z-index: 10; display: block' class='drag-lasso-container'/>");
                            $(document.body).append(d.lasso);
                            addlasso(d.lasso, d.sdi, d.fdi, d.xa, d.ya, d.h);
                        }
                        d.lasso.attr("id", lassoid);
                        var si = Math.min(d.fdi.di, d.sdi.di);
                        var ei = Math.max(d.fdi.di, d.sdi.di);
                        var firstday = option.vstart;
                        var start = DateAdd("d", si, firstday);
                        var end = DateAdd("d", ei, firstday);
                        _dragevent = function() { $("#" + lassoid).remove(); };
                       	quickadd(start, end, true, { left: e.pageX, top: e.pageY });
                        break;
                    case 4: // event moving
                        if (d.cpwrap) {
                            var start = DateAdd("d", d.cdi, option.vstart);
                            var end = DateAdd("d", d.cdi, option.vstart);
                            var gh = gW(d.ny, d.ny + d.h);
                            start.setHours(gh.sh, gh.sm);
                            //crmv@44905
                            //end.setHours(gh.eh, gh.em);
                            var durationMinutes = DateDiff("n", d.data[2], d.data[3]);
							var durH = parseInt( durationMinutes / 60 ); 
							var durM = durationMinutes % 60; 
                            end.setHours(gh.sh + durH, gh.sm + durM);
							//crmv@44905e
                            if (start.getTime() == d.data[2].getTime() && end.getTime() == d.data[3].getTime()) {
                                d.cpwrap.remove();
                                d.target.show();
                            }
                            else {
                                dayupdate(d.data, start, end);
                            }
                        }
                        break;
                    case 5: //Resize
                        if (d.cpwrap) {
                            var start = new Date(d.data[2].toString());
                            var end = new Date(d.data[3].toString());
                            var gh = gW(d.top, d.top + nh);
                            start.setHours(gh.sh, gh.sm);
                            end.setHours(gh.eh, gh.em);

                            if (start.getTime() == d.data[2].getTime() && end.getTime() == d.data[3].getTime()) {
                                d.cpwrap.remove();
                                d.target.show();
                            }
                            else {
                                dayupdate(d.data, start, end);
                            }
                        }
                        break;
                    case 6:
                    case 7:
                        if (d.lasso) {
                            d.cpwrap.remove();
                            d.lasso.remove();
                            var start = new Date(d.data[2].toString());
                            var end = new Date(d.data[3].toString());
                            var currrentdate = DateAdd("d", d.cdi.di, option.vstart);
                            var diff = DateDiff("d", start, currrentdate);
                            start = DateAdd("d", diff, start);
                            end = DateAdd("d", diff, end);
                            if (start.getTime() != d.data[2].getTime() || end.getTime() != d.data[3].getTime()) {
                                dayupdate(d.data, start, end);
                            }
                        }
                        break;
                }
                d = _dragdata = null;
                $('body').noSelect(false);
                return false;
            }
        }
        function getdi(xa, ya, x, y) {
            var ty = 0;
            var tx = 0;
            var lx = 0;
            var ly = 0;
            if (xa && xa.length != 0) {
                lx = xa.length;
                if (x >= xa[lx - 1].e) {
                    tx = lx - 1;
                }
                else {
                    for (var i = 0; i < lx; i++) {
                        if (x > xa[i].s && x <= xa[i].e) {
                            tx = i;
                            break;
                        }
                    }
                }
            }
            if (ya && ya.length != 0) {
                ly = ya.length;
                if (y >= ya[ly - 1].e) {
                    ty = ly - 1;
                }
                else {
                    for (var j = 0; j < ly; j++) {
                        if (y > ya[j].s && y <= ya[j].e) {
                            ty = j;
                            break;
                        }
                    }
                }
            }
            return { x: tx, y: ty, di: ty * lx + tx };
        }
        function addlasso(lasso, sdi, edi, xa, ya, height) {
            var diff = sdi.di > edi.di ? sdi.di - edi.di : edi.di - sdi.di;
            diff++;
            var sp = sdi.di > edi.di ? edi : sdi;
            var ep = sdi.di > edi.di ? sdi : edi;
            var l = xa.length > 0 ? xa.length : 1;
            var h = ya.length > 0 ? ya.length : 1;
            var play = [];
            var width = xa[0].e - xa[0].s;
            var i = sp.x;
            var j = sp.y;
            var max = Math.min(document.documentElement.clientWidth, xa[l - 1].e) - 2;

            while (j < h && diff > 0) {
                var left = xa[i].s;
                var d = i + diff > l ? l - i : diff;
                var wid = width * d;

                //crmv@vte10usersFix
                //crmv@diego
//                if ((jQuery('.wk-daylink').length == 7) && (ya.length == 1)) { //7 giorni e week view
//                	if (i > 4) { // sab e dom corti
//                		wid = width * 7 * 0.10;
//                		left = xa[0].s + 5 * width * 7 * 0.16 + (i-5) * wid;
//                	}
//                	else {
//                		wid = width * 7 * 0.16;
//                		left = xa[0].s + i * wid;
//                	}
//                }
                //crmv@diego e
                //crmv@vte10usersFix e

                while (left + wid >= max) {
                    wid--;
                }

                height = jQuery('#weekViewAllDaywk_container').height() - 2; //crmv@vte10usersFix

                play.push(Tp(__LASSOTEMP, { left: left, top: ya[j].s, height: height, width: wid }));
                i = 0;
                diff = diff - d;
                j++;
            }
            lasso.html(play.join(""));
        }
        function fixcppostion(cpwrap, e, xa, ya) {
            var x = e.pageX - 6;
            var y = e.pageY - 4;
            var w = cpwrap.width();
            var h = 21;
            var lmin = xa[0].s + 6;
            var tmin = ya[0].s + 4;
            var lmax = xa[xa.length - 1].e - w - 2;
            var tmax = ya[ya.length - 1].e - h - 2;
            if (x > lmax) {
                x = lmax;
            }
            if (x <= lmin) {
                x = lmin + 1;
            }
            if (y <= tmin) {
                y = tmin + 1;
            }
            if (y > tmax) {
                y = tmax;
            }
            cpwrap.css({ left: x, top: y });
        }
        
        // crmv@98866
        function eventQuickEditView(data) {
        	data = data || {};
        	var create = data.create || false;
        	
			parent.showFloatingDiv('addEvent', null, { modal: true }); // crmv@138006
			
        	if (!create) {
	        	var params = {
	        		forceLoad: true,
	        		mode: 'detail',
	        		record: data[0],
	        		data: data
	        	};
	        	
	        	var tab = data[15] == 'Task' ? 'todo-tab' : 'event-tab';
	        	params['disableEvent'] = true;
	        	params['disableTodo'] = true;
        	} else {
        		var params = jQuery.extend(data, {});
        		var tab = 'event-tab';
        	}

        	var hrefTab = 'a[href="#' + tab + '"]';
    		parent.jQuery(hrefTab).trigger('click.tab.data-api', params);
        }
        // crmv@98866e
        
        //crmv@17001
        if (edit_permission == 'yes') {
	        $(document)
			.mousemove(dragMove)
			.mouseup(dragEnd);
	        //.mouseout(dragEnd);
        }
		//crmv@17001e
        var c = {
            sv: function(view) { //switch view
                if (view == option.view) {
                    return;
                }
                clearcontainer();
                option.view = view;
                render();
                dochange();
            },
            rf: function() {
                populate();
            },
            gt: function(d) {
                if (!d) {
                    d = new Date();
                }
                option.showday = d;
                render();
                dochange();
            },

            pv: function() {
                switch (option.view) {
                    case "day":
                        option.showday = DateAdd("d", -1, option.showday);
                        break;
                    case "week":
                        option.showday = DateAdd("w", -1, option.showday);
                        break;
                    case "month":
                        option.showday = DateAdd("m", -1, option.showday);
                        break;
                }
                render();
                dochange();
            },
            nt: function() {
                switch (option.view) {
                    case "day":
                        option.showday = DateAdd("d", 1, option.showday);
                        break;
                    case "week":
                        option.showday = DateAdd("w", 1, option.showday);
                        break;
                    case "month":
						var od = option.showday.getDate();
						option.showday = DateAdd("m", 1, option.showday);
						var nd = option.showday.getDate();
						if(od !=nd) //we go to the next month
						{
							option.showday= DateAdd("d", 0-nd, option.showday); //last day of last month
						}
                        break;
                }
                render();
                dochange();
            },
            go: function() {
                return option;
            },
            so: function(p) {
                option = $.extend(option, p);
            },
			// crmv@104639 crmv@140887
			resizeW: function() {
				ResizeView();
			},
			// crmv@104639e crmv@140887e
        };
        this[0].bcal = c;
        return this;
    };

    /**
     * @description {Method} swtichView To switch to another view.
     * @param {String} view View name, one of 'day', 'week', 'month'.
     */
    $.fn.swtichView = function(view) {
        return this.each(function() {
            if (this.bcal) {
                this.bcal.sv(view);
            }
        })
    };

    /**
     * @description {Method} reload To reload event of current time range.
     */
    $.fn.reload = function() {
        return this.each(function() {
            if (this.bcal) {
                this.bcal.rf();
            }
        })
    };

    /**
     * @description {Method} gotoDate To go to a range containing date.
     * If view is week, it will go to a week containing date.
     * If view is month, it will got to a month containing date.
     * @param {Date} date. Date to go.
     */
    $.fn.gotoDate = function(d) {
        return this.each(function() {
            if (this.bcal) {
                this.bcal.gt(d);
            }
        })
    };

    /**
     * @description {Method} previousRange To go to previous date range.
     * If view is week, it will go to previous week.
     * If view is month, it will got to previous month.
     */
    $.fn.previousRange = function() {
        return this.each(function() {
            if (this.bcal) {
                this.bcal.pv();
            }
        })
    };

    /**
     * @description {Method} nextRange To go to next date range.
     * If view is week, it will go to next week.
     * If view is month, it will got to next month.
     */
    $.fn.nextRange = function() {
        return this.each(function() {
            if (this.bcal) {
                this.bcal.nt();
            }
        })
    };


    $.fn.BcalGetOp = function() {
        if (this[0].bcal) {
            return this[0].bcal.go();
        }
        return null;
    };


    $.fn.BcalSetOp = function(p) {
        if (this[0].bcal) {
            return this[0].bcal.so(p);
        }
    };

    //crmv@17001
    filterAssignedUser = function(val) {
    	//crmv@23542
		//fullcleargridcontainer = true;	//crmv@19218
		//crmv@23542 end
    	//crmv@20211
    	if (val == 'selected') {
			filterAssignedSingleUser('selected');
		}
    	else {
    		if (val == 'mine') {
    			jQuery('[name=singleUser]').each(function(index) {
    				if (jQuery(this).attr('value') == 'mine') {
    					jQuery(this).prop('checked',true);
    				}
    				else {
    					jQuery(this).prop('checked',false);
    				}
    			});
    		}
    		else {
    			jQuery('[name=singleUser]').each(function(index) {
    				if (jQuery(this).attr('value') == val) {
    					jQuery(this).prop('checked',true);
    				}
    				else {
    					jQuery(this).prop('checked',false);
    				}
    			});
    		}
    		other_params["url"] = "&filter_assigned_user_id="+val;
    		jQuery("#gridcontainer").reload();
    	}
    	//crmv@20211e
    }


	js2Php = function(date,format) {
		//crmv@26030m //crmv@36554
		var monthNumber = ("0" + (date.getMonth() + 1)).slice(-2)
		var dayNumber = ("0" + date.getDate()).slice(-2)
		if (format == 'dd-mm-yyyy') {
			return dayNumber+'-'+monthNumber+'-'+date.getFullYear();
		}
		else if (format == 'mm-dd-yyyy') {
			return monthNumber+'-'+dayNumber+'-'+date.getFullYear();
		}
		else if (format == 'yyyy-mm-dd') {
			return date.getFullYear()+'-'+monthNumber+'-'+dayNumber;
		}
		//crmv@26030m e //crmv@36554 e
	}

	reloadGrid = function(view,num_days,user_id) {
		option.view = view;
		option.num_days = num_days;
		other_params["url"] = "&filter_assigned_user_id="+user_id;
		$("#gridcontainer").reload();
    }
    //crmv@17001e

	/* crmv@20211 crmv@23542 crmv@21363 crmv@vte10usersFix crmv@43117 crmv@26030m crmv@57382 */
	filterAssignedSingleUser = function(val) {

		jQuery("#filter_view_Option").val("selected");

		var checkedUsers = '';
		if ((val == 'all') && jQuery('#singleUserAll').is(':checked')) {
			jQuery('#singleUserAll').closest('tr').hide();
			jQuery('#singleUserNone').closest('tr').show();
			//checkedUsers = 'all';
			jQuery('input[name=singleUser]').each(function(index) {
				if (jQuery(this).attr('value') == 'none') {
					jQuery(this).prop('checked',false);
				}
				else {
					jQuery(this).prop('checked',true);
					if ((jQuery(this).attr('value') != 'all') && (jQuery(this).attr('value') != 'others')) {
						if (checkedUsers) {
				    		checkedUsers = checkedUsers + ',' + jQuery(this).attr('value');
				    	}
				    	else {
				    		checkedUsers = jQuery(this).attr('value');
				    	}
					}
				}
			});
		}
		//crmv@73370
		else if ((val == 'others')) {
			if(jQuery('#singleUserOthers').is(':checked')){
				jQuery('input[name=singleUser]').each(function(index) {
					if ((jQuery(this).attr('value') == 'all') || (jQuery(this).attr('value') == 'mine') || (jQuery(this).attr('value') == 'none')) {
						jQuery(this).prop('checked',false);
					}
					else {
						jQuery(this).prop('checked',true);
						if (jQuery(this).attr('value') != 'others') {
							if (checkedUsers) {
					    		checkedUsers = checkedUsers + ',' + jQuery(this).attr('value');
					    	}
					    	else {
					    		checkedUsers = jQuery(this).attr('value');
					    	}
						}
					}
				});
			} else {
				var mineChecked = jQuery('#singleUserMine').prop('checked');
				jQuery('input[name=singleUser]').each(function(index) {
					if ((jQuery(this).attr('value') == 'all')  || (jQuery(this).attr('value') == 'none') || (jQuery(this).attr('value') == 'others')) {
						// do nothing
					}
					else {
						if (jQuery(this).attr('value') != 'mine' || !mineChecked) {
							jQuery(this).prop('checked',false);
     					} else {
      						if (checkedUsers) {
          						checkedUsers = checkedUsers + ',' + jQuery(this).attr('value');
         					}
         					else {
          						checkedUsers = jQuery(this).attr('value');
							}
						}
    				}
				});
				
			}
		}
		//crmv@73370e
		else if ((val == 'none') && jQuery('#singleUserNone').is(':checked')) {
			jQuery('#singleUserAll').closest('tr').show();
			jQuery('#singleUserNone').closest('tr').hide();
			jQuery('input[name=singleUser]').each(function(index) {
				if (jQuery(this).attr('value') != 'none') {
					jQuery(this).prop('checked',false);
				}
			});
			checkedUsers = 'none';
		}
		else {
			if (val != 'selected') {
				jQuery('#singleUserAll').prop('checked',false);
				jQuery('#singleUserOthers').prop('checked',false);
				jQuery('#singleUserNone').prop('checked',false);
			}
			jQuery('input[name=singleUser]').each(function(index) {
			    if (jQuery(this).is(':checked')) {
			    	if (checkedUsers) {
			    		checkedUsers = checkedUsers + ',' + jQuery(this).attr('value');
			    	}
			    	else {
			    		checkedUsers = jQuery(this).attr('value');
			    	}
			    }
			});
		}
		if (checkedUsers.length == 0) {
			checkedUsers = 'none';
		}
		other_params["url"] = "&filter_assigned_user_id="+checkedUsers;
		//alert('|'+checkedUsers+'|');
		jQuery("#gridcontainer").reload();
    }

    //crmv@20629
    updateShownList = function() {
    	checkedUsers = '';
    	jQuery('[name=singleUser]:checked').each(function(){
    		if (checkedUsers) {
	    		checkedUsers = checkedUsers + ',' + jQuery(this).attr('value');
	    	}
	    	else {
	    		checkedUsers = jQuery(this).attr('value');
	    	}
    	});
		// crmv@192033
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: "module=Calendar&action=CalendarAjax&file=wdCalendar&subfile=ajaxRequests/UpdateShownList&checkedUsers="+checkedUsers,
		});
		// crmv@192033e
    }
    reloadShownList = function(val) {
    	if (val == 'selected') {
    		response = getFile("index.php?module=Calendar&action=CalendarAjax&file=wdCalendar&subfile=ajaxRequests/ReloadShownList");
    		jQuery('#filterDivCalendar').html(response);
		}
    }
    //crmv@20629e

    //crmv@vte10usersFix
    completedTask  = function(val) {
    	if (other_params) {
    		if (other_params["task"] && other_params["task"].indexOf('&noCompletedTask=0') > -1) {
    			other_params["task"] = '&noCompletedTask=1';
    			//jQuery('#completedTaskButton').html('mostra');
    		}
    		else {
    			other_params["task"] = '&noCompletedTask=0';
    			//jQuery('#completedTaskButton').html('nascondi');
    		}
    	}
    	jQuery("#gridcontainer").reload();
    }
    //crmv@vte10usersFix e

    //crmv@25392
    isloadingNew = function(val) {
    	if (val) {
    		parent.jQuery("#status").show();
    	}
    	else {
    		parent.jQuery("#status").hide();
    	}
    }
    //crmv@25392 e

    //crmv@26030m
    //crmv@26807
    setEventBubble= function(val) {
    	buddle_add_type = 'event';
    	jQuery('#change_to_event_type').parent().removeClass('dvtUnSelectedCell');
    	jQuery('#change_to_event_type').parent().addClass('dvtSelectedCell');
    	jQuery('#change_to_task_type').parent().removeClass('dvtSelectedCell');
    	jQuery('#change_to_task_type').parent().addClass('dvtUnSelectedCell');
    	jQuery('#buddle_tr_event_type').css('display','');
    	jQuery('#buddle_tr_event_location').css('display','');
    	jQuery('#buddle_tr_invite_search').css('display','');
    	jQuery('#buddle_tr_invite_add').css('display','');
    	jQuery('#buddle_tr_singleContactlink').css('display','none');
    	jQuery('#buddle_tr_contactslink').css('display','');
    	jQuery('#bubble_concent_th').html(i18n.xgcalendar.content + ':');
    }
    setTaskBubble= function(val) {
    	buddle_add_type = 'task';
    	jQuery('#change_to_task_type').parent().removeClass('dvtUnSelectedCell');
    	jQuery('#change_to_task_type').parent().addClass('dvtSelectedCell');
    	jQuery('#change_to_event_type').parent().removeClass('dvtSelectedCell');
    	jQuery('#change_to_event_type').parent().addClass('dvtUnSelectedCell');
    	jQuery('#buddle_tr_event_type').css('display','none');
    	jQuery('#buddle_tr_event_location').css('display','none');
    	jQuery('#buddle_tr_invite_search').css('display','none');
    	jQuery('#buddle_tr_invite_add').css('display','none');
    	jQuery('#buddle_tr_singleContactlink').css('display','');
    	jQuery('#buddle_tr_contactslink').css('display','none');
    	jQuery('#bubble_concent_th').html(i18n.xgcalendar.task_content + ':');
    }
    //crmv@26807e

    resetBubble= function(val) {
    	setEventBubble();
//    	if (val) {
//    		jQuery('#event_task_div').css('display','');
//    	}
//    	else {
//    		jQuery('#event_task_div').css('display','none');
//    	}
    	jQuery("#bbit-cal-description").val('');
        jQuery("#bbit-cal-location").val('');
    }

	eventButton = function(val) {
		setEventBubble();
    }

	taskButton = function(val) {
		setTaskBubble();
	}
    //crmv@26030m e

	//crmv@39473
    GeoCalendar = function(val){

    	var options = jQuery("#gridcontainer").BcalGetOp();
    	var events = eval(options.eventItems);
    	var ids = '';
    	for(i=0;i<events.length;i++){
    		ids += events[i][0] + ';'
    	}
    	//window.open('index.php?module=SDK&action=SDKAjax&file=src/Geolocalization/GeoLocalization&ids='+ids+'&output=embed','_blank');
    	window.open('index.php?module=Geolocalization&action=GeolocalizationAjax&file=ShowMap&ids='+ids+'&output=embed','_blank');

    }
    //crmv@39473e
	
	// crmv@104639 crmv@140887
	ResizeWindow = function() {
		var options = jQuery("#gridcontainer")[0];
		options.bcal.resizeW();
	}
	// crmv@104639e crmv@140887e
    
    // crmv@98866
    strtodate = function(str) {
        var arr = str.split(" ");
        var arr2 = arr[0].split(i18n.xgcalendar.dateformat.separator);
        var arr3 = arr[1].split(":");

        var y = arr2[i18n.xgcalendar.dateformat.year_index];
        var m = arr2[i18n.xgcalendar.dateformat.month_index].indexOf("0") == 0 ? arr2[i18n.xgcalendar.dateformat.month_index].substr(1, 1) : arr2[i18n.xgcalendar.dateformat.month_index];
        var d = arr2[i18n.xgcalendar.dateformat.day_index].indexOf("0") == 0 ? arr2[i18n.xgcalendar.dateformat.day_index].substr(1, 1) : arr2[i18n.xgcalendar.dateformat.day_index];
        var h = arr3[0].indexOf("0") == 0 ? arr3[0].substr(1, 1) : arr3[0];
        var n = arr3[1].indexOf("0") == 0 ? arr3[1].substr(1, 1) : arr3[1];
        return new Date(y, parseInt(m) - 1, d, h, n);
    }
    // crmv@98866 end

})(jQuery);

//crmv@201468
function setResourcesTodayColumn() {
    jQuery('.event-slot-date').each(function () {
        var date = jQuery(this).text().trim();
        var dateArray = date.split("-",3);
        var headerDate = new Date();
        headerDate.setDate(parseInt(dateArray[0]));
        headerDate.setMonth(parseInt(dateArray[1]) - 1);
        headerDate.setFullYear(parseInt(dateArray[2]));
        var nowDate = new Date();
        headerDate.setHours(0,0,0,0);
        nowDate.setHours(0,0,0,0);

        if (headerDate.getTime() === nowDate.getTime()) {
            jQuery('#calendarResourcesTable .column-' + getShorthandDayOfTheWeek(headerDate)).addClass('tg-today');
        }
    });
}

function getShorthandDayOfTheWeek(date) {
    var dayOfTheWeek = date.getDay();
    var weekdays = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
    return weekdays[dayOfTheWeek];
}
//crmv@201468e
