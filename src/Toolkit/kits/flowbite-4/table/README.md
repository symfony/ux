# Table

Use the table component to show text, images, links, and other elements inside a structured set of data made up of rows and columns of table cells

```twig {"preview":true}
{%- set products = [
    {name: 'Apple MacBook Pro 17"', color: 'Silver', category: 'Laptop', price: '$2999', stock: 231},
    {name: 'Microsoft Surface Pro', color: 'White', category: 'Laptop PC', price: '$1999', stock: 423},
    {name: 'Magic Mouse 2', color: 'Black', category: 'Accessories', price: '$99', stock: 121},
] -%}
<div class="w-full relative overflow-x-auto bg-neutral-primary-soft shadow-xs rounded-base border border-default">
    <twig:Table>
        <twig:Table:Caption>
            Our products
            <p class="mt-1.5 text-sm font-normal text-body">Browse a list of Flowbite products designed to help you work and play, stay organized, get answers, keep in touch, grow your business, and more.</p>
        </twig:Table:Caption>
        <twig:Table:Header class="border-t">
            <twig:Table:Row>
                <twig:Table:Head>Product name</twig:Table:Head>
                <twig:Table:Head>Color</twig:Table:Head>
                <twig:Table:Head>Category</twig:Table:Head>
                <twig:Table:Head>Price</twig:Table:Head>
                <twig:Table:Head>Stock</twig:Table:Head>
            </twig:Table:Row>
        </twig:Table:Header>
        <twig:Table:Body>
            {% for product in products %}
                <twig:Table:Row>
                    <twig:Table:Head scope="row" class="font-medium text-heading whitespace-nowrap">{{ product.name }}</twig:Table:Head>
                    <twig:Table:Cell>{{ product.color }}</twig:Table:Cell>
                    <twig:Table:Cell>{{ product.category }}</twig:Table:Cell>
                    <twig:Table:Cell>{{ product.price }}</twig:Table:Cell>
                    <twig:Table:Cell>{{ product.stock }}</twig:Table:Cell>
                </twig:Table:Row>
            {% endfor %}
        </twig:Table:Body>
        <twig:Table:Footer>
            <twig:Table:Row>
                <twig:Table:Head scope="row" class="text-base" colspan="3">Total</twig:Table:Head>
                <twig:Table:Cell>$5997</twig:Table:Cell>
                <twig:Table:Cell>775</twig:Table:Cell>
            </twig:Table:Row>
        </twig:Table:Footer>
    </twig:Table>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:Table>
    <twig:Table:Caption>A list of your recent invoices.</twig:Table:Caption>
    <twig:Table:Header>
        <twig:Table:Row>
            <twig:Table:Head>Invoice</twig:Table:Head>
            <twig:Table:Head>Status</twig:Table:Head>
            <twig:Table:Head>Method</twig:Table:Head>
            <twig:Table:Head class="text-right">Amount</twig:Table:Head>
        </twig:Table:Row>
    </twig:Table:Header>
    <twig:Table:Body>
        <twig:Table:Row>
            <twig:Table:Cell class="font-medium">INV001</twig:Table:Cell>
            <twig:Table:Cell>Paid</twig:Table:Cell>
            <twig:Table:Cell>Credit Card</twig:Table:Cell>
            <twig:Table:Cell class="text-right">$250.00</twig:Table:Cell>
        </twig:Table:Row>
    </twig:Table:Body>
</twig:Table>
```

## Examples

### Highlight striped

Use this example to increase the readability of the data sets by alternating the background color of every second table row.

```twig {"preview":true}
{%- set products = [
    {name: 'Apple MacBook Pro 17"', color: 'Silver', category: 'Laptop', price: '$2999', stock: 231},
    {name: 'Microsoft Surface Pro', color: 'White', category: 'Laptop PC', price: '$1999', stock: 423},
    {name: 'Magic Mouse 2', color: 'Black', category: 'Accessories', price: '$99', stock: 121},
] -%}
<div class="w-full relative overflow-x-auto bg-neutral-primary-soft shadow-xs rounded-base border border-default">
    <twig:Table>
        <twig:Table:Header>
            <twig:Table:Row>
                <twig:Table:Head>Product name</twig:Table:Head>
                <twig:Table:Head>Color</twig:Table:Head>
                <twig:Table:Head>Category</twig:Table:Head>
                <twig:Table:Head>Price</twig:Table:Head>
                <twig:Table:Head>Stock</twig:Table:Head>
            </twig:Table:Row>
        </twig:Table:Header>
        <twig:Table:Body highlight="striped">
            {% for product in products %}
                <twig:Table:Row>
                    <twig:Table:Head scope="row" class="font-medium text-heading whitespace-nowrap">{{ product.name }}</twig:Table:Head>
                    <twig:Table:Cell>{{ product.color }}</twig:Table:Cell>
                    <twig:Table:Cell>{{ product.category }}</twig:Table:Cell>
                    <twig:Table:Cell>{{ product.price }}</twig:Table:Cell>
                    <twig:Table:Cell>{{ product.stock }}</twig:Table:Cell>
                </twig:Table:Row>
            {% endfor %}
        </twig:Table:Body>
        <twig:Table:Footer>
            <twig:Table:Row>
                <twig:Table:Head scope="row" class="text-base" colspan="3">Total</twig:Table:Head>
                <twig:Table:Cell>$5997</twig:Table:Cell>
                <twig:Table:Cell>775</twig:Table:Cell>
            </twig:Table:Row>
        </twig:Table:Footer>
    </twig:Table>
</div>
```

### Without border

Use this example of a table component without any border between the table cells.

```twig {"preview":true}
{%- set products = [
    {name: 'Apple MacBook Pro 17"', color: 'Silver', category: 'Laptop', price: '$2999', stock: 231},
    {name: 'Microsoft Surface Pro', color: 'White', category: 'Laptop PC', price: '$1999', stock: 423},
    {name: 'Magic Mouse 2', color: 'Black', category: 'Accessories', price: '$99', stock: 121},
] -%}
<twig:Table borderless>
    <twig:Table:Header>
        <twig:Table:Row>
            <twig:Table:Head>Product name</twig:Table:Head>
            <twig:Table:Head>Color</twig:Table:Head>
            <twig:Table:Head>Category</twig:Table:Head>
            <twig:Table:Head>Price</twig:Table:Head>
            <twig:Table:Head>Stock</twig:Table:Head>
        </twig:Table:Row>
    </twig:Table:Header>
    <twig:Table:Body>
        {% for product in products %}
            <twig:Table:Row>
                <twig:Table:Head scope="row" class="font-medium text-heading whitespace-nowrap">{{ product.name }}</twig:Table:Head>
                <twig:Table:Cell>{{ product.color }}</twig:Table:Cell>
                <twig:Table:Cell>{{ product.category }}</twig:Table:Cell>
                <twig:Table:Cell>{{ product.price }}</twig:Table:Cell>
                <twig:Table:Cell>{{ product.stock }}</twig:Table:Cell>
            </twig:Table:Row>
        {% endfor %}
    </twig:Table:Body>
    <twig:Table:Footer>
        <twig:Table:Row>
            <twig:Table:Head scope="row" class="text-base" colspan="3">Total</twig:Table:Head>
            <twig:Table:Cell>$5997</twig:Table:Cell>
            <twig:Table:Cell>775</twig:Table:Cell>
        </twig:Table:Row>
    </twig:Table:Footer>
</twig:Table>
```

## API Reference

::: api-reference
