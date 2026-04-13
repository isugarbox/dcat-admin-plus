<div class="calendar-box">
    <div id="{{$calendar_id}}"></div>

    <div id="external-events">
    </div>
    <div id="drop-remove">

    </div>

    <!-- 模态框 -->
    <div class="modal fade" id="{{$calendar_id}}-Modal" tabindex="-1" data-backdrop="static" role="dialog" aria-labelledby="eventModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content ">
                <div class="modal-header">
                    <h5 class="modal-title">事件详情</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">知道了</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>

    /* initialize the external events
     -----------------------------------------------------------------*/
    function ini_events(ele) {
        ele.each(function () {

            // create an Event Object (https://fullcalendar.io/docs/event-object)
            // it doesn't need to have a start or end
            var eventObject = {
                title: $.trim($(this).text()) // use the element's text as the event title
            }

            // store the Event Object in the DOM element so we can get to it later
            $(this).data('eventObject', eventObject)

            // make the event draggable using jQuery UI
            $(this).draggable({
                zIndex: 1070,
                revert: true, // will cause the event to go back to its
                revertDuration: 0  //  original position after the drag
            })

        })
    }

    ini_events($('#external-events div.external-event'))

    /* initialize the calendar
 -----------------------------------------------------------------*/
    //Date for the calendar events (dummy data)
    var date = new Date()
    var d = date.getDate(),
        m = date.getMonth(),
        y = date.getFullYear()

    var Calendar = FullCalendar.Calendar;
    var Draggable = FullCalendar.Draggable;

    var containerEl = document.getElementById('external-events');
    var checkbox = document.getElementById('drop-remove');
    var calendarEl = document.getElementById('{{$calendar_id}}');

    // initialize the external events
    // ----------------------------------------------------------------
    new Draggable(containerEl, {
        itemSelector: '.external-event',
        eventData: function (eventEl) {
            return {
                title: eventEl.innerText,
                backgroundColor: window.getComputedStyle(eventEl, null).getPropertyValue('background-color'),
                borderColor: window.getComputedStyle(eventEl, null).getPropertyValue('background-color'),
                textColor: window.getComputedStyle(eventEl, null).getPropertyValue('color'),
            };
        }
    });

    var calendar = new Calendar(calendarEl, {
        initialView: '{{$initialView}}',
        selectable: true,
        locale: '{{$locale}}',
        timeZone: '{{$timeZone}}',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: '{{$header_toolbar_right_btn}}'
        },
        buttonText: { // 设置按钮文本为中文
            today: '今天',
            month: '月',
            week: '周',
            day: '日',
            list: '日程',
        },
        views: {
            list: {
                buttonText: '日程',
            }
        },
        themeSystem: 'bootstrap',
        events: {!! $items !!},
        eventClick: function(info) {
            // 检查事件是否允许弹出模态框
            if (info.event.extendedProps.showModal) {
                // 获取事件的标题和描述
                var eventTitle = info.event.title;
                var eventDescription = info.event.extendedProps.description;

                // 设置模态框的内容
                $('#{{$calendar_id}}-Modal').find('.modal-title').text(eventTitle);
                $('#{{$calendar_id}}-Modal').find('.modal-body').text(eventDescription);
                // 显示模态框
                $('#{{$calendar_id}}-Modal').modal('show');
            }

        },
        editable: false,
        droppable: false, // this allows things to be dropped onto the calendar !!!
        drop: function (info) {
            // is the "remove after drop" checkbox checked?
            if (checkbox.checked) {
                // if so, remove the element from the "Draggable Events" list
                info.draggedEl.parentNode.removeChild(info.draggedEl);
            }
        },
        dateClick: function(info) {
            let date = info.dateStr;
            console.log(date);

            $.ajax({
            url: '/admin/resource/records/started_at/' + date + '/patient_id/' + user_id,
            method: 'GET',
            success: function(data) {
                data.forEach(item => {
                    
                });
            }
        });
        }
    });

    calendar.render();
    // $('#calendar').fullCalendar()

    /* ADDING EVENTS */
    var currColor = '#3c8dbc' //Red by default
    // Color chooser button
    $('#color-chooser > li > a').click(function (e) {
        e.preventDefault()
        // Save color
        currColor = $(this).css('color')
        // Add color effect to button
        $('#add-new-event').css({
            'background-color': currColor,
            'border-color': currColor
        })
    })
    $('#add-new-event').click(function (e) {
        e.preventDefault()
        // Get value and make sure it is not null
        var val = $('#new-event').val()
        if (val.length == 0) {
            return
        }

        // Create events
        var event = $('<div />')
        event.css({
            'background-color': currColor,
            'border-color': currColor,
            'color': '#fff'
        }).addClass('external-event')
        event.text(val)
        $('#external-events').prepend(event)

        // Add draggable funtionality
        ini_events(event)

        // Remove event from text input
        $('#new-event').val('')
    })

    // 移植到此(暂时无奈...)
    var user_id = 0;
    $('[name="patient"]').on('change', function() {
        var id = $(this).val();
        if (id == '' || id == undefined) {
            return;
        }
        user_id = id;
        calendar.removeAllEvents();

        $.ajax({
            url: '/admin/resource/prescriptions/patient_id/' + id,
            method: 'GET',
            success: function(data) {
                data.forEach(item => {
                    let start = new Date(item.start_at);
                    let end = new Date(item.end_at);

                    // 循环每一天
                    let currentDate = new Date(start);
                    let i = 0;

                    while (currentDate <= end) {
                        i ++;
                        calendar.addEvent({
                            id: item.id + '_' + currentDate.getTime(), // 唯一ID
                            title: i + '处方：' + (item.body_part?.name ?? '部位'),
                            start: formatDateTime(currentDate),
                            // end: e,
                            display: 'list-item', // 圆点
                            color: '#3788d8',
                            allDay: true,
                            showModal: true,
                            description: '部位：' + (item.body_part?.name ?? '') + '，运动模型：' + (item.sport_mode?.name ?? '') + '，计量方式：' + (item.measurement_mode?.name ?? '') + '，设备种类：' + (item.category?.name ?? '') + getGroups(item),
                        });

                        currentDate.setDate(currentDate.getDate() + 1);
                    }
                });
            }
        });

        $.ajax({
            url: '/admin/resource/records/patient_id/' + id,
            method: 'GET',
            success: function(data) {
                data.forEach(item => {
                    let start = new Date(item.started_at);
                    let end = new Date(item.ended_at);

                    // 循环每一天
                    let currentDate = new Date(start);
                    while (currentDate <= end) {
                        calendar.addEvent({
                            id: item.id + '_' + currentDate.getTime(), // 唯一ID
                            title: '运动：' + (item.body_part?.name ?? '部位'),
                            start: formatDateTime(currentDate),
                            display: 'list-item', // 圆点
                            color: '#52C41A',
                            allDay: true,
                            showModal: false,
                            description: '',
                            url: '/admin/chart/info'
                        });

                        // 日期 +1 天（纯原生 JS）
                        currentDate.setDate(currentDate.getDate() + 1);
                    }
                });
            }
        });
    });

    function formatDateTime(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0'); // 月份补零
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        const seconds = String(date.getSeconds()).padStart(2, '0');
        
        return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
    }

    function getGroups(prescription) {
        var groups = JSON.parse(prescription.groups);
        var j = 1;
        var sub = '运动组数：' + Object.keys(groups).length + '；';
        for (let key in groups) {
            sub += '第' + (j) + '组：' + '运动量['+prescription.measurement_mode.remark+']：' + groups[key]['amount'] + 
                '，伸展参数['+prescription.sport_mode.remark+']：' + groups[key].extension + 
                '，屈曲参数['+prescription.sport_mode.remark+']：' + groups[key].flexion + '；';
            j++;
        }

        return sub;
    }
</script>