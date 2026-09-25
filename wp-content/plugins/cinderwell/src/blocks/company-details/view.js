const dayKeys = [ 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday' ];

const timeParts = ( date, timeZone, offsetMinutes ) => {
	try {
		const parts = new Intl.DateTimeFormat( 'en-US', {
			timeZone,
			weekday: 'long',
			hour: '2-digit',
			minute: '2-digit',
			hourCycle: 'h23',
		} ).formatToParts( date );
		const values = Object.fromEntries( parts.map( ( part ) => [ part.type, part.value ] ) );
		return {
			day: values.weekday.toLowerCase(),
			minutes: ( Number( values.hour ) * 60 ) + Number( values.minute ),
		};
	} catch ( error ) {
		const shifted = new Date( date.getTime() + ( Number( offsetMinutes || 0 ) * 60000 ) );
		return { day: dayKeys[ shifted.getUTCDay() ], minutes: ( shifted.getUTCHours() * 60 ) + shifted.getUTCMinutes() };
	}
};

const toMinutes = ( value ) => {
	if ( ! /^\d{2}:\d{2}$/.test( value || '' ) ) return null;
	const [ hours, minutes ] = value.split( ':' ).map( Number );
	return ( hours * 60 ) + minutes;
};

const isOpen = ( schedule, current ) => {
	const today = schedule[ current.day ] || {};
	if ( today.status === 'all_day' ) return true;
	if ( today.status === 'open' && ( today.periods || [] ).some( ( period ) => {
		const opens = toMinutes( period.opens );
		const closes = toMinutes( period.closes );
		return opens !== null && closes !== null && ( opens < closes ? current.minutes >= opens && current.minutes < closes : current.minutes >= opens );
	} ) ) return true;

	const dayIndex = dayKeys.indexOf( current.day );
	const previous = schedule[ dayKeys[ ( dayIndex + 6 ) % 7 ] ] || {};
	return previous.status === 'open' && ( previous.periods || [] ).some( ( period ) => {
		const opens = toMinutes( period.opens );
		const closes = toMinutes( period.closes );
		return opens !== null && closes !== null && opens >= closes && current.minutes < closes;
	} );
};

const updateStatus = ( status ) => {
	let schedule;
	try {
		schedule = JSON.parse( status.dataset.schedule || '{}' );
	} catch ( error ) {
		return;
	}
	const open = isOpen( schedule, timeParts( new Date(), status.dataset.timezone, status.dataset.utcOffset ) );
	const label = open ? status.dataset.openLabel : status.dataset.closedLabel;
	if ( status.textContent !== label ) status.textContent = label;
	status.classList.toggle( 'is-open', open );
};

const statuses = Array.from( document.querySelectorAll( '[data-cw-company-status]' ) );
const updateStatuses = () => statuses.forEach( updateStatus );

if ( statuses.length ) {
	updateStatuses();
	window.setInterval( updateStatuses, 60000 );
}
