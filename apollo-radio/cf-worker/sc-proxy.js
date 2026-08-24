/**
 * Apollo Radio — Cloudflare Worker SoundCloud Proxy
 * Deploy to: radio.apollo.rio.br/sc-proxy or apradio.pages.dev/sc-proxy
 *
 * Flow: ?url=<permalink> → oEmbed → track_id → api-v2 → progressive MP3 stream
 *
 * Features:
 *   - CORS headers for cross-origin fetch
 *   - Cache-Control (15 min)
 *   - Error responses with JSON structure
 *   - Health endpoint at /sc-proxy/health
 */

export async function onRequest(context) {
    const { request } = context;
    const url = new URL(request.url);

    // CORS preflight.
    if (request.method === 'OPTIONS') {
        return new Response(null, {
            status: 204,
            headers: corsHeaders(),
        });
    }

    // Health check.
    if (url.pathname.endsWith('/health')) {
        return jsonResponse({ status: 'ok', version: '2.0.0' }, 200);
    }

    try {
        const scUrl = url.searchParams.get('url');
        if (!scUrl) {
            return jsonResponse({ error: 'Missing url param' }, 400);
        }

        // Validate it's a SoundCloud URL.
        if (!scUrl.includes('soundcloud.com')) {
            return jsonResponse({ error: 'Invalid SoundCloud URL' }, 400);
        }

        // Step 1: oEmbed → extract track ID.
        const oEmbedUrl = `https://soundcloud.com/oembed?format=json&url=${encodeURIComponent(scUrl)}`;
        const oEmbedRes = await fetch(oEmbedUrl);
        if (!oEmbedRes.ok) {
            return jsonResponse({ error: 'oEmbed fetch failed', status: oEmbedRes.status }, 502);
        }
        const oEmbed = await oEmbedRes.json();

        // Fixed regex: single escape (was double-escaped in v1).
        const match = oEmbed.html.match(/tracks%2F(\d+)/);
        if (!match) {
            return jsonResponse({ error: 'Track ID not found in oEmbed' }, 404);
        }
        const trackId = match[1];

        // Step 2: api-v2 → track data.
        const clientId = 'u2ydppvwXCUxV6VITwH4OXk8JBySpoNr';
        const apiUrl = `https://api-v2.soundcloud.com/tracks/${trackId}?client_id=${clientId}`;
        const apiRes = await fetch(apiUrl);

        if (apiRes.status === 429) {
            // Rate limited — return oEmbed metadata only.
            return jsonResponse({
                error: 'Rate limited by SoundCloud',
                title: oEmbed.title || '',
                author: oEmbed.author_name || '',
            }, 429);
        }

        if (!apiRes.ok) {
            return jsonResponse({ error: 'Track API failed', status: apiRes.status }, 502);
        }
        const trackData = await apiRes.json();

        // Step 3: Find progressive MP3 transcoding.
        const stream = (trackData.media && trackData.media.transcodings)
            ? trackData.media.transcodings.find(t => t.format.protocol === 'progressive')
            : null;

        if (!stream) {
            return jsonResponse({ error: 'No progressive stream found' }, 404);
        }

        // Step 4: Resolve transcoding → direct MP3 URL.
        const sep = stream.url.includes('?') ? '&' : '?';
        const streamRes = await fetch(`${stream.url}${sep}client_id=${clientId}`);
        if (!streamRes.ok) {
            return jsonResponse({ error: 'Stream URL fetch failed', status: streamRes.status }, 502);
        }
        const streamData = await streamRes.json();

        return jsonResponse({
            stream_url: streamData.url,
            url: streamData.url,
            title: trackData.title || '',
            duration: trackData.duration ? Math.round(trackData.duration / 1000) : 0,
        }, 200, {
            'Cache-Control': 'public, max-age=900, s-maxage=900',
        });

    } catch (e) {
        return jsonResponse({ error: e.message }, 500);
    }
}

function jsonResponse(data, status, extraHeaders) {
    const headers = corsHeaders();
    headers['Content-Type'] = 'application/json';
    if (extraHeaders) {
        Object.assign(headers, extraHeaders);
    }
    return new Response(JSON.stringify(data), { status, headers });
}

function corsHeaders() {
    return {
        'Access-Control-Allow-Origin': '*',
        'Access-Control-Allow-Methods': 'GET, OPTIONS',
        'Access-Control-Allow-Headers': 'Content-Type',
        'Access-Control-Max-Age': '86400',
    };
}
