// Depends on ./ical_events.js

recur_events = []

function an_filter(string) {
    // remove non alphanumeric chars
    return string.replace(/[^\w\s]/gi, '')
}

function moment_icaltime(moment, timezone) {
    // TODO timezone
    return new ICAL.Time().fromJSDate(moment.toDate())
}

function expand_recur_events(start, end, timezone, events_callback) {
    events = []
    for (event of recur_events) {
	event_properties = event.event_properties
        expand_recur_event(event, moment_icaltime(start, timezone), moment_icaltime(end, timezone), function(event){
            fc_event(event, function(event){
                events.push(merge_events(event_properties, merge_events({className:['recur-event']}, event)))
            })
        })
    }
    events_callback(events)
}

function fc_events(ics, event_properties) {
    events = []
    ical_events(
        ics,
        function(event){
            fc_event(event, function(event){
                events.push(merge_events(event_properties, event))
            })
			//console.log(merge_events(event_properties, event))
        },
        function(event){
            event.event_properties = event_properties
            recur_events.push(event)
        }
    )
    return events
}

function merge_events(e, f) {
    // f has priority
    for (k in e) {
        if (k == 'className') {
            f[k] = [].concat(f[k]).concat(e[k])
        } else if (! f[k]) {
            f[k] = e[k]
        }
    }
    return f
}

function fc_event(event, event_callback) {
    e = {
        title:event.getFirstPropertyValue('summary'),
        id:event.getFirstPropertyValue('uid'),
        className:['event-'+an_filter(event.getFirstPropertyValue('uid'))],        
        allDay:false,
        class:event.getFirstPropertyValue('class'),
        description:event.getFirstPropertyValue('description'),
        location:event.getFirstPropertyValue('location'),
        attendee:event.getAllProperties('attendee'),    
    }
    try {
        e['start'] = event.getFirstPropertyValue('dtstart').toJSDate()
        e['allDay'] = event.getFirstPropertyValue('dtstart').toString().indexOf("T") === -1
        
        // Get timezone event string
        var tzid  = event.getFirstProperty('dtstart').getParameter('tzid') 
        
        // Adapt date-time if tzid is not 'undefined'
        if (tzid) {
            // Get timezone event offset (i.e. -04:00)
            var tzoffset = moment().tz(tzid).format('Z')
            
            // Create date-time moment with timezone
            var dt = event.getFirstPropertyValue('dtstart') // i.e. 2018-07-27T12:30:00
            var tzdate = moment.tz((dt+tzoffset).replace('Z', ''), tzid)
			//console.log(dt, tzoffset, (dt+tzoffset).replace('Z', ''), tzdate)
            
            // Apply user timezone
            var tzdate_user = tzdate.tz(moment.tz.guess())
            
            // Format date
            var dtevent = tzdate_user.toDate() //tzdate_user.format('YYYY-MM-DDTHH:mm:ss')
            
            // Set value 
            e['start'] = dtevent        
        }
    } catch (TypeError) {
        console.debug('Undefined "dtstart", vevent skipped.')
        return
    }
    try {
        e['end'] = event.getFirstPropertyValue('dtend').toJSDate()
        
        // Get timezone event string
        var tzid = event.getFirstProperty('dtend').getParameter('tzid')
        
        // Adapt date-time if tzid is not 'undefined'
        if (tzid) {
            // Get timezone event offset (i.e. -04:00)
            var tzoffset = moment().tz(tzid).format('Z')
            
            // Create date-time moment with timezone
            var dt = event.getFirstPropertyValue('dtend')
            var tzdate = moment.tz((dt+tzoffset).replace('Z', ''), tzid)
            
            // Apply user timezone
            var tzdate_user = tzdate.tz(moment.tz.guess())
            
            // Format date
            var dtevent = tzdate_user.toDate() //tzdate_user.format('YYYY-MM-DDTHH:mm:ss')
            
            // Set value 
            e['end'] = dtevent
        }
    } catch (TypeError) {
        e['allDay'] = true
    }
    event_callback(e)
}

