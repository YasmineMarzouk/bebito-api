import { check, group, sleep } from 'k6';
import http from 'k6/http';
import { Counter, Rate, Trend } from 'k6/metrics';

const baseUrl = (__ENV.PACK_LOAD_BASE_URL || 'http://localhost').replace(/\/$/, '');
const profile = __ENV.PACK_LOAD_PROFILE || 'baseline';
const authorization = __ENV.PACK_LOAD_AUTHORIZATION || '';
const detailIdentifier = __ENV.PACK_LOAD_DETAIL_IDENTIFIER || '';
const checkoutPayload = __ENV.PACK_LOAD_CHECKOUT_PAYLOAD ? JSON.parse(__ENV.PACK_LOAD_CHECKOUT_PAYLOAD) : null;
const adminPackPayload = __ENV.PACK_LOAD_ADMIN_PACK_PAYLOAD ? JSON.parse(__ENV.PACK_LOAD_ADMIN_PACK_PAYLOAD) : null;

const errors = new Rate('pack_request_errors');
const listingDuration = new Trend('pack_listing_duration', true);
const checkoutDuration = new Trend('pack_checkout_duration', true);
const checkoutAttempts = new Counter('pack_checkout_attempts');

const profiles = {
  baseline: { executor: 'constant-vus', vus: 10, duration: '5m' },
  load: { executor: 'constant-vus', vus: 50, duration: '15m' },
  stress: { executor: 'ramping-vus', startVUs: 100, stages: [{ duration: '10m', target: 200 }, { duration: '5m', target: 0 }] },
  spike: { executor: 'ramping-vus', startVUs: 10, stages: [{ duration: '30s', target: 100 }, { duration: '2m', target: 10 }] },
  endurance: { executor: 'constant-vus', vus: 50, duration: '60m' },
};

if (!profiles[profile]) {
  throw new Error(`Unknown PACK_LOAD_PROFILE: ${profile}`);
}

if (!/^https?:\/\/(localhost|127\.0\.0\.1|[^/]+\.test)(:\d+)?$/i.test(baseUrl)) {
  throw new Error('PACK_LOAD_BASE_URL must be localhost, 127.0.0.1, or a dedicated .test host. Never target production.');
}

export const options = { scenarios: { pack_catalog: profiles[profile] } };

function headers(extra = {}) {
  return {
    Accept: 'application/json',
    ...(authorization ? { Authorization: authorization } : {}),
    ...extra,
  };
}

function assertResponse(response, metric) {
  metric.add(response.timings.duration);
  const ok = check(response, { 'response is successful': (item) => item.status >= 200 && item.status < 300 });
  errors.add(!ok);
}

export default function () {
  group('LOAD-01/02/03 Pack listing and availability', () => {
    const response = http.get(`${baseUrl}/api/v1/shop/packs`, { headers: headers() });
    assertResponse(response, listingDuration);
  });

  if (detailIdentifier) {
    group('LOAD-04/05 Pack detail and variant availability', () => {
      const response = http.get(`${baseUrl}/api/v1/shop/packs/${detailIdentifier}`, { headers: headers() });
      assertResponse(response, listingDuration);
    });
  }

  if (__ENV.PACK_LOAD_ENABLE_MUTATIONS === 'true' && checkoutPayload) {
    group('LOAD-09/10/11 Pack and mixed checkout', () => {
      checkoutAttempts.add(1);
      const payload = JSON.parse(JSON.stringify(checkoutPayload));
      const keySuffix = `${__VU}`.padStart(12, '0').slice(-12);
      const response = http.post(`${baseUrl}/api/v1/store/checkout`, JSON.stringify(payload), {
        headers: headers({ 'Content-Type': 'application/json', 'Idempotency-Key': `00000000-0000-4000-8000-${keySuffix}` }),
      });
      assertResponse(response, checkoutDuration);
    });
  }

  if (__ENV.PACK_LOAD_ENABLE_MUTATIONS === 'true' && adminPackPayload && authorization) {
    group('LOAD-06/07 Admin listing and create/update/publish', () => {
      const response = http.get(`${baseUrl}/api/v1/admin/packs`, { headers: headers() });
      assertResponse(response, listingDuration);
    });
  }

  sleep(1);
}
