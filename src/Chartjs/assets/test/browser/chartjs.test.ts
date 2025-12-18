import { expect, test } from '@playwright/test';

test('Can display a line chart without options', async ({ page }) => {
    await page.goto('/ux-chartjs/without-options');

    // Wait for the canvas element to be visible
    const canvas = page.locator('canvas[data-controller*="chart"]');
    await expect(canvas).toBeVisible();

    // Wait for the chart instance to be available and access its configuration
    const chartData = await page
        .waitForFunction(() => {
            return (window as any).__chartInstance !== undefined;
        })
        .then(() =>
            page.evaluate(() => {
                const chart = (window as any).__chartInstance;
                return {
                    type: chart.config.type,
                    labels: chart.config.data.labels,
                    datasets: chart.config.data.datasets,
                    options: chart.config.options,
                };
            })
        );

    expect(chartData.type).toBe('line');
    expect(chartData.labels).toEqual(['January', 'February', 'March', 'April', 'May', 'June', 'July']);
    expect(chartData.datasets).toHaveLength(1);
    expect(chartData.datasets[0].label).toBe('My First dataset');
    expect(chartData.datasets[0].data).toEqual([0, 10, 5, 2, 20, 30, 45]);
    expect(chartData.datasets[0].backgroundColor).toBe('rgb(255, 99, 132)');
    expect(chartData.datasets[0].borderColor).toBe('rgb(255, 99, 132)');
});

test('Can display a line chart with options', async ({ page }) => {
    await page.goto('/ux-chartjs/with-options');

    // Wait for the canvas element to be visible
    const canvas = page.locator('canvas[data-controller*="chart"]');
    await expect(canvas).toBeVisible();

    // Wait for the chart instance to be available and access its configuration
    const chartData = await page
        .waitForFunction(() => {
            return (window as any).__chartInstance !== undefined;
        })
        .then(() =>
            page.evaluate(() => {
                const chart = (window as any).__chartInstance;
                return {
                    type: chart.config.type,
                    labels: chart.config.data.labels,
                    datasets: chart.config.data.datasets,
                    showLines: chart.config.options.showLines,
                };
            })
        );

    expect(chartData.type).toBe('line');
    expect(chartData.labels).toEqual(['January', 'February', 'March', 'April', 'May', 'June', 'July']);
    expect(chartData.datasets).toHaveLength(1);
    expect(chartData.datasets[0].data).toEqual([0, 10, 5, 2, 20, 30, 45]);
    expect(chartData.showLines).toBe(false);
});

test('Can display a pie chart', async ({ page }) => {
    await page.goto('/ux-chartjs/pie');

    // Wait for the canvas element to be visible
    const canvas = page.locator('canvas[data-controller*="chart"]');
    await expect(canvas).toBeVisible();

    // Wait for the chart instance to be available and access its configuration
    const chartData = await page
        .waitForFunction(() => {
            return (window as any).__chartInstance !== undefined;
        })
        .then(() =>
            page.evaluate(() => {
                const chart = (window as any).__chartInstance;
                return {
                    type: chart.config.type,
                    labels: chart.config.data.labels,
                    datasets: chart.config.data.datasets,
                };
            })
        );

    expect(chartData.type).toBe('pie');
    expect(chartData.labels).toEqual(['Red', 'Blue', 'Yellow', 'Green', 'Purple', 'Orange']);
    expect(chartData.datasets).toHaveLength(1);
    expect(chartData.datasets[0].label).toBe('My First Dataset');
    expect(chartData.datasets[0].data).toEqual([12, 19, 3, 5, 2, 3]);
    expect(chartData.datasets[0].backgroundColor).toEqual([
        'rgb(255, 99, 132)',
        'rgb(54, 162, 235)',
        'rgb(255, 205, 86)',
        'rgb(75, 192, 192)',
        'rgb(153, 102, 255)',
        'rgb(255, 159, 64)',
    ]);
});

test('Can display a pie chart with options', async ({ page }) => {
    await page.goto('/ux-chartjs/pie-with-options');

    // Wait for the canvas element to be visible
    const canvas = page.locator('canvas[data-controller*="chart"]');
    await expect(canvas).toBeVisible();

    // Wait for the chart instance to be available and access its configuration
    const chartData = await page
        .waitForFunction(() => {
            return (window as any).__chartInstance !== undefined;
        })
        .then(() =>
            page.evaluate(() => {
                const chart = (window as any).__chartInstance;
                return {
                    type: chart.config.type,
                    labels: chart.config.data.labels,
                    datasets: chart.config.data.datasets,
                    responsive: chart.config.options.responsive,
                    legendPosition: chart.config.options.plugins?.legend?.position,
                };
            })
        );

    expect(chartData.type).toBe('pie');
    expect(chartData.labels).toEqual(['Red', 'Blue', 'Yellow', 'Green', 'Purple', 'Orange']);
    expect(chartData.datasets).toHaveLength(1);
    expect(chartData.datasets[0].data).toEqual([12, 19, 3, 5, 2, 3]);
    expect(chartData.responsive).toBe(true);
    expect(chartData.legendPosition).toBe('top');
});
