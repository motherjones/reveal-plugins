let requestCount = {};
let nonceValue = '';
let currentPage = '';
let versionValue = false;

self.addEventListener('message', function (event) {
	if (event.data === 'newspack-reset-request-count') {
		requestCount = {};
	}
	if (event.data.indexOf('newspack-nonce-') === 0) {
		nonceValue = event.data.replace('newspack-nonce-', '');
	}
	if (event.data.indexOf('newspack-version-') === 0) {
		versionValue = event.data.replace('newspack-version-', '');
	}
});

const findProperty = payload => {
	const found = payload.match(/tid=([\w-]*)/);
	if (found) {
		return found[1];
	}
};

const sendMessage = message =>
	fetch(
		`/wp-json/newspack-manager/v1/sw-message?message=${encodeURIComponent(
			`${message} (URL: ${currentPage})`
		)}&nonce=${nonceValue}&version=${versionValue}`
	);

const handlePageViewForProperty = (clientId, property) => {
	if (!requestCount[clientId]) {
		requestCount[clientId] = {};
	}
	requestCount[clientId][property] = requestCount[clientId][property] + 1 || 1;
	if (requestCount[clientId][property] > 1) {
		sendMessage(`Property \`${property}\` has sent more than two pageviews per request.`);
	}
};

self.addEventListener('fetch', async event => {
	const url = event.request.url;
	if (url.match(/google-analytics\.com.*\/collect/)) {
		if (!event.clientId) {
			return;
		}
		const client = await self.clients.get(event.clientId);
		if (!client) {
			return;
		}
		currentPage = client.url;
		const { search } = new URL(url);
		let property = findProperty(search);
		let params = search.replace(/^\?/, '').split('&');
		const text = await event.request.text();
		if (!property) {
			property = findProperty(text);
		}
		if (property) {
			params = params.concat(text.split('&'));
			if (
				// GA4
				params.includes('en=page_view') ||
				// UA
				params.includes('t=pageview')
			) {
				handlePageViewForProperty(event.clientId, property);
			}
		}
	}
});

