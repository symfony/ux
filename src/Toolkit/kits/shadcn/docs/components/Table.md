# Table

The Table component provides a structured way to display data in rows and columns with support for headers, captions, and customizable styling.

## Examples

### Basic Table

```twig
<twig:Table>
    <twig:Table:Caption>A list of your recent invoices.</twig:Table:Caption>
    <twig:Table:Header>
        <twig:Table:Row>
            <twig:Table:Head className="w-[100px]">Invoice</twig:Table:Head>
            <twig:Table:Head>Status</twig:Table:Head>
            <twig:Table:Head>Method</twig:Table:Head>
            <twig:Table:Head className="text-right">Amount</twig:Table:Head>
        </twig:Table:Row>
    </twig:Table:Header>
    <twig:Table:Body>
        <twig:Table:Row>
            <twig:Table:Cell className="font-medium">INV001</twig:Table:Cell>
            <twig:Table:Cell>Paid</twig:Table:Cell>
            <twig:Table:Cell>Credit Card</twig:Table:Cell>
            <twig:Table:Cell className="text-right">$250.00</twig:Table:Cell>
        </twig:Table:Row>
    </twig:Table:Body>
</twig:Table>
``` 
