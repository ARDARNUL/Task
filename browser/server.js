import express from 'express';
import { chromium } from 'playwright';

const app = express();
app.use(express.json());

const PORT = 3000;
let browser;

async function getBrowser() {
  if (!browser) {
    browser = await chromium.launch({
      headless: true,
      args: ['--no-sandbox', '--disable-dev-shm-usage', '--disable-blink-features=AutomationControlled'],
    });
  }
  return browser;
}

async function newContext() {
  const b = await getBrowser();
  return b.newContext({
    locale: 'ru-RU',
    timezoneId: 'Europe/Moscow',
    userAgent:
      'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36',
    viewport: { width: 1440, height: 900 },
  });
}

async function setupPage(context) {
  const page = await context.newPage();
  await page.addInitScript(() => {
    Object.defineProperty(navigator, 'webdriver', { get: () => false });
    Object.defineProperty(navigator, 'plugins', { get: () => [1, 2, 3, 4, 5] });
    Object.defineProperty(navigator, 'languages', { get: () => ['ru-RU', 'ru', 'en-US', 'en'] });
    window.chrome = { runtime: {} };
  });
  return page;
}

async function fetchFirstPage(baseUrl) {
  const context = await newContext();
  try {
    const page = await setupPage(context);
    console.log(`[goto first] ${baseUrl}`);
    await page.goto(baseUrl, { waitUntil: 'domcontentloaded', timeout: 45000 });
    await page.waitForTimeout(2500);

    const html = await page.content();
    const m = html.match(/<script type="application\/json" class="state-view">(.*?)<\/script>/s);
    if (!m) {
      console.log('[first] state-view not found');
      return { reviews: [], organization: null };
    }

    let state;
    try {
      state = JSON.parse(m[1]);
    } catch (e) {
      console.log(`[first] state-view parse error: ${e.message}`);
      return { reviews: [], organization: null };
    }

    const stack = state?.stack ?? [];
    let organization = {
      name: null,
      address: null,
      category: null,
      rating: null,
      ratingsCount: null,
      reviewsCount: null,
    };
    let first50 = [];

    for (const item of stack) {
      const results = item?.results;
      if (!results) continue;
      for (const org of results.items ?? []) {
        if (org?.ratingData) {
          organization.rating = org.ratingData.ratingValue ?? null;
          organization.ratingsCount = org.ratingData.ratingCount ?? null;
          organization.reviewsCount = org.ratingData.reviewCount ?? null;
        }
        if (org?.title) organization.name = org.title;
        if (org?.fullAddress) organization.address = org.fullAddress;
        else if (org?.address) organization.address = org.address;
        if (org?.categories?.[0]?.name) organization.category = org.categories[0].name;

        const rr = org?.reviewResults?.reviews;
        if (Array.isArray(rr) && rr.length > 0) {
          first50 = rr;
        }
      }
    }

    console.log(`[first] got ${first50.length} reviews, organization=${JSON.stringify(organization)}`);
    return { reviews: first50, organization };
  } finally {
    await context.close();
  }
}

async function fetchViaScroll(baseUrl, sessionLabel) {
  const context = await newContext();
  try {
    const page = await setupPage(context);
    const captured = [];
    const sessionSeen = new Set();

    page.on('response', async (response) => {
      const u = response.url();
      if (u.includes('/maps/api/business/fetchReviews')) {
        try {
          const json = await response.json();
          if (json?.data?.reviews) {
            let added = 0;
            for (const r of json.data.reviews) {
              if (r.reviewId && !sessionSeen.has(r.reviewId)) {
                sessionSeen.add(r.reviewId);
                captured.push(r);
                added++;
              }
            }
            console.log(`[${sessionLabel}] +${added} new, session total: ${captured.length}`);
          }
        } catch (_) {}
      }
    });

    console.log(`[${sessionLabel}] goto ${baseUrl}/reviews`);
    await page.goto(baseUrl + '/reviews', { waitUntil: 'domcontentloaded', timeout: 45000 });
    await page.waitForTimeout(4000);

    let lastCount = 0;
    let stable = 0;
    let scrolls = 0;
    const maxScrolls = 60;

    while (scrolls < maxScrolls) {
      await page.evaluate(() => {
        const containers = document.querySelectorAll('.scroll__container, [class*="reviews"]');
        containers.forEach((el) => {
          if (el.scrollHeight > el.clientHeight) el.scrollTop = el.scrollHeight;
        });
        window.scrollTo(0, document.body.scrollHeight);
      });
      await page.waitForTimeout(3000);

      const total = captured.length;
      if (total === lastCount) stable++;
      else stable = 0;
      lastCount = total;

      if (stable >= 10) {
        console.log(`[${sessionLabel}] stop after ${stable} stable iterations, captured ${captured.length}`);
        break;
      }
      scrolls++;
    }

    return captured;
  } finally {
    await context.close();
  }
}

app.post('/parse', async (req, res) => {
  const { url } = req.body;

  if (!url || !/^https?:\/\/yandex\.ru\/maps\/org\//.test(url)) {
    return res.status(400).json({ error: 'invalid url' });
  }

  const baseUrl = url.replace(/\/+$/, '').replace(/\/reviews$/, '');

  try {
    const { reviews: first50, organization } = await fetchFirstPage(baseUrl + '/reviews');

    const seen = new Set();
    const reviews = [];
    const addReviews = (arr) => {
      for (const r of arr) {
        if (!r?.reviewId || seen.has(r.reviewId)) continue;
        seen.add(r.reviewId);
        reviews.push({
          reviewId: r.reviewId,
          authorName: r.author?.name ?? null,
          rating: r.rating ?? null,
          text: r.text ?? null,
          publishedAt: r.updatedTime ?? null,
          raw: r,
        });
      }
    };
    addReviews(first50);
    console.log(`after HTML: collected ${reviews.length}`);

    const totalExpected = organization?.reviewsCount ?? 0;

    let sessionsUsed = 0;
    const maxSessions = 6;
    let prevCount = reviews.length;

    while (sessionsUsed < maxSessions) {
      sessionsUsed++;
      const label = `session ${sessionsUsed}`;
      const before = reviews.length;

      const captured = await fetchViaScroll(baseUrl, label);
      addReviews(captured);

      console.log(`[${label}] done. before=${before}, after=${reviews.length}`);

      if (reviews.length === before) {
        console.log(`[${label}] no new reviews — stopping`);
        break;
      }

      if (reviews.length === prevCount && sessionsUsed >= 3) {
        console.log(`no progress after session ${sessionsUsed} — stopping`);
        break;
      }
      prevCount = reviews.length;

      await new Promise((r) => setTimeout(r, 3000));
    }

    res.json({
      ok: true,
      url,
      organization: organization ?? {
        name: null,
        address: null,
        category: null,
        rating: null,
        ratingsCount: null,
        reviewsCount: null,
      },
      reviews,
      meta: {
        totalFromApi: totalExpected || reviews.length,
        reviewsCollected: reviews.length,
        sessionsUsed,
      },
    });
  } catch (err) {
    console.error('parse error:', err);
    res.status(500).json({ ok: false, error: err.message });
  }
});

app.get('/health', (_req, res) => res.json({ ok: true }));

app.listen(PORT, () => {
  console.log(`browser parser listening on :${PORT}`);
});