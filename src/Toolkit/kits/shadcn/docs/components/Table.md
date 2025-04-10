# Table

A component for displaying structured data in rows and columns with support for headers, captions, and customizable styling.

```twig {"preview":true,"height":"400px"}
{%- set invoices = [
  { invoice: "INV001", paymentStatus: "Paid", totalAmount: "$250.00", paymentMethod: "Credit Card" },
  { invoice: "INV002", paymentStatus: "Pending", totalAmount: "$150.00", paymentMethod: "PayPal" },
  { invoice: "INV003", paymentStatus: "Unpaid", totalAmount: "$350.00", paymentMethod: "Bank Transfer" },
] -%}
<twig:Table>
    <twig:Table:Caption>A list of your recent invoices.</twig:Table:Caption>
    <twig:Table:Header>
        <twig:Table:Row>
            <twig:Table:Head class="w-[100px]">Invoice</twig:Table:Head>
            <twig:Table:Head>Status</twig:Table:Head>
            <twig:Table:Head>Method</twig:Table:Head>
            <twig:Table:Head class="text-right">Amount</twig:Table:Head>
        </twig:Table:Row>
    </twig:Table:Header>
    <twig:Table:Body>
        {% for invoice in invoices %}
            <twig:Table:Row>
                <twig:Table:Cell class="font-medium">{{ invoice.invoice }}</twig:Table:Cell>
                <twig:Table:Cell>{{ invoice.paymentStatus }}</twig:Table:Cell>
                <twig:Table:Cell>{{ invoice.paymentMethod }}</twig:Table:Cell>
                <twig:Table:Cell class="text-right">{{ invoice.totalAmount }}</twig:Table:Cell>
            </twig:Table:Row>
        {% endfor %}
    </twig:Table:Body>
</twig:Table>
```

## Installation

<!-- Placeholder: Installation -->

## Usage

<!-- Placeholder: Usage -->

## Examples

### Basic Table

```twig {"preview":true,"height":"550px"}
{%- set invoices = [
  { invoice: "INV001", paymentStatus: "Paid", totalAmount: "$250.00", paymentMethod: "Credit Card" },
  { invoice: "INV002", paymentStatus: "Pending", totalAmount: "$150.00", paymentMethod: "PayPal" },
  { invoice: "INV003", paymentStatus: "Unpaid", totalAmount: "$350.00", paymentMethod: "Bank Transfer" },
  { invoice: "INV004", paymentStatus: "Paid", totalAmount: "$450.00", paymentMethod: "Credit Card" },
  { invoice: "INV005", paymentStatus: "Paid", totalAmount: "$550.00", paymentMethod: "PayPal" },
  { invoice: "INV006", paymentStatus: "Pending", totalAmount: "$200.00", paymentMethod: "Bank Transfer" },
  { invoice: "INV007", paymentStatus: "Unpaid", totalAmount: "$300.00", paymentMethod: "Credit Card" },
] -%}
<twig:Table>
    <twig:Table:Caption>A list of your recent invoices.</twig:Table:Caption>
    <twig:Table:Header>
        <twig:Table:Row>
            <twig:Table:Head class="w-[100px]">Invoice</twig:Table:Head>
            <twig:Table:Head>Status</twig:Table:Head>
            <twig:Table:Head>Method</twig:Table:Head>
            <twig:Table:Head class="text-right">Amount</twig:Table:Head>
        </twig:Table:Row>
    </twig:Table:Header>
    <twig:Table:Body>
        {% for invoice in invoices %}
            <twig:Table:Row>
                <twig:Table:Cell class="font-medium">{{ invoice.invoice }}</twig:Table:Cell>
                <twig:Table:Cell>{{ invoice.paymentStatus }}</twig:Table:Cell>
                <twig:Table:Cell>{{ invoice.paymentMethod }}</twig:Table:Cell>
                <twig:Table:Cell class="text-right">{{ invoice.totalAmount }}</twig:Table:Cell>
            </twig:Table:Row>
        {% endfor %}
    </twig:Table:Body>
    <twig:Table:Footer>
        <twig:Table:Row>
            <twig:Table:Cell colspan="3">Total</twig:Table:Cell>
            <twig:Table:Cell class="text-right">$1,500.00</twig:Table:Cell>
        </twig:Table:Row>
    </twig:Table:Footer>
</twig:Table>
``` 
