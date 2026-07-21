# Table

A responsive table component.

```twig {"preview":true,"height":"30rem"}
{%- set invoices = [
    {invoice: 'INV001', paymentStatus: 'Paid', totalAmount: '$250.00', paymentMethod: 'Credit Card'},
    {invoice: 'INV002', paymentStatus: 'Pending', totalAmount: '$150.00', paymentMethod: 'PayPal'},
    {invoice: 'INV003', paymentStatus: 'Unpaid', totalAmount: '$350.00', paymentMethod: 'Bank Transfer'},
    {invoice: 'INV004', paymentStatus: 'Paid', totalAmount: '$450.00', paymentMethod: 'Credit Card'},
    {invoice: 'INV005', paymentStatus: 'Paid', totalAmount: '$550.00', paymentMethod: 'PayPal'},
    {invoice: 'INV006', paymentStatus: 'Pending', totalAmount: '$200.00', paymentMethod: 'Bank Transfer'},
    {invoice: 'INV007', paymentStatus: 'Unpaid', totalAmount: '$300.00', paymentMethod: 'Credit Card'},
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
            <twig:Table:Cell class="text-right">$2,500.00</twig:Table:Cell>
        </twig:Table:Row>
    </twig:Table:Footer>
</twig:Table>
```

## Installation

::: installation

## Usage

```twig
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

### Footer

Use the `TableFooter` component to add a footer to the table.

```twig {"preview":true,"height":"300px"}
{%- set invoices = [
    {invoice: 'INV001', paymentStatus: 'Paid', totalAmount: '$250.00', paymentMethod: 'Credit Card'},
    {invoice: 'INV002', paymentStatus: 'Pending', totalAmount: '$150.00', paymentMethod: 'PayPal'},
    {invoice: 'INV003', paymentStatus: 'Unpaid', totalAmount: '$350.00', paymentMethod: 'Bank Transfer'},
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
            <twig:Table:Cell class="text-right">$2,500.00</twig:Table:Cell>
        </twig:Table:Row>
    </twig:Table:Footer>
</twig:Table>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true,"height":"550px"}
<div class="flex flex-col gap-8 w-full items-center">
    {# Arabic #}
    <twig:Table dir="rtl">
        <twig:Table:Caption>قائمة بفواتيرك الأخيرة.</twig:Table:Caption>
        <twig:Table:Header>
            <twig:Table:Row>
                <twig:Table:Head class="w-[100px]">الفاتورة</twig:Table:Head>
                <twig:Table:Head>الحالة</twig:Table:Head>
                <twig:Table:Head>الطريقة</twig:Table:Head>
                <twig:Table:Head class="text-right">المبلغ</twig:Table:Head>
            </twig:Table:Row>
        </twig:Table:Header>
        <twig:Table:Body>
            <twig:Table:Row>
                <twig:Table:Cell class="font-medium">INV001</twig:Table:Cell>
                <twig:Table:Cell>مدفوع</twig:Table:Cell>
                <twig:Table:Cell>بطاقة ائتمانية</twig:Table:Cell>
                <twig:Table:Cell class="text-right">$250.00</twig:Table:Cell>
            </twig:Table:Row>
            <twig:Table:Row>
                <twig:Table:Cell class="font-medium">INV002</twig:Table:Cell>
                <twig:Table:Cell>قيد الانتظار</twig:Table:Cell>
                <twig:Table:Cell>PayPal</twig:Table:Cell>
                <twig:Table:Cell class="text-right">$150.00</twig:Table:Cell>
            </twig:Table:Row>
            <twig:Table:Row>
                <twig:Table:Cell class="font-medium">INV003</twig:Table:Cell>
                <twig:Table:Cell>غير مدفوع</twig:Table:Cell>
                <twig:Table:Cell>تحويل بنكي</twig:Table:Cell>
                <twig:Table:Cell class="text-right">$350.00</twig:Table:Cell>
            </twig:Table:Row>
        </twig:Table:Body>
        <twig:Table:Footer>
            <twig:Table:Row>
                <twig:Table:Cell colspan="3">المجموع</twig:Table:Cell>
                <twig:Table:Cell class="text-right">$2,500.00</twig:Table:Cell>
            </twig:Table:Row>
        </twig:Table:Footer>
    </twig:Table>

    {# Hebrew #}
    <twig:Table dir="rtl">
        <twig:Table:Caption>רשימת החשבוניות האחרונות שלך.</twig:Table:Caption>
        <twig:Table:Header>
            <twig:Table:Row>
                <twig:Table:Head class="w-[100px]">חשבונית</twig:Table:Head>
                <twig:Table:Head>סטטוס</twig:Table:Head>
                <twig:Table:Head>שיטה</twig:Table:Head>
                <twig:Table:Head class="text-right">סכום</twig:Table:Head>
            </twig:Table:Row>
        </twig:Table:Header>
        <twig:Table:Body>
            <twig:Table:Row>
                <twig:Table:Cell class="font-medium">INV001</twig:Table:Cell>
                <twig:Table:Cell>שולם</twig:Table:Cell>
                <twig:Table:Cell>כרטיס אשראי</twig:Table:Cell>
                <twig:Table:Cell class="text-right">$250.00</twig:Table:Cell>
            </twig:Table:Row>
            <twig:Table:Row>
                <twig:Table:Cell class="font-medium">INV002</twig:Table:Cell>
                <twig:Table:Cell>ממתין</twig:Table:Cell>
                <twig:Table:Cell>PayPal</twig:Table:Cell>
                <twig:Table:Cell class="text-right">$150.00</twig:Table:Cell>
            </twig:Table:Row>
            <twig:Table:Row>
                <twig:Table:Cell class="font-medium">INV003</twig:Table:Cell>
                <twig:Table:Cell>לא שולם</twig:Table:Cell>
                <twig:Table:Cell>העברה בנקאית</twig:Table:Cell>
                <twig:Table:Cell class="text-right">$350.00</twig:Table:Cell>
            </twig:Table:Row>
        </twig:Table:Body>
        <twig:Table:Footer>
            <twig:Table:Row>
                <twig:Table:Cell colspan="3">סה"כ</twig:Table:Cell>
                <twig:Table:Cell class="text-right">$2,500.00</twig:Table:Cell>
            </twig:Table:Row>
        </twig:Table:Footer>
    </twig:Table>
</div>
```

## API Reference

::: api-reference
