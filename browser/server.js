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
      args: [
        '--no-sandbox',
        '--disable-dev-shm-usage',
        '--disable-blink-features=AutomationControlled',
      ],
    });
  }
  return browser;
}

/**
 * POST /parse
 * body: { url: "https://yandex.ru/maps/org/<seoname>/<businessId>/reviews/" }
 */
app.post('/parse', async (req, res) => {
  const { url } = req.body;

  if (!url || !/^https?:\/\/yandex\.ru\/maps\/org\//.test(url)) {
    return res.status(400).json({ error: 'invalid url' });
  }

  let context;
  try {
    const b = await getBrowser();

    context = await b.newContext({
      locale: 'ru-RU',
      timezoneId: 'Europe/Moscow',
      userAgent:
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36',
      viewport: { width: 1440, height: 900 },
    });

    const page = await context.newPage();

    const reviewResponses = [];
    const orgResponses = [];

    page.on('response', async (response) => {
      const u = response.url();
      if (u.includes('/maps/api/business/fetchReviews')) {
        try {
          const json = await response.json();
          reviewResponses.push(json);
        } catch (_) {}
      } else if (u.includes('/maps/api/business/fetchOrg')) {
        try {
          const json = await response.json();
          orgResponses.push(json);
        } catch (_) {}
      }
    });

    await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 45000 });
    await page.waitForTimeout(1500);

    const maxScrolls = 40; 
    let scrolls = 0;
    let lastCount = 0;
    let stableIterations = 0;

    while (scrolls < maxScrolls) {
      await page.mouse.wheel(0, 4000);
      await page.waitForTimeout(1200);

      const currentCount = reviewResponses.reduce(
        (sum, r) => sum + (r?.data?.reviews?.length ?? 0),
        0
      );

      if (currentCount === lastCount) {
        stableIterations++;
      } else {
        stableIterations = 0;
      }

      lastCount = currentCount;

      const first = reviewResponses[0];
      const total = first?.data?.params?.count ?? 0;
      const receivedIds = new Set();
      for (const r of reviewResponses) {
        for (const rev of r?.data?.reviews ?? []) receivedIds.add(rev.reviewId);
      }
      if (total > 0 && receivedIds.size >= total) break;
      if (stableIterations >= 4) break;

      scrolls++;
    }

    let organizationInfo = { name: null, rating: null, ratingsCount: null, reviewsCount: null };
    if (orgResponses.length > 0) {
      const org = orgResponses[0]?.data ?? orgResponses[0];
      organizationInfo = {
        name: org?.title ?? org?.name ?? null,
        rating: org?.ratingData?.ratingValue ?? org?.ratingValue ?? null,
        ratingsCount: org?.ratingData?.ratingCount ?? org?.ratingCount ?? null,
        reviewsCount: org?.ratingData?.reviewCount ?? org?.reviewCount ?? null,
      };
    }

    const seen = new Set();
    const reviews = [];
    for (const r of reviewResponses) {
      for (const rev of r?.data?.reviews ?? []) {
        if (!rev?.reviewId || seen.has(rev.reviewId)) continue;
        seen.add(rev.reviewId);
        reviews.push({
          reviewId: rev.reviewId,
          authorName: rev.author?.name ?? null,
          rating: rev.rating ?? null,
          text: rev.text ?? null,
          publishedAt: rev.updatedTime ?? null,
          raw: rev,
        });
      }
    }

    const firstParams = reviewResponses[0]?.data?.params ?? {};

    res.json({
      ok: true,
      url,
      organization: organizationInfo,
      reviews,
      meta: {
        totalFromApi: firstParams.count ?? reviews.length,
        totalPages: firstParams.totalPages ?? null,
        pagesFetched: reviewResponses.length,
        reviewsCollected: reviews.length,
      },
    });
  } catch (err) {
    console.error('parse error:', err);
    res.status(500).json({ ok: false, error: err.message });
  } finally {
    if (context) await context.close();
  }
});

app.get('/health', (_req, res) => res.json({ ok: true }));

app.listen(PORT, () => {
  console.log(`browser parser listening on :${PORT}`);
});