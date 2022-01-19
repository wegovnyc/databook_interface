/*
webagenda-viewer (calendar viewer - ical & dav)
 
Copyright (C) 2017  Noël Martinon

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

var agenda_mail;
var agenda_name;
var xhr;
var start, end;

function get_users(){
	/*
    var xhr = $.ajax({
        timeout: 8000,
        url: "api/get_users.php",
        type: "GET",
        dataType: 'json',
        success: function(data) {
			$.each(data, function(k, v){					
				$('<option>').val(v).text(k).appendTo('#userlist');
			});
			$('#userlist').selectpicker("refresh");
            $('#loading-users').fadeOut();
        },
        error: function (xhr, ajaxOptions, thrownError) {
			new PNotify({
					title: 'Erreur',
					text: 'Impossible d\'obtenir la liste des agendas',
					styling: 'fontawesome',
					type: 'error',
                    hide: false,
                    addclass: 'translucent',
                    buttons: {
                        closer_hover: false,
                        sticker: false,
                    },
					animate: {
						animate: true,
						in_class: 'bounceInLeft',
						out_class: 'bounceOutRight'
					}						
				});
            $('#loading-users').fadeOut();
		}
    });
	*/
}

function load_calendar(ics){
    //if (!agenda_mail || !agenda_name) return;		
	
	//console.log('load_calendar')
	if(xhr && xhr.readyState != 4){
        xhr.abort();
    }
        
    PNotify.removeAll();	    
    /*
    var deb = moment(start).format('YYYYMMDD') + "T" +moment(start).format('HHmmss') + "Z";
	var fin = moment(end).format('YYYYMMDD') + "T" +moment(end).format('HHmmss') + "Z";
	ics.url += '&start='+deb+'&end='+fin;
	*/
    $('#loading-calendar').fadeIn("slow");
    $(".fc-view-container").fadeTo("slow", 0.3);	

    xhr = $.ajax({
        url: ics.url,
        type: "GET",
        success: function(data) {
            $('#calendar').fullCalendar('removeEventSources'); 
            recur_events = [];
                             
            if (data && data.replace(/^\n+|\n+$/g, '').length > 0) {
                $('#calendar').fullCalendar('addEventSource', fc_events(data, ics.event_properties))
			    $('#calendar').fullCalendar('addEventSource', expand_recur_events)
			}
            else {
                // Success notify empty calendar
                var stack_center = {
                    "dir1": "down",
                    "dir2": "right",
                    "context": $("#calendar"),
                    "firstpos1": 100,
                    "firstpos2": ($("#calendar").width() / 2) - (Number(PNotify.prototype.options.width.replace(/\D/g, '')) / 2)
                };
				$(window).resize(function(){
					stack_center.firstpos2 = ($("#calendar").width() / 2) - (Number(PNotify.prototype.options.width.replace(/\D/g, '')) / 2);
				});
		        new PNotify({
					title: 'Aucun rendez-vous',
					text: 'Cet agenda ne contient aucune donnée pour la période spécifiée.',
					styling: 'fontawesome',
					type: 'info',
					stack: stack_center,
					buttons: {
                        closer_hover: false,
                        sticker: false,
                    },						
					animate: {
						animate: true,
						in_class: 'bounceIn',
						out_class: 'fadeOut'
					}
				});
            }
                
			$('#loading-calendar').fadeOut();
			$(".fc-view-container").fadeTo("slow", 1);
        },
        error: function (xhr, ajaxOptions, thrownError) {
				if (xhr.statusText === 'abort') return;
				$('#loading-calendar').fadeOut();
		        $(".fc-view-container").fadeTo("slow", 1);
		        if (!xhr.status) return;
		        
				// Error notify
                var stack_center = {
                    "dir1": "down",
                    "dir2": "right",
                    "context": $("#calendar"),
                    "firstpos1": 100,
                    "firstpos2": ($("#calendar").width() / 2) - (Number(PNotify.prototype.options.width.replace(/\D/g, '')) / 2)
                };
				$(window).resize(function(){
					stack_center.firstpos2 = ($("#calendar").width() / 2) - (Number(PNotify.prototype.options.width.replace(/\D/g, '')) / 2);
				});
		        new PNotify({
					title: 'Erreur '+xhr.status,
					text: 'Impossible d\'ouvrir l\'agenda "'+ics.text+'"',
					styling: 'fontawesome',
					type: 'error',
					stack: stack_center,
					buttons: {
                        closer_hover: false,
                        sticker: false,
                    },						
					after_open: function(notice) {
						notice.attention('tada');
					}
				});
		  }
    });
}

function set_calendar(icalurl) {
	
    //$('#loading-users').fadeIn();
	//get_users();

    $('#calendar').fullCalendar({
        header: {
            left: 'prev,next today',
            center: 'title',
            //right: 'month,agendaWeek,agendaDay,listWeek update'
            right: 'month,agendaWeek,agendaDay,listMonth'
        },
        buttonText:{list:"planning"},
        locale: 'en',
        scrollTime: '07:00:00',
        defaultView: 'listMonth',
        eventRender: function(event, element) {
			//console.log(event, element)
			if (event.class === "PRIVATE") {
				// since fullcalendar 3.5.0 the title div must be manually added when event.title is empty
				if (!element.find('.fc-title').length)
				    element.find('.fc-content').append('<div class="fc-title"></div>');
				
                event.title = "Private";
				element.find('.fc-title').html(event.title);
				element.find('.fc-title').prepend('<i class="fas fa-lock" aria-hidden="true"></i> ');
				element.find('.fc-list-item-title').html(event.title);
			}
			else if (!event.title) {
				event.title = "Unnamed";
				element.find('.fc-title').html(event.title);
				element.find('.fc-list-item-title').html(event.title);
			}
            
            var tiptext = '<span class="qtip-time">';
            if (event.allDay) tiptext += (moment(event.start).format('ddd Do MMM YYYY'));
            else tiptext += (moment(event.start).format('ddd Do MMM YYYY HH:mm'));
			if (event.end) {  
				// Adjust end date in case of "allDay" because end is "date 00:00"
				var end_adjust = event.end;
				if (event.allDay) end_adjust = moment(event.end).subtract('hours', 24);
				
				var diff = moment(end_adjust).diff(moment(event.start),'hours');
				
				if (!event.allDay && moment(event.start).format('ddd Do MMM YYYY')==moment(event.end).format('ddd Do MMM YYYY'))
					tiptext += ' - ' + (moment(event.end).format('HH:mm'));
				else if (!event.allDay) tiptext += ' - ' + (moment(event.end).format('ddd Do MMM YYYY HH:mm'));
				else if (event.allDay && diff > 0) tiptext += ' - ' + (moment(end_adjust).format('ddd Do MMM YYYY'));
			}
			
			tiptext += '</span>';
            if (event.location  && event.location.trim().length) 
				tiptext += '<p class="location"><u>Location</u>: ' + event.location + '</p>';
            if (event.description && event.description.trim().length) {
				var d = event.description.replace('More Info:', '<br/><u>More Info</u>:').replace(/(?<=.)Agency:/g, '<br/>Agency:').replace('Agency:', '<u>Agency</u>:')
				tiptext += '<p class="description">' + (d.length > 250 ? d.substr(0, 200) + '...' : d) + '</p>';
			}
            if (event.attendee && event.attendee.length) { 
                tiptext += '<br><u>Participants</u> :<br>';
                for (var i = 0; i < event.attendee.length; i++) {
                    var attval = JSON.parse(JSON.stringify(event.attendee[i]))[1].cn;
                    var attmail = JSON.parse(JSON.stringify(event.attendee[i]))[3].replace(/mailto:/g, '');                    
                    if (!attval || attval == attmail) attval = attmail;                     
                    else attval = attval + " (<i>" + attmail + "</i>)";                    
                    tiptext += i>0?"<br>":"";
                    tiptext += "- " + attval;
                    }
            }
            
			element.qtip({    
				content: {    
					title: { text: event.title },
					text: tiptext
				},
				show: { solo: true },
				style: { 	
					classes: 'qtip-bootstrap qtip-shadow',
				},
				position: {
					my: 'bottom center',
					at: 'top center',
					target: 'mouse',
					viewport: $('#fullcalendar'),
					
				},
			}); 
        },
        eventClick:  function(event, jsEvent, view) {
			//console.log($('#calendar_modal').modal('show'))
			$('div.qtip:visible').qtip('hide');
			
			/*
			$('#calendar_modal .modal-title').text(event.title)

			var tiptext = '<span class="qtip-time">';
			tiptext += (moment(event.start).format('ddd Do MMM YYYY HH:mm'));
			if (event.end) {  
				var end_adjust = event.end;
				if (event.allDay) 
					end_adjust = moment(event.end).subtract('hours', 24);
				
				var diff = moment(end_adjust).diff(moment(event.start),'hours');
				
				if (!event.allDay && moment(event.start).format('ddd Do MMM YYYY')==moment(event.end).format('ddd Do MMM YYYY'))
					tiptext += ' - ' + (moment(event.end).format('HH:mm'));
				else if (!event.allDay) 
					tiptext += ' - ' + (moment(event.end).format('ddd Do MMM YYYY HH:mm'));
				else if (event.allDay && diff > 0) 
					tiptext += ' - ' + (moment(end_adjust).format('ddd Do MMM YYYY'));
			}
			tiptext += '</span>';
			if (event.location  && event.location.trim().length) 
				tiptext += '<p class="location"><u>Location</u>: ' + event.location + '</p>';
			if (event.description && event.description.trim().length) {
				var d = event.description.replace('More Info:', '<br/><u>More Info</u>:').replace('Agency:', '<br/><u>Agency</u>:').replace(/^<br.>/g, '')
				//d = d.replace(/(https?:\/\/[^\)\s]+)/g, '')
				d = d.replace(/\(?(https?:\/\/[^\)\s]+)\)?/g, function (m) { return `<a href="${m}">${m}</a>`;})
				tiptext += '<p class="description">' + d + '</p>';
			}

			$('#calendar_modal .modal-body').html(tiptext);
			$('#calendar_modal').modal('show');
			*/
			var url = event.description.match(/More Info: (https?:\/\/[^\s]+)/i)[1];
			//console.log(url)
			window.location.href = url;
        },
        eventAfterAllRender: function(view){
			if('agendaDay'===view.name){
				if($('.fc-time-grid-event').length>0){
					var renderedEvents = $('div.fc-event-container a');
					var firstEventOffsetTop = renderedEvents&&renderedEvents.length>0?renderedEvents[0].offsetTop:0;
					$('div.fc-scroller').scrollTo(firstEventOffsetTop+'px');
				}
			}
		},
		eventLimit: true, // allow "more" link when too many events
		viewRender:(function() {
            
            var lastViewName;
            return function(view, element) {
                   
                start = $('#calendar').fullCalendar('getView').start;
	            end = $('#calendar').fullCalendar('getView').end;
	            
                if(view.name === 'agendaDay' && lastViewName != 'agendaDay') {lastViewName = view.name; return;}
                if(view.name === 'agendaWeek' && (lastViewName == 'listWeek' || lastViewName == 'month')) {lastViewName = view.name;  return;}
                if(view.name === 'listWeek' && (lastViewName == 'agendaWeek' || lastViewName == 'month')) {lastViewName = view.name;  return;}
                                
			    load_calendar({url: icalurl});
                lastViewName = view.name;
            }
        })()
    })
    
	/*
    $(".fc-view-container").fadeTo("slow", 0.3);    
	// Custom selectpicker (".selectpicker" = "#userlist")
	$('#userlist').selectpicker({
	    style: 'btn-static',
	    language: 'FR',
	});

    // keep list open on agenda click
    $('#userlist').on('hidden.bs.select', function (e) {		
        $('[data-id=userlist]').trigger('click');
	});

	// keep list open on keydown 'esc' or 'tab'
	document.addEventListener("keydown",function(e){
        var charCode = e.charCode || e.keyCode || e.which;
        if (charCode == 27 || charCode == 9 ){    
            $('[data-id=userlist]').trigger('click');
        }        
    });        
    
    // search has a permanent focus
	$(document).on("click", function () {
	   $('[data-id=userlist]').trigger('click');
	});	
    
	// userlist auto open on start
	$('[data-id=userlist]').trigger('click');
	
    // load the list of available timezones, build the <select> options
    $.getJSON('https://fullcalendar.io/demo-timezones.json', function(timezones) {
      $.each(timezones, function(i, timezone) {
        if (timezone != 'UTC') { // UTC is already in the list
          $('#timezone-selector').append(
            $("<option/>").text(timezone).attr('value', timezone)
          );
          //$('<option>').val(timezone).text(i).appendTo('#timezone-selector');          
        }
      });
      $('#timezone-selector').selectpicker('val', moment.tz.guess());
      $('#timezone-selector').selectpicker("refresh");
    });

    // when the timezone selector changes, dynamically change the calendar option
    $('#timezone-selector').on('change', function() {
      $('#calendar').fullCalendar('option', 'timezone', this.value || false);
    });
    
    // when agenda selection changes    
	$("#userlist").on("changed.bs.select", function(e, clickedIndex, newValue, oldValue) {
		agenda_mail = $(this).find('option').eq(clickedIndex).val();
		agenda_name = $(this).find('option').eq(clickedIndex).text();

		start = $('#calendar').fullCalendar('getView').start;
        end = $('#calendar').fullCalendar('getView').end;	        
        
        // Source change so clear events now
        $('#calendar').fullCalendar('removeEventSources'); 
        recur_events = [];
        load_calendar({url:'ical/api/get_calendar.php?q='+agenda_mail, text:agenda_name});
	});
	*/
}
