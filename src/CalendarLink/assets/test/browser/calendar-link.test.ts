import { expect, test, type Page } from '@playwright/test';

type Provider = 'google' | 'outlook' | 'office365' | 'ics';

async function hrefByProvider(page: Page, provider: Provider): Promise<string> {
    const link = page.getByTestId(`calendar-link-${provider}`);
    await expect(link).toBeVisible();
    const href = await link.getAttribute('href');
    expect(href, `href for provider "${provider}" must not be null`).not.toBeNull();
    return href as string;
}

function decodeIcs(href: string): string {
    const match = href.match(/^data:text\/calendar;[^,]*base64,(.+)$/);
    expect(match, `ICS href must be a base64 data: URI, got "${href}"`).not.toBeNull();
    return Buffer.from((match as RegExpMatchArray)[1], 'base64').toString('utf8');
}

test.describe('CalendarLink', () => {
    test('Basic event — renders correct links for all providers', async ({ page }) => {
        await page.goto('/ux-calendar-link/basic');

        const google = await hrefByProvider(page, 'google');
        expect(google).toContain('https://calendar.google.com/calendar/render?');
        expect(google).toContain('action=TEMPLATE');
        expect(google).toContain('text=Symfony%20UX%20Conf');
        expect(google).toContain('dates=20300615T100000Z%2F20300615T110000Z');
        expect(google).toContain('details=Annual%20conference');
        expect(google).toContain('location=Paris%2C%20FR');

        const outlook = await hrefByProvider(page, 'outlook');
        expect(outlook).toContain('https://outlook.live.com/calendar/0/deeplink/compose?');
        expect(outlook).toContain('rru=addevent');
        expect(outlook).toContain('subject=Symfony%20UX%20Conf');
        expect(outlook).toContain('startdt=2030-06-15T10%3A00%3A00Z');
        expect(outlook).toContain('enddt=2030-06-15T11%3A00%3A00Z');
        expect(outlook).toContain('body=Annual%20conference');
        expect(outlook).toContain('location=Paris%2C%20FR');

        const office365 = await hrefByProvider(page, 'office365');
        expect(office365).toContain('https://outlook.office.com/calendar/0/deeplink/compose?');
        expect(office365).toContain('subject=Symfony%20UX%20Conf');
        expect(office365).toContain('startdt=2030-06-15T10%3A00%3A00Z');
        expect(office365).toContain('enddt=2030-06-15T11%3A00%3A00Z');

        const ics = decodeIcs(await hrefByProvider(page, 'ics'));
        expect(ics).toContain('BEGIN:VCALENDAR');
        expect(ics).toContain('VERSION:2.0');
        expect(ics).toContain('PRODID:-//Symfony//UX Calendar Link//EN');
        expect(ics).toContain('BEGIN:VEVENT');
        expect(ics).toContain('DTSTART:20300615T100000Z');
        expect(ics).toContain('DTEND:20300615T110000Z');
        expect(ics).toContain('SUMMARY:Symfony UX Conf');
        expect(ics).toContain('DESCRIPTION:Annual conference');
        expect(ics).toContain('LOCATION:Paris\\, FR');
        expect(ics).toContain('END:VEVENT');
        expect(ics).toContain('END:VCALENDAR');
    });

    test('All-day event — uses date-only format across providers', async ({ page }) => {
        await page.goto('/ux-calendar-link/all-day');

        const google = await hrefByProvider(page, 'google');
        expect(google).toContain('text=Company%20Holiday');
        expect(google).toContain('dates=20300615%2F20300616');
        expect(google).toContain('location=Worldwide');

        const outlook = await hrefByProvider(page, 'outlook');
        expect(outlook).toContain('subject=Company%20Holiday');
        expect(outlook).toContain('startdt=2030-06-15');
        expect(outlook).toContain('enddt=2030-06-15');
        expect(outlook).toContain('allday=true');
        expect(outlook).not.toContain('startdt=2030-06-15T');

        const office365 = await hrefByProvider(page, 'office365');
        expect(office365).toContain('allday=true');
        expect(office365).toContain('startdt=2030-06-15');

        const ics = decodeIcs(await hrefByProvider(page, 'ics'));
        expect(ics).toContain('DTSTART;VALUE=DATE:20300615');
        expect(ics).toContain('DTEND;VALUE=DATE:20300616');
        expect(ics).toContain('SUMMARY:Company Holiday');
        expect(ics).toContain('LOCATION:Worldwide');
    });

    test('Recurring event — RRULE exposed by Google and ICS, omitted by Outlook providers', async ({ page }) => {
        await page.goto('/ux-calendar-link/recurrence');

        const google = await hrefByProvider(page, 'google');
        expect(google).toContain('text=Daily%20Standup');
        expect(google).toContain('recur=RRULE%3AFREQ%3DDAILY%3BCOUNT%3D5');

        const outlook = await hrefByProvider(page, 'outlook');
        expect(outlook).toContain('subject=Daily%20Standup');
        expect(outlook).not.toContain('recur=');
        expect(outlook).not.toContain('RRULE');

        const office365 = await hrefByProvider(page, 'office365');
        expect(office365).not.toContain('recur=');
        expect(office365).not.toContain('RRULE');

        const ics = decodeIcs(await hrefByProvider(page, 'ics'));
        expect(ics).toContain('SUMMARY:Daily Standup');
        expect(ics).toContain('RRULE:FREQ=DAILY;COUNT=5');
    });

    test('Event with reminders — VALARM blocks exposed only by ICS', async ({ page }) => {
        await page.goto('/ux-calendar-link/reminders');

        const ics = decodeIcs(await hrefByProvider(page, 'ics'));
        expect(ics).toContain('SUMMARY:Dentist Appointment');
        const alarmBlocks = ics.match(/BEGIN:VALARM[\s\S]*?END:VALARM/g) ?? [];
        expect(alarmBlocks).toHaveLength(2);
        expect(ics).toContain('ACTION:DISPLAY');
        expect(ics).toContain('TRIGGER:-PT15M');
        expect(ics).toContain('TRIGGER:-PT1H');

        const google = await hrefByProvider(page, 'google');
        expect(google).toContain('text=Dentist%20Appointment');
        expect(google).not.toContain('VALARM');
        expect(google).not.toContain('TRIGGER');

        const outlook = await hrefByProvider(page, 'outlook');
        expect(outlook).not.toContain('VALARM');
        expect(outlook).not.toContain('TRIGGER');

        const office365 = await hrefByProvider(page, 'office365');
        expect(office365).not.toContain('VALARM');
        expect(office365).not.toContain('TRIGGER');
    });
});
