import { expect, test } from '@playwright/test';

test('Can filters examples from homepage', async ({ page }) => {
    await page.goto('/');
    await expect(page.getByText("Symfony UX's E2E App")).toBeVisible();

    // Ensure that some examples are initially visible
    await expect(page).toHaveURL('/');
    await expect(page.getByRole('heading', { name: 'UX Autocomplete' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'UX Map' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'UX Translator' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Clear' })).toBeDisabled();

    // Filter examples by "autocomplete"
    await page.getByRole('textbox', { name: 'Search for examples:' }).fill('autocomplete');
    await page.waitForResponse('/_components/LiveExamplesSearch');

    await expect(page.getByRole('heading', { name: 'UX Autocomplete' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'UX Map' })).not.toBeVisible();
    await expect(page.getByRole('heading', { name: 'UX Translator' })).not.toBeVisible();
    await expect(page, 'The URL must be updated with the query value').toHaveURL('/?query=autocomplete');
    await expect(page.getByRole('button', { name: 'Clear' })).toBeEnabled();

    // Filter examples by "map"
    await page.getByRole('textbox', { name: 'Search for examples:' }).fill('map');
    await page.waitForResponse('/_components/LiveExamplesSearch');

    await expect(page.getByRole('heading', { name: 'UX Autocomplete' })).not.toBeVisible();
    await expect(page.getByRole('heading', { name: 'UX Map' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'UX Translator' })).not.toBeVisible();
    await expect(page, 'The URL must be updated with the query value').toHaveURL('/?query=map');
    await expect(page.getByRole('button', { name: 'Clear' })).toBeEnabled();

    // Filter examples by a non-existing term
    await page
        .getByRole('textbox', { name: 'Search for examples:' })
        .fill("Les chaussettes de l'archiduchesse sont-elles sèches ou archi-sèches ?");
    await page.waitForResponse('/_components/LiveExamplesSearch');

    await expect(page.getByText('No results found.')).toBeVisible();
    await expect(page.getByRole('heading', { name: 'UX Autocomplete' })).not.toBeVisible();
    await expect(page.getByRole('heading', { name: 'UX Map' })).not.toBeVisible();
    await expect(page.getByRole('heading', { name: 'UX Translator' })).not.toBeVisible();
    await expect(page.getByRole('button', { name: 'Clear' })).toBeEnabled();

    // Reset the filter
    await page.getByRole('button', { name: 'Clear' }).click();
    await page.waitForResponse('/_components/LiveExamplesSearch/clearQuery');

    await expect(page.getByRole('heading', { name: 'UX Autocomplete' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'UX Map' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'UX Translator' })).toBeVisible();
    await expect(page, 'The URL must be reset').toHaveURL('/?query=');
    await expect(page.getByRole('button', { name: 'Clear' })).toBeDisabled();
});

test('Can mutate a LiveProp(url: true), the state must be persisted in the URL, as a query parameter', async ({
    page,
}) => {
    await page.goto('/ux-live-component/counter');
    await expect(page.getByTestId('value')).toHaveText('0');
    await expect(page).toHaveURL('/ux-live-component/counter');

    // Increment the counter
    await page.getByRole('button', { name: '+' }).click();
    await page.waitForResponse('/_components/LiveCounter/increment');
    await expect(page.getByTestId('value')).toHaveText('1');
    await expect(page).toHaveURL('/ux-live-component/counter?value=1');

    // Increment the counter again
    await page.getByRole('button', { name: '+' }).click();
    await page.waitForResponse('/_components/LiveCounter/increment');
    await expect(page.getByTestId('value')).toHaveText('2');
    await expect(page).toHaveURL('/ux-live-component/counter?value=2');

    // Decrement the counter
    await page.getByRole('button', { name: '-' }).click();
    await page.waitForResponse('/_components/LiveCounter/decrement');
    await expect(page.getByTestId('value')).toHaveText('1');
    await expect(page).toHaveURL('/ux-live-component/counter?value=1');

    // Reload the page to ensure state is preserved
    await page.reload();
    await expect(page.getByTestId('value')).toHaveText('1');
    await expect(page).toHaveURL('/ux-live-component/counter?value=1');
});

test('Can mutate a LiveProp(url: new UrlMapping(mapPath: true)), the state must be persisted in the URL, as a path parameter', async ({
    page,
}) => {
    await page.goto('/ux-live-component/fruits');

    const buttonPrevious = page.getByRole('button', { name: 'Previous' });
    const buttonNext = page.getByRole('button', { name: 'Next' });

    await expect(page.getByRole('heading', { name: 'Page 1' })).toBeVisible();
    await expect(page.getByText('Apple')).toBeVisible();
    await expect(page.getByText('Banana')).toBeVisible();
    await expect(page.getByText('Cherry')).toBeVisible();
    await expect(page.getByText('Coconut')).toBeVisible();
    await expect(page.getByText('Grape')).toBeVisible();
    await expect(buttonPrevious).toBeDisabled();
    await expect(buttonNext).toBeEnabled();

    // Go to next page
    await buttonNext.click();
    await page.waitForResponse('/_components/LiveFruitsPagination/goToNextPage');
    await expect(page.getByRole('heading', { name: 'Page 2' })).toBeVisible();
    await expect(page.getByText('Kiwi')).toBeVisible();
    await expect(page.getByText('Lemon')).toBeVisible();
    await expect(page.getByText('Mango')).toBeVisible();
    await expect(page.getByText('Orange')).toBeVisible();
    await expect(page.getByText('Papaya')).toBeVisible();
    await expect(buttonPrevious).toBeEnabled();
    await expect(buttonNext).toBeEnabled();
    await expect(page).toHaveURL('/ux-live-component/fruits/2');

    // Go to next page
    await buttonNext.click();
    await page.waitForResponse('/_components/LiveFruitsPagination/goToNextPage');
    await expect(page.getByRole('heading', { name: 'Page 3' })).toBeVisible();
    await expect(page.getByText('Peach')).toBeVisible();
    await expect(page.getByText('Pineapple')).toBeVisible();
    await expect(page.getByText('Pear')).toBeVisible();
    await expect(page.getByText('Pomegranate')).toBeVisible();
    await expect(page.getByText('Pomelo')).toBeVisible();
    await expect(buttonPrevious).toBeEnabled();
    await expect(buttonNext).toBeDisabled();
    await expect(page).toHaveURL('/ux-live-component/fruits/3');

    // Go back to previous page
    await buttonPrevious.click();
    await page.waitForResponse('/_components/LiveFruitsPagination/goToPreviousPage');
    await expect(page.getByRole('heading', { name: 'Page 2' })).toBeVisible();
    await expect(page.getByText('Kiwi')).toBeVisible();
    await expect(page.getByText('Lemon')).toBeVisible();
    await expect(page.getByText('Mango')).toBeVisible();
    await expect(page.getByText('Orange')).toBeVisible();
    await expect(page.getByText('Papaya')).toBeVisible();
    await expect(buttonPrevious).toBeEnabled();
    await expect(buttonNext).toBeEnabled();
    await expect(page).toHaveURL('/ux-live-component/fruits/2');

    // Go explicitly to page 1
    await page.goto('/ux-live-component/fruits/1');
    await expect(page.getByRole('heading', { name: 'Page 1' })).toBeVisible();
    await expect(page.getByText('Apple')).toBeVisible();
    await expect(page.getByText('Banana')).toBeVisible();
    await expect(page.getByText('Cherry')).toBeVisible();
    await expect(page.getByText('Coconut')).toBeVisible();
    await expect(page.getByText('Grape')).toBeVisible();
    await expect(buttonPrevious).toBeDisabled();
    await expect(buttonNext).toBeEnabled();

    // Reload the page to ensure state is preserved
    await page.goto('/ux-live-component/fruits/1');
    await expect(page.getByRole('heading', { name: 'Page 1' })).toBeVisible();
    await expect(page.getByText('Apple')).toBeVisible();
    await expect(page.getByText('Banana')).toBeVisible();
    await expect(page.getByText('Cherry')).toBeVisible();
    await expect(page.getByText('Coconut')).toBeVisible();
    await expect(page.getByText('Grape')).toBeVisible();
    await expect(buttonPrevious).toBeDisabled();
    await expect(buttonNext).toBeEnabled();
});

test('Can mutate a LiveProp(url: new UrlMapping(mapPath: true)), the state must be persisted in the URL, as a path parameter, and unknown query parameters must be preserved', async ({
    page,
}) => {
    await page.goto('/ux-live-component/fruits/2?foo=bar');

    const buttonPrevious = page.getByRole('button', { name: 'Previous' });
    const buttonNext = page.getByRole('button', { name: 'Next' });

    await expect(page.getByRole('heading', { name: 'Page 2' })).toBeVisible();
    await expect(page.getByText('Kiwi')).toBeVisible();
    await expect(page.getByText('Lemon')).toBeVisible();
    await expect(page.getByText('Mango')).toBeVisible();
    await expect(page.getByText('Orange')).toBeVisible();
    await expect(page.getByText('Papaya')).toBeVisible();
    await expect(buttonPrevious).toBeEnabled();
    await expect(buttonNext).toBeEnabled();
    await expect(page).toHaveURL('/ux-live-component/fruits/2?foo=bar');

    // Go to next page
    await buttonNext.click();
    await page.waitForResponse('/_components/LiveFruitsPagination/goToNextPage');
    await expect(page.getByRole('heading', { name: 'Page 3' })).toBeVisible();
    await expect(page.getByText('Peach')).toBeVisible();
    await expect(page.getByText('Pineapple')).toBeVisible();
    await expect(page.getByText('Pear')).toBeVisible();
    await expect(page.getByText('Pomegranate')).toBeVisible();
    await expect(page.getByText('Pomelo')).toBeVisible();
    await expect(buttonPrevious).toBeEnabled();
    await expect(buttonNext).toBeDisabled();
    await expect(page).toHaveURL('/ux-live-component/fruits/3?foo=bar');
});

test('Form auto-validation', async ({ page }) => {
    await page.goto('/ux-live-component/registration-form');

    const fieldEmail = page.getByRole('textbox', { name: 'Email' });
    const fieldPassword = page.getByRole('textbox', { name: 'Password' });
    const buttonSubmit = page.getByRole('button', { name: 'Register' });

    // Let's submit the form without filling in any values
    await buttonSubmit.click();
    await page.waitForResponse('/_components/LiveRegistrationForm/saveRegistration');

    await expect(page.getByText('This value should not be blank.')).toHaveCount(2);
    await expect(buttonSubmit).toBeDisabled();

    // Fill the email only (with an invalid value)
    await fieldEmail.fill('invalid-email');
    await fieldEmail.blur();
    await page.waitForResponse('/_components/LiveRegistrationForm');

    await expect(page.getByText('This value is not a valid email address.')).toHaveCount(1);
    await expect(page.getByText('This value should not be blank.')).toHaveCount(1);
    await expect(buttonSubmit).toBeDisabled();

    // Fill the email with a valid value
    await fieldEmail.fill('hugo@alliau.me');
    await fieldEmail.blur();
    await page.waitForResponse('/_components/LiveRegistrationForm');

    await expect(page.getByText('This value is not a valid email address.')).toHaveCount(0);
    await expect(page.getByText('This value should not be blank.')).toHaveCount(1);
    await expect(buttonSubmit).toBeDisabled();

    // Fill the password with a too short value
    await fieldPassword.fill('short');
    await fieldPassword.blur();
    await page.waitForResponse('/_components/LiveRegistrationForm');

    await expect(page.getByText('This value is too short. It should have 8 characters or more.')).toHaveCount(1);
    await expect(page.getByRole('button', { name: 'Register' })).toBeDisabled();

    // Fill the password with a valid value
    await fieldPassword.fill('a-very-secure-password');
    await fieldPassword.blur();
    await page.waitForResponse('/_components/LiveRegistrationForm');

    await expect(page.getByText('This value is too short. It should have 8 characters or more.')).toHaveCount(0);
    await expect(buttonSubmit).toBeEnabled();

    // Submit the form
    await buttonSubmit.click();
    await page.waitForResponse('/_components/LiveRegistrationForm/saveRegistration');

    await expect(page.getByText('Registration successful!')).toBeVisible();
});

test('With a DTO as a LiveProp, correctly generates the query parameter', async ({ page }) => {
    await page.goto('/ux-live-component/with-dto');

    const fieldCountry = page.getByRole('textbox', { name: 'Country' });
    const fieldCity = page.getByRole('textbox', { name: 'City' });
    const output = page.locator('output');

    await expect(fieldCountry).toHaveValue('France');
    await expect(fieldCity).toHaveValue('Lyon');
    await expect(output).toHaveText(`
App\\Model\\Address Object
(
    [country] => France
    [city] => Lyon
)
`);
    await expect(page).toHaveURL('/ux-live-component/with-dto');

    // Update country
    await fieldCountry.fill('South Korea');
    await page.waitForResponse('/_components/LiveComponentWithDto');

    await expect(fieldCountry).toHaveValue('South Korea');
    await expect(fieldCity).toHaveValue('Lyon');
    await expect(output).toHaveText(`
App\\Model\\Address Object
(
    [country] => South Korea
    [city] => Lyon
)
`);
    await expect(page).toHaveURL('/ux-live-component/with-dto?address%5Bcountry%5D=South+Korea&address%5Bcity%5D=Lyon');

    // Update city
    await fieldCity.fill('Seoul');
    await page.waitForResponse('/_components/LiveComponentWithDto');

    await expect(fieldCountry).toHaveValue('South Korea');
    await expect(fieldCity).toHaveValue('Seoul');
    await expect(output).toHaveText(`
App\\Model\\Address Object
(
    [country] => South Korea
    [city] => Seoul
)
`);
    await expect(page).toHaveURL(
        '/ux-live-component/with-dto?address%5Bcountry%5D=South+Korea&address%5Bcity%5D=Seoul'
    );
});

test('With a DTO as a LiveProp, initializes its state from the URL query parameter', async ({ page }) => {
    await page.goto('/ux-live-component/with-dto?address%5Bcountry%5D=Japan&address%5Bcity%5D=Tokyo');

    const fieldCountry = page.getByRole('textbox', { name: 'Country' });
    const fieldCity = page.getByRole('textbox', { name: 'City' });
    const output = page.locator('output');

    await expect(fieldCountry).toHaveValue('Japan');
    await expect(fieldCity).toHaveValue('Tokyo');
    await expect(output).toHaveText(`
App\\Model\\Address Object
(
    [country] => Japan
    [city] => Tokyo
)
`);
});

test('With a DTO as a LiveProp, keep unknown query parameters', async ({ page }) => {
    // We add an extra "foo=bar" query parameter that the component does not know about
    await page.goto('/ux-live-component/with-dto?address%5Bcountry%5D=Italy&address%5Bcity%5D=Rome&foo=bar');

    const fieldCountry = page.getByRole('textbox', { name: 'Country' });
    const fieldCity = page.getByRole('textbox', { name: 'City' });
    const output = page.locator('output');

    await expect(fieldCountry).toHaveValue('Italy');
    await expect(fieldCity).toHaveValue('Rome');
    await expect(output).toHaveText(`
App\\Model\\Address Object
(
    [country] => Italy
    [city] => Rome
)
`);

    // Update city
    await fieldCity.fill('Milan');
    await page.waitForResponse('/_components/LiveComponentWithDto');

    await expect(fieldCountry).toHaveValue('Italy');
    await expect(fieldCity).toHaveValue('Milan');
    await expect(output).toHaveText(`
App\\Model\\Address Object
(
    [country] => Italy
    [city] => Milan
)
`);
    // The "foo=bar" query parameter must be preserved
    await expect(page).toHaveURL(
        '/ux-live-component/with-dto?address%5Bcountry%5D=Italy&address%5Bcity%5D=Milan&foo=bar'
    );
});

test('With a DTO Collection as a LiveProp, correctly generates the query parameter', async ({ page }) => {
    await page.goto('/ux-live-component/with-dto-collection');

    const buttonAddAddress = page.getByRole('button', { name: 'Add address' });
    const output = page.locator('output');

    await expect(output).toHaveText(`
Array
(
)
`);
    await expect(page).toHaveURL('/ux-live-component/with-dto-collection');

    // Add first address
    await buttonAddAddress.click();
    await page.waitForResponse('/_components/LiveComponentWithDtoCollection/addAddress');

    await expect(output).toHaveText(`
Array
(
    [0] => App\\Model\\Address Object
        (
            [country] => France
            [city] => Lyon
        )

)
`);
    await expect(page).toHaveURL(
        '/ux-live-component/with-dto-collection?addresses%5B0%5D%5Bcountry%5D=France&addresses%5B0%5D%5Bcity%5D=Lyon'
    );

    // Add second address
    await buttonAddAddress.click();
    await page.waitForResponse('/_components/LiveComponentWithDtoCollection/addAddress');

    await expect(output).toHaveText(`
Array
(
    [0] => App\\Model\\Address Object
        (
            [country] => France
            [city] => Lyon
        )

    [1] => App\\Model\\Address Object
        (
            [country] => South Korea
            [city] => Seoul
        )

)
`);
    await expect(page).toHaveURL(
        '/ux-live-component/with-dto-collection?addresses%5B0%5D%5Bcountry%5D=France&addresses%5B0%5D%5Bcity%5D=Lyon&addresses%5B1%5D%5Bcountry%5D=South+Korea&addresses%5B1%5D%5Bcity%5D=Seoul'
    );

    // Reset
    await page.getByRole('button', { name: 'Reset' }).click();
    await page.waitForResponse('/_components/LiveComponentWithDtoCollection/reset');

    await expect(output).toHaveText(`
Array
(
)
`);
    await expect(page).toHaveURL('/ux-live-component/with-dto-collection');
});

test('With a DTO as a LiveProp, with the Symfony Serializer', async ({ page }) => {
    await page.goto('/ux-live-component/with-dto-and-serializer');

    await page.getByRole('button', { name: 'Init address' }).click();
    await page.waitForResponse('/_components/LiveComponentWithDtoAndSerializer/initAddress');

    await expect(page.locator('output')).toHaveText(`
App\\Model\\Address Object
(
    [country] => France
    [city] => Lyon
)
`);
    await expect(page).toHaveURL(
        '/ux-live-component/with-dto-and-serializer?address%5Bserialized_country%5D=France&address%5Bserialized_city%5D=Lyon'
    );
});

test('With a DTO as a LiveProp, with custom methods to hydrate/dehydrate the DTO', async ({ page }) => {
    await page.goto('/ux-live-component/with-dto-and-custom-hydration-methods');

    await page.getByRole('button', { name: 'Init address' }).click();
    await page.waitForResponse('/_components/LiveComponentWithDtoAndCustomHydrationMethods/initAddress');

    await expect(page.locator('output')).toHaveText(`
App\\Model\\Address Object
(
    [country] => France
    [city] => Lyon
)
`);
    await expect(page).toHaveURL(
        '/ux-live-component/with-dto-and-custom-hydration-methods?address%5Bx-country%5D=France&address%5Bx-city%5D=Lyon'
    );
});

test('With a DTO as a LiveProp, with custom hydration extension', async ({ page }) => {
    await page.goto('/ux-live-component/with-dto-and-hydration-extension');

    await page.getByRole('button', { name: 'Init point' }).click();
    await page.waitForResponse('/_components/LiveComponentWithDtoAndHydrationExtension/initPoint');

    await expect(page.locator('output')).toHaveText(`
App\\Model\\Point Object
(
    [x] => 69.42
    [y] => -1.337
)
`);
    await expect(page).toHaveURL(
        '/ux-live-component/with-dto-and-hydration-extension?point%5Bpx%5D=69.42&point%5Bpy%5D=-1.337'
    );
});

test('Item list, can add item, modify item, remove a specific item or all items at once', async ({ page }) => {
    await page.goto('ux-live-component/item-list');

    const buttonAddItem = page.getByRole('button', { name: 'Add item' });
    const buttonDeleteAll = page.getByRole('button', { name: 'Delete all ' });

    await expect(page.getByText('No items.')).toBeVisible();

    // Add one item
    await buttonAddItem.click();
    await page.waitForResponse('/_components/LiveItemList/addItem');

    await expect(page).toHaveURL('/ux-live-component/item-list?items%5B0%5D=');
    await expect(page.getByText('No items.')).not.toBeVisible();
    await expect(page.getByRole('textbox', { name: 'Item #0' })).toHaveValue('');

    await page.getByRole('textbox', { name: 'Item #0' }).fill('The first item');
    await page.waitForResponse('/_components/LiveItemList');

    await expect(page).toHaveURL('/ux-live-component/item-list?items%5B0%5D=The+first+item');
    await expect(page.getByRole('textbox', { name: 'Item #0' })).toHaveValue('The first item');

    // Add two more items
    await buttonAddItem.click();
    await page.waitForResponse('/_components/LiveItemList/addItem');
    await buttonAddItem.click();
    await page.waitForResponse('/_components/LiveItemList/addItem');

    await expect(page).toHaveURL(
        '/ux-live-component/item-list?items%5B0%5D=The+first+item&items%5B1%5D=&items%5B2%5D='
    );
    await expect(page.getByRole('textbox', { name: 'Item #0' })).toHaveValue('The first item');
    await expect(page.getByRole('textbox', { name: 'Item #1' })).toHaveValue('');
    await expect(page.getByRole('textbox', { name: 'Item #2' })).toHaveValue('');

    // Fill the last item
    await page.getByRole('textbox', { name: 'Item #2' }).fill('The last item');
    await page.waitForResponse('/_components/LiveItemList');

    await expect(page).toHaveURL(
        '/ux-live-component/item-list?items%5B0%5D=The+first+item&items%5B1%5D=&items%5B2%5D=The+last+item'
    );
    await expect(page.getByRole('textbox', { name: 'Item #2' })).toHaveValue('The last item');

    // Delete the 2nd item
    await page.getByTitle('Delete item #1').click();
    await page.waitForResponse('/_components/LiveItemList/deleteItem');

    await expect(page).toHaveURL('/ux-live-component/item-list?items%5B0%5D=The+first+item&items%5B2%5D=The+last+item');
    await expect(page.getByRole('textbox', { name: 'Item #0' })).toHaveValue('The first item');
    await expect(page.getByRole('textbox', { name: 'Item #2' })).toHaveValue('The last item');

    // Delete all items
    await buttonDeleteAll.click();
    await page.waitForResponse('/_components/LiveItemList/deleteItems');

    await expect(page).toHaveURL('/ux-live-component/item-list');
    await expect(page.getByText('No items.')).toBeVisible();
});

test('LiveProp should be aliased', async ({ page }) => {
    await page.goto('/ux-live-component/with-aliased-live-props');

    const fieldQuery = page.getByRole('textbox', { name: 'Query' });
    const fieldCategory = page.getByRole('textbox', { name: 'Category' });

    // Fill "query"
    await fieldQuery.fill('Symfony is great!');
    await page.waitForResponse('/_components/LiveComponentWithAliasedLiveProps');

    await expect(page).toHaveURL('/ux-live-component/with-aliased-live-props?q=Symfony+is+great%21&cat=');

    // Fill "category"
    await fieldCategory.fill('Web development');
    await page.waitForResponse('/_components/LiveComponentWithAliasedLiveProps');

    await expect(page).toHaveURL(
        '/ux-live-component/with-aliased-live-props?q=Symfony+is+great%21&cat=Web+development'
    );
});
