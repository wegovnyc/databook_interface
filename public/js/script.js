function unescape(t)
{
	return t.replace(/""/g, '"').replace(/''/g, "'")
}

function toDashDate(d)
{
	if (!d)
		return ''
	//console.log(s)
	y  = d.toString().substr(0, 4)
	m  = d.toString().substr(4, 2)
	d  = d.toString().substr(6, 2)
	return '<span class="text-nowrap">'+y+'-'+m+'-'+d+'</span>';
}

function usToDashDate(d)
{
	if (!d)
		return ''
	console.log(d)
	dd  = d.toString().substr(0, 2)
	m  = d.toString().substr(3, 2)
	y  = d.toString().substr(8, 4)
	//console.log(y,m,d)
	return '<span class="text-nowrap">20'+y+'-'+m+'-'+dd+'</span>';
}

function toFin(d)
{
	return '$' + parseFloat(d).toFixed(2)
}


